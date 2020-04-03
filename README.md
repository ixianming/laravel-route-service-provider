# Laravel RouteServiceProvider

The documentation details the functionality of the extension package, which is more informative, but not more complex.

Usually, the default configuration can meet most of the needs, so you don't need to set it after installing the extension package. You just need to be familiar with the default rules to use it.

- [中文文档](/README-zh.md)

[TOC]

## About the extension package

The extension package's `ServiceProvider` inherits Laravel's `App\Providers\RouteServiceProvider`. therefore, after installing the extension package, the explicit bindings, filters, custom parsing logic, etc. of the routing model defined in the boot() method in `App\Providers\RouteServiceProvider` are still available.

However, it should be noted that the changes to `map()`, `mapApiRoutes()`, and `mapWebRoutes()` in `App\Providers\RouteServiceProvider` are invalid, because the extension overwrites the `map()` method and the `map()` method no longer references `mapApiRoutes()`, `mapWebRoutes()`.

## Features

- You can set middleware groups that are allowed to match routing files.

    > The extension package use default or custom rules to assign routing files to these middleware groups and perform loading.

- You can create multiple routing files for the same middleware group, and you can place these routing files anywhere in the `routes` directory.

    > The name of the routing file can use the default rules, or the developer can customize the matching rules between the routing file and the middleware group.

- You can set the response format of the exception information to the global default Json output, or you can independently set the response format of the exception information to Json for each middleware group that allows the matching routing file. (Independent settings have higher priority than global default settings)

- Check for duplicate URLs (complete URLs with domain restrictions) in all routes.

- You can set whether to allow registration of closure-based routes.

- You can set whether the names of named routes can be duplicated.

- You can set whether the controller is allowed to be reused.

- You can customize the root namespace `namespace` used by each middleware group that is allowed to match routing files.

- You can customize the subdomain `domain` used by each middleware group that is allowed to match routing files.

- You can customize the route prefix `prefix` used by each middleware group that is allowed to match routing files.

- You can customize the route name prefix `name` used by each middleware group that is allowed to match routing files.

- You can customize the route parameter regular expression constraint `where` used by each middleware group that is allowed to match routing files.

## Impact on performance

Installing and using any expansion package will inevitably bring additional performance overhead. Using this expansion package is also the case.

**The additional performance overhead is reasonable and negligible.** However, if there are extreme requirements for the application performance, please test by yourself before use.

When testing, be sure to confirm the following points:

- Make sure the only variable is whether the extension package is installed or not.

- **Make sure that the number of routing files and routes loaded by the app are the same before and after installing the extension package.**

### Use route cache

After installing the extension package, use the `php artisan route:cache` command to generate the route cache in any environment, comparison of using route cache after installation and using route cache before installation:

- Increased memory consumption (The values are measured in PHP 7.3 environment and are for reference only.):

    - Laravel 7:
    
        In theory, using the route cache only increases the memory consumption (about 55 KB) when loading the extension package service provider. However, in actual tests, the new memory consumption will increase with the number of routes. When the number of routes exceeds about 230, the new memory consumption will fluctuate significantly:

        - When the number of routes is less than about 230, the memory consumption is increased by about (about 55 + (about 0.04 * number of routes)) KB compared to the time before installing the expansion package.

        - When the number of routes is about 230-300, the memory consumption is increased by about (about 55 + (about 0.2 * number of routes)) KB compared to before the expansion package is installed.

        - When the number of routes exceeds about 300, the memory consumption is increased by about (about 55 + (about 0.3 * number of routes)) KB compared to before the expansion package was installed.
    
    - Laravel 5.* / Laravel 6:
    
        - When the number of routes is less than about 300, the memory consumption is increased by about (about 100 + (about 0.6 * the number of routes)) KB compared to before the expansion package is installed.
        
        - When the number of routes exceeds about 300, the memory consumption will increase by about (about 100 + (about 0.9 * number of routes)) KB compared to that before the expansion package is installed.
    
    The test also found that without using this extension package, after using the route cache, the number of Laravel 7 routes exceeds about 230, and the number of Laravel 5.* / Laravel 6 routes exceeding about 300, memory consumption will also occur Significant fluctuations:

    - When no extension package is installed, route cache is used, and the number of routes is less than about 230 (Laravel 7) / about 300 (Laravel 5.* / Laravel 6), the memory footprint of each route is about 4.5 KB.

    - When no extension package is installed, route cache is used, and the number of routes exceeds about 230 (Laravel 7) / 300 (Laravel 5.* / Laravel 6), the memory footprint of each route is about 9 KB.

    In my opinion, the reason for the above memory differences is related to the underlying implementation of PHP7 arrays (automatic expansion mechanism). For the specific underlying implementation of PHP7 arrays, please find the information yourself. If you have different opinions on this, welcome to exchange and learn together.

    Overall, even if your application is a large application with nearly a thousand routes, the added memory consumption is only about 300 KB, which is almost negligible.

