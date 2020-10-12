<?php

namespace Ixianming\Routing;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;
use App\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * An array that stores configuration for the middleware groups.
     *
     * @var array
     */
    protected $middlewareGroupsConfig = array();

    /**
     * Register service.
     *
     * @return void
     * @throws \Exception
     */
    public function register()
    {
        if (!$this->routesAreCached()) {
            // Instantiate the route collector of the extension package.
            $routeCollection = new RouteCollection();

            // Set whether or not closure routing is allowed. The default value is false.
            if (isset($this->closureRoute) && is_bool($this->closureRoute)) {
                $routeCollection->setCanUseClosureRoute($this->closureRoute);
            }

            // Set whether the route name is unique. The default value is true.
            if (isset($this->uniqueRouteName) && is_bool($this->uniqueRouteName)) {
                $routeCollection->setRouteNameIsUnique($this->uniqueRouteName);
            }

            // Set whether reuse action is allowed. The default value is true.
            if (isset($this->allowReuseAction) && is_bool($this->allowReuseAction)) {
                $routeCollection->setActionCanBeReused($this->allowReuseAction);
            }

            // Gets all routes that have been loaded.
            $loadedRoutes = $this->app['router']->getRoutes();

            // Add the loaded routes to the new route collector.
            foreach ($loadedRoutes as $route) {
                $routeCollection->add($route);
            }
            unset($loadedRoutes);

            // Set the new route collection instance to router.
            $this->app['router']->setRoutes($routeCollection);

            // zh-cn：由于某些扩展包（如：`laravel/ui`）会注册并使用路由的自定义宏，且要求在路由文件中添加方法来调用这些自定宏。如果本扩展包的服务提供者的 boot 顺序先于其他服务提供者，那么，本扩展包的服务提供者在 boot 阶段加载路由文件时，其他服务提供者的路由自定义宏可能尚未注册，在路由文件中调用自定宏就将抛出异常。因此，应当在所有服务提供者 boot 完成后再尽快加载路由文件。（事实上，Laravel 本身也是这样做的，路由加载服务几乎是最后启动的。Laravel 服务提供者的 boot 顺序为：框架的服务提供者 -> 扩展包服务提供者 -> APP的服务提供者（含路由加载服务））
            // en: Since some extension packages (e.g. `laravel/ui`) register and use custom macros of routing and add methods to call these custom macros in the routing file. If the `boot` order of the service provider of this expansion package comes before the other service providers, then when the service provider of this expansion package loads the routing file during the `boot` phase, the routing custom macro of the other service provider may not be registered yet and calling the custom macro in the routing file will throw an exception. Therefore, the routing file should be loaded as soon as possible after all service provider boot is complete. (In fact, Laravel itself does this, and the route loading service is almost last to boot. the Laravel service provider boot order is: service providers of the framework -> service providers of extension package -> service providers of the APP (with route loading service))
            $this->app->booted(function () {
                $loadedProviders = $this->app->getLoadedProviders(); // Gets loaded service providers.

                // If the Laravel's route service provider `App\Providers\RouteServiceProvider` has been loaded, the extension pack will no longer provide service.
                if (isset($loadedProviders['App\Providers\RouteServiceProvider'])) {
                    // If the Laravel's route service provider `App\Providers\RouteServiceProvider` has been loaded, the Laravel's route service provider will be automatically commented if the file `config/app.php` is writable. Throws an exception if the file `config/app.php` is unwritable and the runtime environment is not the console.
                    $appConfigPath = config_path('app.php');
                    $canWrite = is_writable($appConfigPath);
                    if ($canWrite) {
                        $originalContent = file_get_contents($appConfigPath);
                        $newContent = strtr($originalContent, array('App\Providers\RouteServiceProvider' => '// App\Providers\RouteServiceProvider'));
                        file_put_contents($appConfigPath, $newContent, LOCK_EX);
                    }

                    if (!$this->app->runningInConsole() && !$canWrite) {
                        throw new \RuntimeException('Laravel\'s `App\Providers\RouteServiceProvider` has booted. Please comments `App\Providers\RouteServiceProvider::class` in the `providers` array of `config/app.php`.');
                    }

                    return;
                }

                // Gets all routing files.
                $routeFilesPathArr = $this->loadRouteFiles(base_path('routes'));

                // Gets the middleware groups that are allowed to match routing files and the configuration of these middleware groups.
                $middlewareGroups = $this->middlewareGroupsConfig();

                $routeCollection = $this->app['routes'];

                $fileBelongsTo = array();

                foreach ($middlewareGroups as $middlewareGroup => $config) {
                    foreach ($routeFilesPathArr as $routeFilePath) {
                        if ($this->isMatch($middlewareGroup, $routeFilePath, $config['matchRule'])) {
                            $fileAbsPath = explode(base_path(), $routeFilePath)[1] ?? $routeFilePath;
                            if (isset($fileBelongsTo[$fileAbsPath])) {
                                if ($fileBelongsTo[$fileAbsPath]['middlewareGroup'] != $middlewareGroup) {
                                    throw new \RuntimeException('The `' . $middlewareGroup . '` middleware group is loading routing file `' . $fileAbsPath . '`, but the routing file has been loaded into the `' . $fileBelongsTo[$fileAbsPath] . '` middleware group. Please do not load it repeatedly.');
                                }

                                if ($fileBelongsTo[$fileAbsPath]['loadedTimes'] >= 2) {
                                    throw new \RuntimeException('The routing file `' . $fileAbsPath . '` has been repeatedly loaded by the middleware group `' . $middlewareGroup . '` no less than 3 times, please check your routing code. If you use custom routing file matching rules, please check your custom code. Please read the `README.MD` of `ixianming/laravel-route-service-provider` for more details and then modify your code.');
                                }
                            }

                            $fileBelongsTo[$fileAbsPath]['middlewareGroup'] = $middlewareGroup;
                            $fileBelongsTo[$fileAbsPath]['loadedTimes'] = isset($fileBelongsTo[$fileAbsPath]['loadedTimes']) ? ++$fileBelongsTo[$fileAbsPath]['loadedTimes'] : 1;

                            // Set the routing file path currently loading.
                            $routeCollection->setCurrentlyLoadingFilePath($fileAbsPath);

                            Route::middleware($middlewareGroup)
                                ->namespace($config['namespace'])
                                ->domain($config['domain'])
                                ->prefix($config['prefix'])
                                ->name($config['name'])
                                ->where($config['where'])
                                ->group($routeFilePath);
                        }
                    }
                }

                unset($fileBelongsTo);
                $routeCollection->setCurrentlyLoadingFilePathToNull();
            });
        }

        parent::register();
    }

    /**
     * Gets the real path of all the routing files.
     *
     * @param string $routeDir
     * @return array
     */
    protected function loadRouteFiles($routeDir)
    {
        $routeFilesAbsPath = array();

        $allDirsAndFilesInPath = glob($routeDir);
        foreach ($allDirsAndFilesInPath as $absPath) {
            if (is_dir($absPath)) {
                $routeFilesAbsPath = array_merge($routeFilesAbsPath, $this->loadRouteFiles($absPath . '/*'));
            } else {
                if (Str::endsWith(strtolower($absPath), '.php')) {
                    $routeFilesAbsPath[] = $absPath;
                }
            }
        }

        return $routeFilesAbsPath;
    }

    /**
     * Get whether the output format of the default exception response is json. The default value is false.
     *
     * @return bool
     */
    public function defaultExceptionJsonResponse()
    {
        return isset($this->defaultExceptionJsonResponse) && is_bool($this->defaultExceptionJsonResponse) ? $this->defaultExceptionJsonResponse : false;
    }

    /**
     * Gets the middleware groups that are allowed to match routing files.
     *
     * @return array
     */
    public function getAllowMatchRouteMiddlewareGroups()
    {
        $allowMatchRouteMiddlewareGroups = array('web', 'api');

        if (isset($this->allowMatchRouteMiddlewareGroups) && is_array($this->allowMatchRouteMiddlewareGroups)) {
            foreach ($this->allowMatchRouteMiddlewareGroups as $middlewareGroupName) {
                if (is_string($middlewareGroupName) && !in_array($middlewareGroupName, $allowMatchRouteMiddlewareGroups)) {
                    $allowMatchRouteMiddlewareGroups[] = $middlewareGroupName;
                }
            }
        }

        return $allowMatchRouteMiddlewareGroups;
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {

    }

    /**
     * Verify that the routing file matches the middleware group.
     *
     * @param string $middlewareGroup
     * @param string $routeFilePath
     * @param Closure|null $matchRule
     * @return bool
     * @throws \Exception
     */
    protected function isMatch($middlewareGroup, $routeFilePath, $matchRule = null)
    {
        if (empty($matchRule)) {
            $tmp = explode('/', $routeFilePath);
            $routeFileName = strtolower($tmp[count($tmp) - 1]);
            $middlewareGroup = strtolower($middlewareGroup);

            return (
                $routeFileName == $middlewareGroup . '.php'
                || Str::endsWith($routeFileName, '_' . $middlewareGroup . '.php')
                || Str::startsWith($routeFileName, $middlewareGroup . '_')
            );
        } else {
            $routeFilePath = explode(base_path('routes'), $routeFilePath)[1] ?? $routeFilePath;
            $matchResult = $matchRule($routeFilePath);
            if (is_bool($matchResult)) {
                return $matchResult;
            } else {
                throw new \InvalidArgumentException('The return value of the custom configuration item `matchRule` of the `' . $middlewareGroup . '` middleware group is not a boolean. Please read the `README.MD` of `ixianming/laravel-route-service-provider` for more details and then modify your code.');
            }
        }
    }

    /**
     * Gets the default configuration of the middleware groups that are allowed to match routing files.
     *
     * @return array
     */
    public function defaultConfigs()
    {
        // Get all middleware groups.
        $middlewareGroups = $this->app['Illuminate\Contracts\Http\Kernel']->getMiddlewareGroups();

        // Get the middleware groups that are allowed to match routing files.
        $allowMatchRouteMiddlewareGroups = $this->getAllowMatchRouteMiddlewareGroups();

        $defaultConfigs = array();
        foreach ($middlewareGroups as $middlewareGroup => $middlewares) {
            // If this middleware group is allowed to match routing files, set a default config for it.
            if (in_array($middlewareGroup, $allowMatchRouteMiddlewareGroups)) {
                $defaultConfigs[$middlewareGroup]['namespace'] = isset($this->namespace) ? $this->namespace : null;
                $defaultConfigs[$middlewareGroup]['domain'] = null;
                $defaultConfigs[$middlewareGroup]['prefix'] = $middlewareGroup === 'web' ? '' : $middlewareGroup;
                $defaultConfigs[$middlewareGroup]['name'] = '';
                $defaultConfigs[$middlewareGroup]['where'] = array();
                $defaultConfigs[$middlewareGroup]['eJsonResponse'] = $middlewareGroup === 'api' ? true : $this->defaultExceptionJsonResponse();
                $defaultConfigs[$middlewareGroup]['matchRule'] = null;
            }
        }

        return $defaultConfigs;
    }

    /**
     * Check the correctness of the configuration.
     *
     * @param string $middlewareGroup
     * @param array $config
     * @return void
     * @throws \Exception
     */
    protected function checkConfig($middlewareGroup, $config)
    {
        $item = '';
        $valueType = 'a string';
        $error = true;

        if ($config['namespace'] !== null && !is_string($config['namespace'])) {
            $item = 'namespace';
        } elseif ($config['domain'] !== null && !is_string($config['domain'])) {
            $item = 'domain';
        } elseif ($config['prefix'] !== null && !is_string($config['prefix'])) {
            $item = 'prefix';
        } elseif ($config['name'] !== null && !is_string($config['name'])) {
            $item = 'name';
        } elseif ($config['where'] !== null && !is_array($config['where'])) {
            $item = 'where';
            $valueType = 'an array';
        } elseif ($config['eJsonResponse'] !== null && !is_bool($config['eJsonResponse'])) {
            $item = 'eJsonResponse';
            $valueType = 'a boolean';
        } elseif ($config['matchRule'] !== null && !($config['matchRule'] instanceof Closure)) {
            $item = 'matchRule';
            $valueType = 'a closure';
        } else {
            $error = false;
        }

        if ($error) {
            $exceptionMsg = 'The value of the configuration item `' . $item . '` of the middleware group `' . $middlewareGroup . '` is not ' . $valueType . '. Please read the `README.MD` of `ixianming/laravel-route-service-provider` for more details and then modify your code.';
            throw new \InvalidArgumentException($exceptionMsg);
        }
    }

    /**
     * Gets the configuration of the middleware groups that are allowed to match routing files.
     *
     * @return array
     * @throws \Exception
     */
    public function middlewareGroupsConfig()
    {
        if (!empty($this->middlewareGroupsConfig)) {
            return $this->middlewareGroupsConfig;
        }

        // Gets the default configuration of all middleware groups that are allowed to match routing files.
        $defaultConfigs = $this->defaultConfigs();

        // Gets custom configuration.
        $customMiddlewareGroupsConfig = method_exists($this, 'customMiddlewareGroupsConfig') ? $this->customMiddlewareGroupsConfig() : array();
        if (!is_array($customMiddlewareGroupsConfig)) {
            throw new \InvalidArgumentException('The return value of the method `customMiddlewareGroupsConfig` is not an array. Please read the `README.MD` of `ixianming/laravel-route-service-provider` for more details and then modify your code.');
        }

        $middlewareGroupsConfig = array();
        foreach ($defaultConfigs as $middlewareGroup => $defaultConfig) {
            if (!empty($customMiddlewareGroupsConfig[$middlewareGroup])) {
                if (!is_array($customMiddlewareGroupsConfig[$middlewareGroup])) {
                    throw new \InvalidArgumentException('The custom configuration value of the `' . $middlewareGroup . '` middleware group is not an array. Please read the `README.MD` of `ixianming/laravel-route-service-provider` for more details and then modify your code.');
                }

                $middlewareGroupsConfig[$middlewareGroup] = array_merge($defaultConfig, $customMiddlewareGroupsConfig[$middlewareGroup]);
            } else {
                $middlewareGroupsConfig[$middlewareGroup] = $defaultConfig;
            }
        }

        foreach ($middlewareGroupsConfig as $middlewareGroup => $config) {
            // Check the correctness of the configuration of the middleware group.
            $this->checkConfig($middlewareGroup, $config);

            $this->middlewareGroupsConfig[$middlewareGroup]['namespace'] = empty($config['namespace']) ? null : $config['namespace'];
            $this->middlewareGroupsConfig[$middlewareGroup]['domain'] = empty($config['domain']) ? null : $config['domain'];
            $this->middlewareGroupsConfig[$middlewareGroup]['prefix'] = empty($config['prefix']) ? '' : $config['prefix'];
            $this->middlewareGroupsConfig[$middlewareGroup]['name'] = empty($config['name']) ? '' : $config['name'];
            $this->middlewareGroupsConfig[$middlewareGroup]['where'] = empty($config['where']) ? array() : $config['where'];
            $this->middlewareGroupsConfig[$middlewareGroup]['eJsonResponse'] = $config['eJsonResponse'] ?? $this->defaultExceptionJsonResponse();
            $this->middlewareGroupsConfig[$middlewareGroup]['matchRule'] = $config['matchRule'];
        }

        return $this->middlewareGroupsConfig;
    }

}
