<?php

namespace Ixianming\Routing;

use Illuminate\Support\Str;
use Illuminate\Routing\RouteCollection as OriginalRouteCollection;

class RouteCollection extends OriginalRouteCollection
{
    /**
     * Whether or not closure routing is allowed. The default value is false.
     *
     * @var bool
     */
    protected $closureRoute = false;

    /**
     * Whether the route name is unique. The default value is true.
     *
     * @var bool
     */
    protected $uniqueRouteName = true;

    /**
     * Whether reuse action is allowed. The default value is true.
     *
     * @var bool
     */
    protected $allowReuseAction = true;

    /**
     * The routing file path currently loading.
     *
     * @var string|null
     */
    protected $currentlyLoadingFilePath = null;

    /**
     * Set whether or not closure routing is allowed. The default value is false.
     *
     * @param bool $closureRoute
     * @return void
     */
    public function setCanUseClosureRoute($closureRoute)
    {
        $this->closureRoute = is_bool($closureRoute) ? $closureRoute : false;
    }

    /**
     * Set Whether the route name is unique. The default value is true.
     *
     * @param bool $uniqueRouteName
     * @return void
     */
    public function setRouteNameIsUnique($uniqueRouteName)
    {
        $this->uniqueRouteName = is_bool($uniqueRouteName) ? $uniqueRouteName : true;
    }

    /**
     * Set whether reuse action is allowed. The default value is true.
     *
     * @param bool $allowReuseAction
     * @return void
     */
    public function setActionCanBeReused($allowReuseAction)
    {
        $this->allowReuseAction = is_bool($allowReuseAction) ? $allowReuseAction : true;
    }

    /**
     * Set the routing file path currently loading.
     *
     * @param string|null $filePath
     * @return void
     */
    public function setCurrentlyLoadingFilePath($filePath)
    {
        $this->currentlyLoadingFilePath = is_string($filePath) ? $filePath : null;
    }

    /**
     * Set the routing file path currently loading to empty.
     *
     * @return void
     */
    public function setCurrentlyLoadingFilePathToNull()
    {
        $this->currentlyLoadingFilePath = null;
    }

    /**
     * Get a randomly generated route name.
     *
     * @return string
     */
    protected function generateRouteName()
    {
        return 'Ixianming::' . Str::random();
    }

    /**
     * Add the given route to the arrays of routes.
     *
     * @param \Illuminate\Routing\Route $route
     * @return void
     * @throws \Exception
     */
    protected function addToCollections($route)
    {
        $domainAndUri = $route->getDomain() . $route->uri();

        if (!isset($route->idstr)) {
            $route->idstr = Str::random();
        }

        if (!isset($route->definedFile)) {
            $route->definedFile = $this->currentlyLoadingFilePath;
            $route->definedLine = 0;

            // Get the file and line that defining this route from the debug backtrace.
            $debugBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 9);

            $backtraceKey = null;
            if (isset($debugBacktrace[4]['class'])
                && isset($debugBacktrace[4]['function'])
                && $debugBacktrace[4]['class'] == 'Illuminate\Support\Facades\Facade'
                && $debugBacktrace[4]['function'] == '__callStatic'
            ) {
                $backtraceKey = 4;
            } elseif (isset($debugBacktrace[5]['class'])
                && isset($debugBacktrace[5]['function'])
                && $debugBacktrace[5]['class'] == 'Illuminate\Routing\RouteRegistrar'
                && $debugBacktrace[5]['function'] == '__call'
            ) {
                $backtraceKey = 5;
            } elseif (isset($debugBacktrace[3]['class'])
                && isset($debugBacktrace[3]['file'])
                && $debugBacktrace[3]['class'] == 'Illuminate\Routing\Router'
                && !Str::endsWith($debugBacktrace[3]['file'], 'Illuminate\Support\Facades\Facade.php')
                && !Str::endsWith($debugBacktrace[3]['file'], 'Illuminate\Routing\RouteRegistrar.php')) {
                $backtraceKey = 3;
            }

            if ($backtraceKey != null) {
                $route->definedFile = explode(base_path(), $debugBacktrace[$backtraceKey]['file'])[1] ?? $debugBacktrace[$backtraceKey]['file'];
                $route->definedLine = $debugBacktrace[$backtraceKey]['line'] ?? 0;
            }
        }

        foreach ($route->methods() as $method) {
            if (!empty($this->routes[$method][$domainAndUri])) {// URL already exists.
                $conflictRoute = $this->routes[$method][$domainAndUri]; // Get the route with the same URL.

                // If it is the same route, overwrite is allowed, otherwise an error is thrown.
                if ($route->idstr != $conflictRoute->idstr) {
                    $exceptionMsg = $this->buildConflictExceptionMsg($conflictRoute, $route, 'URL');
                    throw new \InvalidArgumentException('[ Method: ' . $method . ', URL: `' . trim($route->getDomain() ?? '', '/') . '/' . trim($route->uri() ?? '', '/') . '` is repeated. ] ' . $exceptionMsg);
                }
            }

            $this->routes[$method][$domainAndUri] = $route;
        }