- Increased I/O overhead:

    - Laravel 7:
    
        - For a normal response, add an IO read (about 15 KB).
        
        - In the case of an exception, add two IO reads (about 20 KB in total).

    - Laravel 5.* / Laravel 6:

        - For a normal response, add two IO reads (about 30 KB in total).
    
        - In the case of an exception, add three IO reads (about 35 KB in total).
        
Other performance overhead is the same as before the extension package was installed.

### No route cache

Without route cache, the extra performance overhead is mainly the memory overhead of `RouteServiceProvider`, `RouteCollection` and `Route` instances, and the matching operation overhead of the route file and the middleware group.

comparison of after installation and before installation:

- Increases negligible CPU computation overhead.

- Memory consumption has increased by about (about 110 + about 1.5 * number of routes)) KB compared to before the installation of the extension. (This value was measured in a PHP 7.3 environment and is for reference only.)

- Increased I/O overhead:

    - For a normal response, add two IO reads (about 30 KB in total).
    
    - In the case of an exception, add three IO reads (about 35 KB in total).

Other performance overhead is the same as before the extension package was installed.

## Installation

### Installation conditions

- PHP >= 7.0

- Laravel >= 5.3

    > Laravel 6 / Laravel 7 can also be installed and used!

### Installation

```shell
composer require ixianming/laravel-route-service-provider
```

#### Use Package Auto-Discovery

- Laravel 5.5+ uses package Auto-Discovery, so doesn't require you to manually add the `ServiceProvider`.

- You don't need to comment on Laravel's route service provider (unless the extension package throws a prompt).

#### Don't Use Package Auto-Discovery

If the Laravel version is less than 5.5 or don't use package auto-discovery:

- Comment Laravel's route service provider `App\Providers\RouteServiceProvider::class` in the `providers` array in `config/app.php`.

- Add the extension's service provider `Ixianming\Routing\RouteServiceProvider::class` to the `providers` array in `config/app.php`.

```php
'providers' => [
    // ...
    
    // App\Providers\RouteServiceProvider::class,
    
    Ixianming\Routing\RouteServiceProvider::class,
    
 ]
```

### Manually add function to handle the output format of exception information

Add the code before the `return` of the `render` method of the `App\Exceptions\Handler` class:

```php
if (method_exists(\Ixianming\Routing\ExceptionResponse::class, 'wantsJson')) {
    list($request, $exception) = \Ixianming\Routing\ExceptionResponse::wantsJson($request, $exception);
}
```

After modification, the `render` method should look like this:

```php
public function render($request, Exception $exception)
{
    // Your code ...
    
    // Your code must precede this function.
    if (method_exists(\Ixianming\Routing\ExceptionResponse::class, 'wantsJson')) {
        list($request, $exception) = \Ixianming\Routing\ExceptionResponse::wantsJson($request, $exception);
    }
    // There should be no code between the function and `return`.
    return parent::render($request, $exception);
}
```

### Notes for uninstall

- After uninstalling the extension package, remember to remove the code that handles the output format of the exception response added to the `render` method of the `App\Exceptions\Handler` class.

- After uninstalling the extension package, remember to remove the code of the attributes and methods used by the extension package added in `App\Providers\RouteServiceProvider`.

- After uninstalling the extension package, remember to remove `Ixianming\Routing\RouteServiceProvider::class` in the `providers` array in `config/app.php` and uncomment the `App\Providers\RouteServiceProvider::class`.

## Use

### Set the middleware groups that are allowed to match routing files

**The extension package default `web` and `api` middleware groups can match routing files.**

