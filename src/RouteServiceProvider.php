<?php

namespace Ixianming\Routing;

use Illuminate\Support\Facades\Route;
use App\Providers\RouteServiceProvider as ServiceProvider;
use Closure;

class RouteServiceProvider extends ServiceProvider
{
    protected $middlewareGroupsRules = array();

    protected $isPriorityJson = false;

    protected $rawAccept;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if (!$this->app->runningInConsole()) {
            $eJsonResponse = (isset($this->eJsonResponse) && is_bool($this->eJsonResponse)) ? $this->eJsonResponse : false;
            if ($eJsonResponse) {
                $this->setRequestAccept();
            } else {
                $this->isPriorityJson = (isset($this->ePriorityJson) && is_bool($this->ePriorityJson)) ? $this->ePriorityJson : false;
                if ($this->isPriorityJson) {
                    $this->rawAccept = $this->app['request']->header('Accept');
                    $this->setRequestAccept();
                }
            }
        }

        parent::register();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        if (!$this->app->runningInConsole()) {
            $this->app->booted(function () {
                $currentRoute = $this->app['routes']->match($this->app['request']);
                $firstMiddleware = empty($currentRoute->getAction('middleware')[0]) ? null : $currentRoute->getAction('middleware')[0];
                $groups = $this->middlewareGroupsRules();

                if (!empty($firstMiddleware) && !empty($groups[$firstMiddleware]) && $groups[$firstMiddleware]['eJsonResponse']) {
                    $this->setRequestAccept();
                } else {
                    if ($this->isPriorityJson) {
                        $this->setRequestAccept($this->rawAccept);
                    }
                }
            });
        }
    }

    /**
     * Reset request accept header.
     *
     * @return void
     */
    protected function setRequestAccept($accept = 'application/json')
    {
        $this->app['request']->headers->set('Accept', $accept);
    }

    /**
     * Get all route files real path.
     *
     * @return array
     */
    protected function loadRoutesFile($basePath)
    {
        $allRoutesFilePath = array();

        $allDirAndFilePath = glob($basePath);
        foreach ($allDirAndFilePath as $path) {
            if (is_dir($path)) {
                $allRoutesFilePath = array_merge($allRoutesFilePath, $this->loadRoutesFile($path . '/*'));
            } else {
                $allRoutesFilePath[] = $path;
            }
        }
        return $allRoutesFilePath;
    }

    /**
     * Get the basic middleware groups.
     *
     * @return array
     */
    public function getBaseMiddlewareGroups()
    {
        return (isset($this->baseMiddlewareGroups) && is_array($this->baseMiddlewareGroups)) ? array_merge(array('web', 'api'), $this->baseMiddlewareGroups) : array('web', 'api');
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $loadedProviders = $this->app->getLoadedProviders();
        if (isset($loadedProviders['App\Providers\RouteServiceProvider'])) {
            $path = config_path('app.php');
            $canWrite = is_writable($path);
            if ($canWrite) {
                $rawContent = file_get_contents($path);
                $newContent = strtr($rawContent, array('App\Providers\RouteServiceProvider' => '// App\Providers\RouteServiceProvider'));
                $writeResult = file_put_contents($path, $newContent, LOCK_EX);
            }

            if (!$this->app->runningInConsole() && !$canWrite) {
                throw new \InvalidArgumentException('Laravel\'s `App\Providers\RouteServiceProvider` has booted. Please comments `App\Providers\RouteServiceProvider::class` in the `providers` array of `config/app.php`.');
            }

            return;
        }

        $lastRoutes = Route::getRoutes();

        $RouteCollection = new RouteCollection();

        Route::setRoutes($RouteCollection);

        foreach ($lastRoutes as $route) {
            $RouteCollection->add($route);
        }

        $allRoutesFilePath = $this->loadRoutesFile(base_path('routes'));

        $middlewareGroups = $this->middlewareGroupsRules();

        foreach ($allRoutesFilePath as $routeFilePath) {
            $RouteCollection->setCurrentFilePath($routeFilePath);
            foreach ($middlewareGroups as $middlewareGroup => $rules) {
                if ($this->isMatch($middlewareGroup, $routeFilePath)) {
                    Route::domain($this->middlewareGroupDomain($middlewareGroup))
                        ->prefix($this->middlewareGroupPrefix($middlewareGroup))
                        ->name($this->middlewareGroupName($middlewareGroup))
                        ->middleware($middlewareGroup)
                        ->namespace($this->namespace)
                        ->group($routeFilePath);
                }
            }
        }

        $RouteCollection->setCurrentFilePath(null);
    }

    /**
     * Get the match rules of the basic middleware groups.
     *
     * @return array
     */
    protected function middlewareGroupsRules()
    {
        if (!empty($this->middlewareGroupsRules)) {
            return $this->middlewareGroupsRules;
        }

        $defaultMiddlewareGroupsRules = $this->defaultMiddlewareGroupsRules();

        $customMiddlewareGroupsRules = method_exists($this, 'customMiddlewareGroupsRules') ? $this->customMiddlewareGroupsRules() : array();
        if (!is_array($customMiddlewareGroupsRules)) {
            throw new \InvalidArgumentException('The return value of the method `customMiddlewareGroupsRules` is not an array. Read the `README.MD` of `ixianming/laravel-route-service-provider` for more details and then modify your code.');
        }

        $middlewareGroupsRules = array();
        foreach ($defaultMiddlewareGroupsRules as $key => $defaultValue) {
            if (!empty($customMiddlewareGroupsRules[$key])) {
                $middlewareGroupsRules[$key] = array_merge($defaultValue, $customMiddlewareGroupsRules[$key]);
            } else {
                $middlewareGroupsRules[$key] = $defaultValue;
            }
        }

        $checkNamePrefix = array();

        foreach ($middlewareGroupsRules as $groupName => $rule) {
            $this->validate($groupName, $rule);

            // Check the uniqueness of routing name prefix.
            if (!empty($rule['name'])) {
                if (!empty($checkNamePrefix[$rule['name']])) {
                    throw new \InvalidArgumentException('Routing name prefix: `' . $rule['name'] . '` is repeated in different basic middleware groups. Check the config of `' . $groupName . ', ' . implode(', ', $checkNamePrefix) . '` basic middleware group. Read the `README.MD` of `ixianming/laravel-route-service-provider` for more details and then modify your code.');
                }
                $checkNamePrefix[$rule['name']] = $groupName;
            }

            $this->middlewareGroupsRules[$groupName]['domain'] = $rule['domain'];
            $this->middlewareGroupsRules[$groupName]['prefix'] = trim($rule['prefix'], '/') ?: '/';
            $this->middlewareGroupsRules[$groupName]['name'] = $rule['name'];
            $this->middlewareGroupsRules[$groupName]['eJsonResponse'] = $rule['eJsonResponse'];
            $this->middlewareGroupsRules[$groupName]['matchRules'] = $rule['matchRules'];
        }

        return $this->middlewareGroupsRules;
    }

    /**
     * Validates configuration.
     *
     * @param string $groupName
     * @param array $rule
     *
     * @throws \InvalidArgumentException
     */
    protected function validate($groupName, $rule)
    {
        if (!empty($rule['domain']) && !is_string($rule['domain'])) {
            throw new \InvalidArgumentException('The `domain` value of the basic middleware group `' . $groupName . '` is not string. Read the `README.MD` of `ixianming/laravel-route-service-provider` for more details and then modify your code.');
        }

        if (!empty($rule['prefix']) && !is_string($rule['prefix'])) {
            throw new \InvalidArgumentException('The `prefix` value of the basic middleware group `' . $groupName . '` is not string. Read the `README.MD` of `ixianming/laravel-route-service-provider` for more details and then modify your code.');
        }

        if (!empty($rule['name']) && !is_string($rule['name'])) {
            throw new \InvalidArgumentException('The `name` value of the basic middleware group `' . $groupName . '` is not string. Read the `README.MD` of `ixianming/laravel-route-service-provider` for more details and then modify your code.');
        }

        if (!empty($rule['eJsonResponse']) && !is_bool($rule['eJsonResponse'])) {
            throw new \InvalidArgumentException('The `eJsonResponse` value of the basic middleware group `' . $groupName . '` is not Boolean. Read the `README.MD` of `ixianming/laravel-route-service-provider` for more details and then modify your code.');
        }

        if (!empty($rule['matchRules']) && !($rule['matchRules'] instanceof Closure)) {
            throw new \InvalidArgumentException('The `matchRules` of the basic middleware group `' . $groupName . '` is not a method. Read the `README.MD` of `ixianming/laravel-route-service-provider` for more details and then modify your code.');
        }
    }

    /**
     * Get the default match rules of the basic middleware groups.
     *
     * @return array
     */
    protected function defaultMiddlewareGroupsRules()
    {
        $middlewareGroups = Route::getMiddlewareGroups();

        $baseMiddlewareGroups = $this->getBaseMiddlewareGroups();

        $defaultMiddlewareGroupsRulesArr = array();
        foreach ($middlewareGroups as $middlewareGroup => $value) {
            if (in_array($middlewareGroup, $baseMiddlewareGroups)) {
                $defaultMiddlewareGroupsRulesArr[$middlewareGroup]['domain'] = null;
                $defaultMiddlewareGroupsRulesArr[$middlewareGroup]['prefix'] = '';
                $defaultMiddlewareGroupsRulesArr[$middlewareGroup]['name'] = null;
                $defaultMiddlewareGroupsRulesArr[$middlewareGroup]['eJsonResponse'] = false;
                $defaultMiddlewareGroupsRulesArr[$middlewareGroup]['matchRules'] = null;

                if ($middlewareGroup !== 'web') {
                    $defaultMiddlewareGroupsRulesArr[$middlewareGroup]['prefix'] = $middlewareGroup;
                }

                if ($middlewareGroup === 'api') {
                    $defaultMiddlewareGroupsRulesArr[$middlewareGroup]['eJsonResponse'] = true;
                }
            }
        }

        return $defaultMiddlewareGroupsRulesArr;
    }

    /**
     * Determine whether the route file match the basic middleware group
     *
     * @return boolean
     */
    protected function isMatch($middlewareGroup, $routeFilePath)
    {
        $rules = $this->middlewareGroupsRules();
        if (!empty($rules[$middlewareGroup]['matchRules'])) {
            $match = $rules[$middlewareGroup]['matchRules'](explode(base_path('routes'), $routeFilePath)[1]);
            if (is_bool($match)) {
                return $match;
            } else {
                throw new \InvalidArgumentException('The return value of `matchRules` for the basic middleware group `' . $middlewareGroup . '` is not Boolean. Read the `README.MD` of `ixianming/laravel-route-service-provider` for more details and then modify your code.');
            }
        } else {
            $tmp = explode('/', $routeFilePath);
            $routeFileName = strtolower($tmp[count($tmp) - 1]);
            $middlewareGroup = strtolower($middlewareGroup);

            return (($routeFileName == $middlewareGroup . '.php')
                || ends_with($routeFilePath, '_' . $middlewareGroup . '.php')
                || starts_with($routeFileName, $middlewareGroup . '_')
            );
        }
    }

    /**
     * Get domain of the specified basic middleware group.
     *
     * @return string | null
     */
    protected function middlewareGroupDomain($middlewareGroup)
    {
        $rules = $this->middlewareGroupsRules();
        return empty($rules[$middlewareGroup]['domain']) ? null : $rules[$middlewareGroup]['domain'];
    }

    /**
     * Get prefix of the specified basic middleware group.
     *
     * @return string
     */
    protected function middlewareGroupPrefix($middlewareGroup)
    {
        $rules = $this->middlewareGroupsRules();
        return $rules[$middlewareGroup]['prefix'];
    }

    /**
     * Get name of the specified basic middleware group.
     *
     * @return string | null
     */
    protected function middlewareGroupName($middlewareGroup)
    {
        $rules = $this->middlewareGroupsRules();
        return empty($rules[$middlewareGroup]['name']) ? null : $rules[$middlewareGroup]['name'];
    }

}
