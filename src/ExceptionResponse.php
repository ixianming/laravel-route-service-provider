<?php

namespace Ixianming\Routing;

use Illuminate\Support\Str;

class ExceptionResponse
{
    /**
     * An array that stores whether the output format of the exception response of the middleware group is json.
     *
     * @var array
     */
    protected static $groupExceptionJsonResponse = array();

    /**
     * The handler of the response format of the exception information.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Throwable $exception
     * @return array
     */
    public static function wantsJson($request, $exception)
    {
        $eJsonResponse = false;

        $routeProvider = app()->getProvider('Ixianming\Routing\RouteServiceProvider');
        if ($routeProvider != null) {
            $currentRoute = $request->route();

            // Gets the middleware groups that are allowed to match routing files and the configuration of these middleware groups.
            try {
                $middlewareGroupsConfig = $routeProvider->middlewareGroupsConfig();
            } catch (\Throwable $e) {
                $exception = $e;
                $middlewareGroupsConfig = $routeProvider->defaultConfigs();
            }

            foreach ($middlewareGroupsConfig as $middlewareGroup => $config) {
                // Set whether the output format of the exception response of the middleware group is json.
                self::setGroupExceptionJsonResponse($middlewareGroup, $config['eJsonResponse']);
            }

            if ($currentRoute) {
                $rootMiddlewareGroup = $currentRoute->getAction('middleware')[0] ?? null;
            } else {
                $rootMiddlewareGroup = null;
                $nullPrefixMiddlewareGroups = array();

                $domain = $request->root();

                $decodedPath = trim($request->decodedPath(), '/');
                $pathArr = explode('/', $decodedPath);
                $firstPath = $pathArr[0];

                foreach ($middlewareGroupsConfig as $middlewareGroup => $config) {
                    if (Str::is('*' . trim($config['domain'] ?? '', '/') . '*', $domain)) {
                        $prefix = trim($config['prefix'] ?? '', '/');
                        if (empty($prefix)) {
                            $nullPrefixMiddlewareGroups[] = $middlewareGroup;
                        } else {
                            if ($firstPath == $prefix) {
                                $rootMiddlewareGroup = $middlewareGroup;
                                break;
                            }
                        }
                    }
                }

                $rootMiddlewareGroup = $rootMiddlewareGroup ?? ($nullPrefixMiddlewareGroups[0] ?? null);
            }

            $eJsonResponse = self::exceptionJsonResponse($routeProvider, $rootMiddlewareGroup);
        }

        if ($eJsonResponse) {
            $request->headers->set('Accept', 'application/json');
        }

        return [$request, $exception];
    }

    /**
     * Set whether the output format of the exception response of the middleware group is json.
     *
     * @param string $middlewareGroup
     * @param bool|null $eJsonResponse
     * @return void
     */
    protected static function setGroupExceptionJsonResponse($middlewareGroup, $eJsonResponse)
    {
        self::$groupExceptionJsonResponse[$middlewareGroup] = $eJsonResponse;
    }

    /**
     * Get whether the output format of the exception response is json.
     *
     * @param \Ixianming\Routing\RouteServiceProvider $middlewareGroup
     * @param string|null $middlewareGroup
     * @return bool
     */
    protected static function exceptionJsonResponse($routeProvider, $middlewareGroup = null)
    {
        return isset(self::$groupExceptionJsonResponse[$middlewareGroup]) && is_bool(self::$groupExceptionJsonResponse[$middlewareGroup]) ? self::$groupExceptionJsonResponse[$middlewareGroup] : $routeProvider->defaultExceptionJsonResponse();
    }

}