To add middleware groups that are allowed to match routing files, add the `$allowMatchRouteMiddlewareGroups` attribute to `app/Providers/RouteServiceProvider.php`:

```php
protected $allowMatchRouteMiddlewareGroups = ['middlewareGroup_1', 'middlewareGroup_2'];
```

- The value of the `$allowMatchRouteMiddlewareGroups` attribute is a one-dimensional array. The value of the array is the name of the middleware group that is allowed to match routing file.

- The value of the `$allowMatchRouteMiddlewareGroups` attribute is merged with the default value, so you only need to define new middleware groups that are allowed to match routing files in the `$allowMatchRouteMiddlewareGroups` attribute.

- When the value type of the `$allowMatchRouteMiddlewareGroups` attribute is wrong, the default value will be used.

### Set whether the global default exception response format is Json

**Before using this function, manually add a function to handle the output format of exception information in the `render` method of `App\Exceptions\Handler` class, otherwise this function is invalid.**

To set the global default exception information response format to Json format, add the `$defaultExceptionJsonResponse` attribute to `app/Providers/RouteServiceProvider.php` and set its value to `true`:

```php
protected $defaultExceptionJsonResponse = true;
```

- The value of the `$defaultExceptionJsonResponse` attribute must be a **boolean value (`true` or `false`)**.

- The `$defaultExceptionJsonResponse` attribute **default value is `false`**.

- When the value type of the `$defaultExceptionJsonResponse` attribute is wrong, the default value will be used.

- When the value of the `$defaultExceptionJsonResponse` attribute is `true`, the response format of the exception information thrown by the application is Json **when accessing an unknown route or a route under a middleware group that does not customize the exception information response format**.

- When the value of the `$defaultExceptionJsonResponse` attribute does not exist or the value is `null` or the value is `false`, **when accessing an unknown route or a route under a middleware group that does not customize the exception information response format**, the response of the exception information thrown by the application, the response format is **determined by the `Accept` parameter of the request header**.

- **When accessing the route under the middleware group with the customized exception information response format**, no matter how the value of the `$defaultExceptionJsonResponse` attribute is set, the response format of the exception information thrown by the application is determined by the customized setting of the middleware group. (see the following for details of custom rules)

### Set whether to allow registration of closure-based route

**By default, extension package are forbidden to register and use closure-based route.**

> Why prohibit registration and use of closure-based route:
> 
> - When an application is published, Laravel is usually optimized, and route cache is one of the optimization items. But route cache does not apply to closure-based route. If you use closure-based route, you will get an error when generating the cache! To prevent route cache from being unavailable when the code is released, the best solution is to always disable closure routing.
>
> - During team development, this configuration can forcibly restrict the way developers can register routes and reduce the risk of mistakes.

To allow registration and use of closure-based route, add the `$closureRoute` attribute to `app/Providers/RouteServiceProvider.php` and set its value to `true`:

```php
protected $closureRoute = true;
```

- The value of the `$closureRoute` attribute must be a **boolean value (`true` or `false`)**.

- The `$closureRoute` attribute **default value is `false`**.

- When the value type of the `$closureRoute` attribute is wrong, the default value will be used.

- When the value of `$closureRoute` is `true`, it means that registration is allowed to use closure-based route.

- When the value of `$closureRoute` is `false`, it means that registration and use of closure-based route is prohibited.

### Set whether the name of the named route allows duplicate names

**Name requirements for named routes are unique by default.**

**Note: The name of a named route should not end with `.` (English period). In laravel, ending with `.` will be considered as a route name prefix, not a full name.**

Why the name of named routes need to be unique:

- In some scenarios where named routes are used to control permissions or generate URLs, named routes with the same name can cause business confusion.

- In some scenarios where the route name is required to be unique, the developer may not even realize that the route name is repeated when defining the route without double-checking.

- If the name of the named route allows duplicates. Then the URL of the named route generated using the `route()` method may not be the URL you want.

To allow duplicate names for named routes, add the `$uniqueRouteName` attribute to `app/Providers/RouteServiceProvider.php` and set its value to `true`:

```php
protected $uniqueRouteName = false;
```

- The value of the `$uniqueRouteName` attribute must be a **boolean value (`true` or `false`)**.

