<?php

namespace Ixianming\Routing;

use Illuminate\Routing\RouteCollection as OriginalRouteCollection;

class RouteCollection extends OriginalRouteCollection
{
    /**
     * the routing file path that the current routing group is executing loading.
     *
     * @var string
     */
    protected $routeFilePath;

    /**
     * Add the given route to the arrays of routes.
     *
     * @param  \Illuminate\Routing\Route $route
     * @return void
     */
    protected function addToCollections($route)
    {
        $domainAndUri = $route->getDomain() . $route->uri();
        $route->filePath = $this->getCurrentFilePath();

        foreach ($route->methods() as $method) {
            if (!empty($this->routes[$method][$domainAndUri])) {
                $prevRoute = $this->routes[$method][$domainAndUri];
                if (!empty($prevRoute->filePath) && !empty($route->filePath)) {
                    if ($prevRoute->filePath === $route->filePath && $prevRoute->getAction('middleware')[0] === $route->getAction('middleware')[0]) {
                        $msg = 'This URL is repeatedly defined in `' . $route->filePath . '`. And this routing file belongs to the `' . $route->getAction('middleware')[0] . '` basic middleware group.';
                    } elseif ($prevRoute->filePath === $route->filePath && $prevRoute->getAction('middleware')[0] !== $route->getAction('middleware')[0]) {
                        $msg = 'This URL is repeatedly defined in `' . $route->filePath . '`, and it is important to note, that the routing file is assigned to different basic middleware groups at the same time. It involves `' . $prevRoute->getAction('middleware')[0] . '` and `' . $route->getAction('middleware')[0] . '` basic middleware group.';
                    } elseif ($prevRoute->filePath !== $route->filePath && $prevRoute->getAction('middleware')[0] === $route->getAction('middleware')[0]) {
                        $msg = 'This URL exists in `' . $prevRoute->filePath . '` and `' . $route->filePath . '`. These two routing files belong to the `' . $route->getAction('middleware')[0] . '` basic middleware group.';
                    } else {
                        $msg = 'This URL exists in `' . $prevRoute->filePath . '` (This file is assigned to the `' . $prevRoute->getAction('middleware')[0] . '` basic middleware group) and `' . $route->filePath . '` (This file is assigned to the `' . $route->getAction('middleware')[0] . '` basic middleware group).';
                    }
                } else {
                    if ($prevRoute->getAction('middleware')[0] === $route->getAction('middleware')[0]) {
                        $msg = 'This URL is repeatedly defined in the routing file, that these routing files are allocated to the `' . $route->getAction('middleware')[0] . '` basic middleware group.';
                    } else {
                        $msg = 'This URL is repeatedly defined in the routing file, that these routing files are allocated to different basic middleware group. It involves `' . $prevRoute->getAction('middleware')[0] . '` and `' . $route->getAction('middleware')[0] . '` basic middleware group.';
                    }
                }

                throw new \InvalidArgumentException('[ Route URL: `' . trim($route->getDomain() . '/' . $route->uri(), '/') . '` ] is repeated. ' . $msg . ' Please check your URL definition or check the rules of the basic middleware groups. Read the `README.MD` of `ixianming/laravel-route-service-provider` for more details and then modify your code.');
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
     */
    public function refreshNameLookups()
    {
        $this->nameList = [];

        foreach ($this->allRoutes as $route) {
            $route->filePath = $this->getCurrentFilePath();

            if ($route->getName()) {
                if (!empty($this->nameList[$route->getName()])) {
                    $prevRoute = $this->nameList[$route->getName()];
                    if (!empty($prevRoute->filePath) && !empty($route->filePath)) {
                        if ($prevRoute->filePath === $route->filePath && $prevRoute->getAction('middleware')[0] === $route->getAction('middleware')[0]) {
                            $msg = 'This named route is repeatedly defined in `' . $route->filePath . '`. And this routing file belongs to the `' . $route->getAction('middleware')[0] . '` basic middleware group.';
                        } elseif ($prevRoute->filePath === $route->filePath && $prevRoute->getAction('middleware')[0] !== $route->getAction('middleware')[0]) {
                            $msg = 'This named route is repeatedly defined in `' . $route->filePath . '`, and it is important to note, that the routing file is assigned to different basic middleware groups at the same time. It involves `' . $prevRoute->getAction('middleware')[0] . '` and `' . $route->getAction('middleware')[0] . '` basic middleware group.';
                        } elseif ($prevRoute->filePath !== $route->filePath && $prevRoute->getAction('middleware')[0] === $route->getAction('middleware')[0]) {
                            $msg = 'This named route exists in `' . $prevRoute->filePath . '` and `' . $route->filePath . '`. These two routing files belong to the `' . $route->getAction('middleware')[0] . '` basic middleware group.';
                        } else {
                            $msg = 'This named route exists in `' . $prevRoute->filePath . '` (This file is assigned to the `' . $prevRoute->getAction('middleware')[0] . '` basic middleware group) and `' . $route->filePath . '` (This file is assigned to the `' . $route->getAction('middleware')[0] . '` basic middleware group).';
                        }
                    } else {
                        if ($prevRoute->getAction('middleware')[0] === $route->getAction('middleware')[0]) {
                            $msg = 'This named route is repeatedly defined in the routing file, that these routing files are allocated to the `' . $route->getAction('middleware')[0] . '` basic middleware group.';
                        } else {
                            $msg = 'This named route is repeatedly defined in the routing file, that these routing files are allocated to different basic middleware group. It involves `' . $prevRoute->getAction('middleware')[0] . '` and `' . $route->getAction('middleware')[0] . '` basic middleware group.';
                        }
                    }

                    throw new \InvalidArgumentException('[ Named route: `' . $route->getName() . '` ] is repeated. ' . $msg . ' Please check if there are duplicate `Named route` in the routing files. Or, whether the same routing file is simultaneously assigned to different basic middleware group. Read the `README.MD` of `ixianming/laravel-route-service-provider` for more details and then modify your code.');
                }

                $this->nameList[$route->getName()] = $route;
            }
        }
    }

    /**
     * Set the routing file path that the current routing group is executing loading.
     *
     * @return void
     */
    public function setCurrentFilePath($filePath)
    {
        $this->routeFilePath = $filePath;
    }

    /**
     * Get the routing file path that the current routing group is executing loading.
     *
     * @return string | null
     */
    protected function getCurrentFilePath()
    {
        return $this->routeFilePath;
    }
}