        $this->allRoutes[$method . $domainAndUri] = $route;
    }

    /**
     * Refresh the name look-up table.
     *
     * This is done in case any names are fluently defined or if routes are overwritten.
     *
     * @return void
     * @throws \Exception
     */
    public function refreshNameLookups()
    {
        $this->nameList = [];

        foreach ($this->allRoutes as $route) {
            $name = $route->getName();

            if (Str::endsWith($name, '.')) {
                $name = null;
            }

            if ($name) {
                if ($this->uniqueRouteName && !empty($this->nameList[$name])) {
                    $conflictRoute = $this->nameList[$name];

                    $exceptionMsg = $this->buildConflictExceptionMsg($conflictRoute, $route, 'named route');
                    throw new \InvalidArgumentException('[ Named route: `' . $name . '` is repeated. ] ' . $exceptionMsg);
                }
            } else {
                $route->name($this->generateRouteName());
            }

            $this->nameList[$route->getName()] = $route;
        }
    }

    /**
     * Refresh the action look-up table.
     *
     * This is done in case any actions are overwritten with new controllers.
     *
     * @return void
     * @throws \Exception
     */
    public function refreshActionLookups()
    {
        $this->actionList = [];

        foreach ($this->allRoutes as $route) {
            $controller = $route->getAction('controller');

            $rootMiddlewareGroup = $route->getAction('middleware')[0] ?? null;

            if (!empty($controller)) {
                $controller = trim($controller, '\\');

                if (!$this->allowReuseAction && !empty($this->actionList[$controller])) {
                    $conflictRoute = $this->actionList[$controller];

                    $exceptionMsg = $this->buildConflictExceptionMsg($conflictRoute, $route, 'controller');
                    throw new \InvalidArgumentException('[ Controller: `' . $controller . '` is reused. ] ' . $exceptionMsg);
                }

                $explodeTemp = explode('@', $controller);
                $class = $explodeTemp[0];
                $method = $explodeTemp[1] ?? null;

                if ((empty($method) && !class_exists($class)) || (!empty($method) && !method_exists($class, $method))) {
                    if (empty($method)) {
                        $controllerExistsErrMsg = '[ Class `' . $class . '` used by route does not exist. ]';
                    } else {
                        $controllerExistsErrMsg = '[ Method `' . $class . '::' . $method . '` used by route does not exist. ]';
                    }

                    $exceptionMsg = empty($route->definedFile) ? '' : ' The route is defined in' . (empty($route->definedLine) ? '' : ' line ' . $route->definedLine . ' of') . ' the `' . $route->definedFile . '` file.';
                    $exceptionMsg .= empty($rootMiddlewareGroup) ? '' : ' And the route belongs to the `' . $rootMiddlewareGroup . '` middleware group.';
                    throw new \BadMethodCallException($controllerExistsErrMsg . $exceptionMsg);
                }

                $this->addToActionList($route->getAction(), $route);
            } else {
                if (!$this->closureRoute) {
                    $exceptionMsg = empty($route->definedFile) ? '' : ' The closure route is defined in' . (empty($route->definedLine) ? '' : ' line ' . $route->definedLine . ' of') . ' the `' . $route->definedFile . '` file.';
                    $exceptionMsg .= empty($rootMiddlewareGroup) ? '' : ' And the closure route belongs to the `' . $rootMiddlewareGroup . '` middleware group.';
                    throw new \InvalidArgumentException('[ Closure route has been disabled. ] All closure routes must be converted to controller classes.' . $exceptionMsg . ' Please Read the `README.MD` of `ixianming/laravel-route-service-provider` for more details and then modify your code.');
                }
            }
        }
    }

    /**
     * Build exception message for two conflicting routes.
     *
     * @param \Illuminate\Routing\Route $conflictRoute
     * @param \Illuminate\Routing\Route $route
     * @param string $typeMsg
     * @return string
     */
    protected function buildConflictExceptionMsg($conflictRoute, $route, $typeMsg)
    {
        // Get root middleware.
        $conflictRouteRootMiddlewareGroup = $conflictRoute->getAction('middleware')[0] ?? null;
        $routeRootMiddlewareGroup = $route->getAction('middleware')[0] ?? null;

        $exceptionMsg = '';
        if (!empty($conflictRoute->definedFile) && !empty($route->definedFile)) {
            if ($conflictRoute->definedFile === $route->definedFile) {
                $exceptionMsg .= 'This ' . $typeMsg . ' is repeatedly defined in the `' . $route->definedFile . '` file.';

                if (!empty($conflictRoute->definedLine) && !empty($route->definedLine)) {
                    $exceptionMsg .= ' One of them is defined in line ' . $conflictRoute->definedLine . ', and the other is defined in line ' . $route->definedLine . '.';
                } else {
                    if (!empty($conflictRoute->definedLine) && empty($route->definedLine)) {
                        $oneOfThemLine = $conflictRoute->definedLine;
                    } elseif (empty($conflictRoute->definedLine) && !empty($route->definedLine)) {
                        $oneOfThemLine = $route->definedLine;
                    }

                    if (!empty($oneOfThemLine)) {
                        $exceptionMsg .= ' One of them is defined in line ' . $oneOfThemLine . ', and the other cannot be traced back to the line defined.';
                    }
                }

                $exceptionMsg .= ' And the routing file is assigned to the `' . $routeRootMiddlewareGroup . '` middleware group' . ($conflictRouteRootMiddlewareGroup === $routeRootMiddlewareGroup ? '.' : ' and the `' . $conflictRouteRootMiddlewareGroup . '` middleware group.');
            } else {
                $exceptionMsg .= 'This ' . $typeMsg . ' is defined in' . (empty($conflictRoute->definedLine) ? '' : ' line ' . $conflictRoute->definedLine . ' of') . ' the `' . $conflictRoute->definedFile . '` file and' . (empty($route->definedLine) ? '' : ' line ' . $route->definedLine . ' of') . ' the `' . $route->definedFile . '` file.';
                if ($conflictRouteRootMiddlewareGroup === $routeRootMiddlewareGroup) {
                    $exceptionMsg .= ' And both files were assigned to the `' . $routeRootMiddlewareGroup . '` middleware group.';
                } else {
                    $exceptionMsg .= ' File `' . $conflictRoute->definedFile . '` belongs to the `' . $conflictRouteRootMiddlewareGroup . '` middleware group, and file `' . $route->definedFile . '` belongs to the `' . $routeRootMiddlewareGroup . '` middleware group.';
                }
            }
        } elseif (empty($conflictRoute->definedFile) && empty($route->definedFile)) {
            $exceptionMsg .= 'Two conflicting routes that define this ' . $typeMsg . ' belongs to the `' . $routeRootMiddlewareGroup . '` middleware group' . ($conflictRouteRootMiddlewareGroup === $routeRootMiddlewareGroup ? '' : ' and the `' . $conflictRouteRootMiddlewareGroup . '` middleware group respectively') . '. These routes failed to trace back to the definition file, so you need to check for the definition location yourself. (Tip: Neither of these routes is registered under the routes directory.)';
        } else {
            if (!empty($conflictRoute->definedFile) && empty($route->definedFile)) {
                $oneOfThemLine = $conflictRoute->definedLine;
                $oneOfThemFile = $conflictRoute->definedFile;
                $oneOfThemMiddlewareGroup = $conflictRouteRootMiddlewareGroup;
                $otherMiddlewareGroup = $routeRootMiddlewareGroup;
            } else {
                //empty($conflictRoute->definedFile) && !empty($route->definedFile)
                $oneOfThemLine = $route->definedLine;
                $oneOfThemFile = $route->definedFile;
                $oneOfThemMiddlewareGroup = $routeRootMiddlewareGroup;
                $otherMiddlewareGroup = $conflictRouteRootMiddlewareGroup;
            }

            $exceptionMsg .= 'Two conflicting routes that define this ' . $typeMsg . ' belongs to the `' . $routeRootMiddlewareGroup . '` middleware group' . ($conflictRouteRootMiddlewareGroup === $routeRootMiddlewareGroup ? '' : ' and the `' . $conflictRouteRootMiddlewareGroup . '` middleware group respectively') . '. One of the routes is defined in' . (empty($oneOfThemLine) ? '' : ' line ' . $oneOfThemLine . ' of') . ' the `' . $oneOfThemFile . '` file, which is assigned to the `' . $oneOfThemMiddlewareGroup . '` middleware group. And the other route belongs to the `' . $otherMiddlewareGroup . '` middleware group, and it failed to trace back to the definition file, so you need to check for the definition location yourself. (Tip: It is definitely not registered under the routes directory.)';
        }

        if ($typeMsg == 'named route') {
            $exceptionMsg .= ' Please Check if there are `Named route` with the same name in routes,';
        } elseif ($typeMsg == 'controller') {
            $exceptionMsg .= ' Please check whether the controller is reused in routes,';
        } else {
            $exceptionMsg .= ' Please check the ' . $typeMsg . ' definition in routes,';
        }

        $exceptionMsg .= ' or check the matching rules of the middleware groups.';
        $exceptionMsg .= ' Please Read the `README.MD` of `ixianming/laravel-route-service-provider` for more details and then modify your code.';

        return $exceptionMsg;
    }

}