- The value of the `$uniqueRouteName` attribute **default value is `false`**.

- When the value type of the `$uniqueRouteName` attribute is wrong, the default value will be used.

- When the value of the `$uniqueRouteName` attribute is `true`, it means that the name of the named route must be unique.

- When the value of the `$uniqueRouteName` attribute is `false`, it means that the named route name is allowed to be duplicated.

After banning named routes with the same name, if there are named routes with the same name in all routes, an error message will be thrown. The extension package also indicates the route file path and line where the named route with the same name is located, as well as the middleware group to which it belongs, in order to quickly locate the problem.

### Set whether the controller can be reused

**Controllers are allowed to be reused by default.**

Why prohibit controller reuse:

- Normally, a controller method corresponds to a business logic. The use of the same controller for multiple routes means that this service can be accessed through multiple URLs, which is unfriendly to the management and maintenance of URLs, and can easily cause leaks in some URL-based applications for permission management.

- If reuse of the controller is allowed. Then the URL of the controller generated using the `action()` method may not be the URL you want.

If you need to prevent controller reuse, add the `$allowReuseAction` property to `app/Providers/RouteServiceProvider.php` and set its value to `false`:

```php
protected $allowReuseAction = false;
```

- The value of the `$allowReuseAction` attribute must be a **boolean value (`true` or `false`)**.

- When the `$allowReuseAction` attribute **default value is `true`**.

- When the value type of the `$allowReuseAction` attribute is wrong, the default value will be used.

- When the value of the `$allowReuseAction` attribute is `true`, the controller is allowed to be reused.

- When the value of the `$allowReuseAction` property is `false`, it means that the reuse of the controller is prohibited.

After the controller is prohibited from being reused, it will be checked in all routes whether the controller is reused. If it is reused, an error message will be thrown. The extension package also indicates the route file path and line where the route of the reused controller is located, as well as the middleware group to which it belongs, in order to quickly locate the problem.

### Check for duplicate definition URLs

**This feature is mandatory and does not provide a switch option.**

In Laravel, if you define the same URL, the later routes will overwrite the existing ones. Duplicate definitions are not what we expect, because it makes routing messy, difficult to maintain, and difficult to manage.

After installing the extension package, the extension package will check for duplicate URLs in all routes. If it exists, an exception message will be thrown. The extension package will also indicate the routing file path and line where the duplicate URL is located, as well as the middleware group to which it is located, in order to quickly locate the problem.

> URL refers to the full URL with domain name restrictions.

### Default rules

#### Default routing file matching rules

After installing the extension package, the corresponding routing file is automatically matched for each middleware group that is allowed to match the routing file. These routing files can be placed anywhere in the `routes` directory.

**Default matching rules:**

- Name it `{middlewareGroupName}.php`.

- Start with `{middlewareGroupName}_`.

- End with `_{middlewareGroupName}.php`.

Route files names that meet the above rules will be assigned to the middleware group with corresponding names.

**Note: Each route file in the `routes` directory can only be loaded once.**

**Note: In the default rules, the path or file name is not case sensitive.**

For example:

```php
routes
   |-- web.php
   |-- api.php
   |-- web_errorTest_api.php
   |-- channels.php
   |-- console.php
   |-- welcome_web.PHP
   |-- welcome_api.PHP
   |-- web_User.php
   |-- api_User.php
   |-- Role
        |-- role_WEB.php
        |-- role_API.php
```

- `web.php`, `welcome_web.PHP`, `web_User.php`, `Role/role_WEB.php` these routing files will be assigned to the `web` middleware group.

- `api.php`, `welcome_api.PHP`, `api_User.php`, `Role/role_API.php` these routing files will be assigned to the `api` middleware group.

- When loading the `web_errorTest_api.php` routing file, the extension package will throw an error because the file is assigned to both the `web` and `api` middleware groups (loaded 2 times). Developers should be aware of this when naming routing files with default rules.

#### The root namespace used by default

The root namespace used by all middleware groups that are allowed to match routing files defaults to the value of the attribute `$namespace` in `App\Providers\RouteServiceProvider`. Normally, this value is `App\Http\Controllers`.

#### The subdomain used by default

The subdomain name used by all middleware groups that are allowed to match routing files are empty by default.

#### The routing prefix used by default

- The `prefix` for the `web` middleware group is empty by default.

- The `prefix` for other middleware groups that are allowed to match routing files uses the name of the middleware group by default.

E.g:

By default, the `prefix` of the `web` middleware group is empty; the `prefix` of the `api` middleware group uses the name `api` of the middleware group. And so on.

#### The route name prefix used by default

The route name prefix `name` used by all middleware groups that are allowed to match routing files are empty by default.

#### Regular expression constraints for routing parameters used by default

The routing parameter regular expression constraints `where` used by all middleware groups that are allowed to match routing files are empty by default.

#### Default exception message response format

**Before using this function, manually add a function to handle the output format of exception information in the `render` method of `App\Exceptions\Handler` class, otherwise this function is invalid.**

- **The response format of exception messages of the `api` middleware group is Json by default.**

- The exception message response format of other middleware groups that are allowed to match the routing file is **determined by the global default exception message response format setting**.

### Custom rules

**Custom rules will override the default rules, and uncustomized ones will continue to use the default rules.**

**Tip: when setting custom rules, if there is an error in setting, an exception will be thrown, and the response format of the exception information will be determined by the default rule.**

Custom rules are only valid for middleware groups that are allowed to match routing files. If a middleware group is not allowed to match routing files, even the rules are not useful.

To set custom rules, add the method to `app/Providers/RouteServiceProvider.php`:

```php
protected function customMiddlewareGroupsConfig()
{
    return array(
        '{middlewareGroupName}' => array(
            'namespace' => '',
            'domain' => '',
            'prefix' => '',
            'name' => '',
            'where' => [],
            'eJsonResponse' => false,
            'matchRule' => function ($fileRelativePath) {
                // your code ...
                // The return value must be a boolean.
                return false;
            }
        )

        // ...
    );
}
```

The method return a two-dimensional array. The one-dimensional key `{middlewareGroupName}` is the name of the middleware group that needs to be customized.

The value of `{middlewareGroupName}` is also an array. The configurable keys are `namespace`, `domain`, `prefix`, `name`, `where`, `eJsonResponse`, and `matchRule`.

#### Customize root namespace used by middleware group

**Reminder: If the middleware group customizes the root namespace and does not have the same value as the property `$namespace` in `App\Providers\RouteServiceProvider`. Then, when using methods such as `action()`, `redirectToAction()`, etc., where the incoming parameter is a controller string, the controller string with the full namespace starting with `\` should be passed when the incoming parameter belongs to the controller under that middleware group.**

**Recommendation: Regardless of whether the root namespace is customizable or not, the controller string with the full namespace starting with `\` should be passed when using methods with the controller string as an incoming parameter such as `action()`, `redirectToAction()`.**

> e.g.
> 
> The `Welcome@index` controller belongs to the `web` middleware group and uses the default root namespace `App\Http\Controllers`.
>
> Before customizing the root namespace of the `web` middleware group, when using the `action()` method, you can call: `action('Welcome@index');` or `action('\App\Http\Controllers\Welcome@index');`.
>
> After customizing the root namespace, you need to call: `action('\Custom\App\Http\Controllers\Welcome@index');`.

If you need to customize the root namespace used by the middleware group, set the `namespace` key-value pair under the configuration array of the middleware group.

- The value of `namespace` must be a **string or `null`**.

- If the value type of the `namespace` is wrong, an exception will be thrown.

Since the root namespace has a default value (`App\Http\Controllers`), if you do not need to customize the root namespace of the middleware group, please do not set this item's key-value pair under the configuration array of the middleware group, otherwise the default value will be overwritten.

#### Customize subdomains used by middleware group

To customize the subdomain restrictions used by the middleware group, set the `domain` key-value pair under the configuration array of that middleware group.

- The value of `domain` must be a **string or `null`**.

- If the value type of the `domain` is wrong, an exception will be thrown.

If you do not need to customize the subdomain restrictions of the middleware group, do not set the key-value pairs for this item under the configuration array of that middleware group, otherwise the default value will be overwritten.

#### Customize routing prefixe used by middleware group

To customize the routing prefix used by the middleware group, set the `prefix` key-value pair under the configuration array of that middleware group.

- The value of `prefix` must be a **string or `null`**.

- If the value type of the `prefix` is wrong, an exception will be thrown.

If you do not need to customize the routing prefix of the middleware group, do not set the key-value pairs for this item under the configuration array of that middleware group, otherwise the default value will be overwritten.

#### Customize route name prefix used by middleware group

To customize the route name prefix used by the middleware group, set the `name` key-value pair under the configuration array of that middleware group.

- The value of `name` must be a **string or `null`**.

- If the value type of the `name` is wrong, an exception will be thrown.

If you do not need to customize the route name prefix of the middleware group, do not set the key-value pair for this item under the configuration array of that middleware group, otherwise the default value will be overwritten.

#### Customize regular expression constraints for routing parameters used by middleware group

To customize the routing parameter regular expression constraint used by the middleware group, set the `where` key value pair under the configuration array of that middleware group.

- The value of `where` must be an **array or `null`**.

- If the value type of the `where` is wrong, an exception will be thrown.

- How to properly set regular expression constraints for routing parameters, see [Laravel Doc - Routing - Regular Expression Constraints](https://laravel.com/docs/7.x/routing#parameters-regular-expression-constraints).

If you do not need to customize the routing parameter regular expression constraint of the middleware group, do not set the key-value pairs for this item under the configuration array of that middleware group, otherwise the default value will be overwritten.

#### Customize whether the exception message response format of the middleware group is Json

**Before using this function, manually add a function to handle the output format of exception information in the `render` method of `App\Exceptions\Handler` class, otherwise this function is invalid.**

If you need to customize whether the exception message response format of the middleware group is Json, please set the `eJsonResponse` key-value pair under the configuration array of the middleware group.

- The value of `eJsonResponse` must be a **boolean value (`true` or `false`)**.

- If the value type of the `eJsonResponse` is wrong, an exception will be thrown.

- When the `eJsonResponse` configuration item does not exist or has a value of `null`, the response format of the exception message is determined by the default rule and the global default setting `$defaultExceptionJsonResponse`.

- When the value of `eJsonResponse` is `true`, **when accessing a route under that middleware group**, the response to the exception message thrown is in Json format.

- When the value of `eJsonResponse` is `false`, the response format of the exception message is **determined by the `Accept` parameter of the request header when accessing a route under this middleware group**.

If you do not need to customize the exception message response format of the middleware group, do not set the key-value pairs for this item under the configuration array of that middleware group, otherwise the default value will be overwritten.

#### Custom routing file matching rules for middleware groups

If you need to customize the routing file matching rules used by the middleware group, please set the `matchRule` key-value pair under the configuration array of the middleware group.

**Note: Each routing file is only allowed to be loaded once.**

- The value of `matchRule` is a **closure**.

- The path of a routing file will be passed into the closure. This path is relative to the `routes` directory. The developer needs to encode the matching rules of the routing file path and the middleware group. If the routing file path meets the matching conditions, the closure needs return `true`, otherwise it return `false`.

    **Note: The path passed to the closure is case sensitive.**

    ```php
    //e.g.
    //Custom matching rules for web middleware groups
    'web' => array(
        'matchRule' => function ($fileRelativePath) {
            $fileRelativePath = strtolower($fileRelativePath); //Turn lowercase
            if (Str::endsWith($fileRelativePath, '_web.php')) {
                  //If the routing file ends in `_web.php`, it is assigned to the web middleware group
                  return true;
            } else {
                  return false;
            }
        }
    )
    ```

- **The return value of the closure must be a boolean (`true` or `false`).**

- An exception is thrown when the value of `matchRule` is not a closure or if the return value of a closure is not a boolean value.

If you do not need to customize the routing file matching rules of the middleware group, do not set the key-value pair for this item under the configuration array of the middleware group, otherwise the default value will be overwritten.

### Generate route cache

When the application is released to the production environment, don't forget to generate the route cache in the production environment!

input the command:

```shell
php artisan route:cache
```

This command generates the route cache file in the `bootstrap/cache` directory.

Note that route cache does not apply to closure-based route. To use route cache, all closure routes must be converted to controller classes.

If closure route is used, an error will be reported when generating the cache!

In addition, if you add any new routes, you must generate a new route cache!

If you want to remove the cache route file, you can use the command:

```shell
php artisan route:clear
```

## License

The extension package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
