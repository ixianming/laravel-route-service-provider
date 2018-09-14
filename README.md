# Laravel RouteServiceProvider

- [中文文档](/README-zh.md)

## Install

### Installation conditions：

- Laravel >= 5.3

### Install：

```shell
composer require ixianming/laravel-route-service-provider
```

#### Use Package Auto-Discovery

- Laravel 5.5+ uses Package Auto-Discovery, so doesn't require you to manually add the ServiceProvider.

- You don't need to comment on Laravel's routing service provider (unless the extension package throws a prompt).

#### Don't Use Package Auto-Discovery

If the Laravel version is less than 5.5 or don't use package auto-discovery:

- Add the extension's service provider `Ixianming\Routing\RouteServiceProvider::class` to the `providers` array in `config/app.php`.

- Comment Laravel's routing service provider `App\Providers\RouteServiceProvider::class` in the `providers` array in `config/app.php`.

```php
'providers' => [
    // ...
    
    // App\Providers\RouteServiceProvider::class,
    
    Ixianming\Routing\RouteServiceProvider::class,
    
 ]
```

> Note：
>
> After uninstalling the extension package, remember to remove `Ixianming\Routing\RouteServiceProvider::class` from the `providers` array in `config/app.php` and uncomment the `App\Providers\RouteServiceProvider::class`.


## About The Package

The extension package's `RouteServiceProvider` extends Laravel's `App\Providers\RouteServiceProvider`. Therefore, after installing the extension package, you can still explicitly bind, filter, customize the parsing logic, etc. of the routing model defined in the `boot()` method in `App\Providers\RouteServiceProvider`. However, it should be noted that the modification of the three methods `map()`, `mapApiRoutes()`, `mapWebRoutes()` in `App\Providers\RouteServiceProvider` is invalid because the extension wraps the `map ()` method, and the `mapApiRoutes()`, `mapWebRoutes()` methods are no longer referenced in the `map()` method.

### Expansion pack function

- The extension package gets the set of basic middleware group and then automatically assigns the corresponding routing file to each basic middleware group and performs the load using default rules or custom rules.

- Multiple routing files can be created for the same basic middleware group and can be placed anywhere in the `routes` directory. Developers can use the default matching rules or customize the matching rules between individual basic middleware groups and routing files.

- You can set the global exception information response format to Json format, or you can independently set the response format of the exception information of each basic middleware group to Json format.

- You can customize the `domain` used by the basic middleware group.

- You can customize the `prefix` used by the basic middleware group.

- You can customize the routing name prefix for the basic middleware group.

- Automatically check for duplicate URLs in all routes (A duplicate URL is the full URL that contains the domain name), and if they do, an error message will be thrown. The extension packages also indicate the routing file address of the duplicate URL, or the basic middleware group to which the duplicate URL belongs, to facilitate rapid positioning of the problem.

- Automatically check all routings for named routes with the same name. If they exist, an error message will be thrown. The extension packages also indicate the routing file address of the duplicate named routes, or the basic middleware group to which the duplicate named routes belongs, to facilitate rapid positioning of the problem.

> Reminder: The expansion pack don't check if the controller is reused. So, same as before installing this extension, if you reuse the controller, the URL of the controller generated using the `action()` method might not be the URL you want.

### About basic middleware group

Whether you read this section don't affect the use of the expansion pack.

The basic middleware group has default values, so you can use it after installing the expansion pack, no configuration is required.

To learn more, please continue reading this section.

The middleware group is defined in the `$middlewareGroups` property of `app/HTTP/Kernel.php`. Laravel comes with two middleware groups `web` and `api`, which developers can add or modify as needed. Middleware groups make it more convenient to assign many middleware to a route at once

The basic middleware group is a concept of this extension package: a middleware group that allows automatic matching of routing files is called a basic middleware group.

So the only purpose of defining a basic middleware group is to automatically match the routing file to the specified middleware group, rather than automatically matching the routing files to all middleware groups in general.

For example, the developer defines a middleware group for the convenience of reference, rather than matching the routing files like the `web` and `api` middleware groups. At this time, you need to define the basic middleware group to tell the extension package which middleware groups need to automatically match the routing file.

The extension package defaults to the `web` and `api` middleware groups as the basic middleware group.

To add a basic middleware group, add the `$baseMiddlewareGroups` attribute to `app/Providers/RouteServiceProvider.php`:

```php
protected $baseMiddlewareGroups = ['custom1', custom2'];
```

- `$baseMiddlewareGroups` 属性的值需为数组，数组的值为中间件组的名称。

- The default value of the `$baseMiddlewareGroups` property is: `$baseMiddlewareGroups = ['web', 'api'];`.

- The value of the defined `$baseMiddlewareGroups` property will eventually be merged with the default value, so just define the new basic middleware group in the `$baseMiddlewareGroups` property.
    
- When the value of the `$baseMiddlewareGroups` property is not empty, if the value is of the wrong type, the default value will be used.

#### FAQs

- `web`, `api`, `custom1`, `custom2` are middleware groups, `web`, `api`, `custom1` are basic middleware groups. Can I reference the `custom1` middleware group in the route under the `web` basic middleware group?

    can. The basic middleware group is essentially a middleware group, so it is no problem to reference the `custom1` middleware group in the route under the `web` basic middleware group, even if the referenced middleware group is defined as the basic middleware group.
    
    The sole role of the basic middleware group is to automatically match the corresponding routing file to this middleware group.
    
    In this example, you may be curious as to how to match the routing file. Both `web` and `custom1` are basic middleware groups, so the extension package will match the routes for the `web` and `custom1` middleware groups respectively. Although the route under the `web` basic middleware group references the `custom1` middleware group, these routes belong to the `web` middleware group.

### Default route matching rule

After the expansion pack is installed, the corresponding routing file is automatically matched for each basic middleware group. These routing files can be placed anywhere in the `routes` directory.

Default route matching rule：

A routing file named with `{basic middleware group name}.php`, or the file name begins with `{basic middleware group name}_`, or the file name ends with `_{basic middleware group name}.php`. The routing file will be assigned to the basic middleware group of the corresponding name.

> Note：Not case sensitive.

E.g：

```php
routes
   |-- web.php
   |-- api.php
   |-- channels.php
   |-- console.php
   |-- welcome_web.php
   |-- welcome_api.php
   |-- web_User.php
   |-- api_User.php
   |-- Role
        |-- role_WEB.php
        |-- role_API.php
```

- `web.php`, `welcome_web.php`, `web_User.php`, `Role/role_WEB.php` These routing files will be assigned the `web` middleware group.

- `api.php`、`welcome_api.php`、`api_User.php`、`Role/role_API.php` These routing files will be assigned the `api` middleware group.

### Default route prefix

- The `prefix` of the `web` basic middleware group is empty by default.

- The `prefix` of other basic middleware groups uses the name of the basic middleware group by default.

E.g：

By default, the `prefix` of the `web` basic middleware group is empty; the `prefix` of the `api` basic middleware group uses the name `api` of the basic middleware group. And so on.

### Default route name prefix

The `name` of all basic middleware groups is empty by default, that is, the route name prefix is not set.

### `domain` used by default

The `domain` of all basic middleware groups is empty by default, which is the subdomain that does not restrict access.

### The response format of the default exception information

- The response format of the exception information for the `api` basic middleware group defaults to Json.

- The response format of the exception information of other basic middleware groups is the same as before the expansion package is installed.

### Set the response format of the exception information to Json format

We all know that when Laravel throws an exception, it displays the Whoops page. The page content is good, but it is not so friendly for API applications.

In most cases, we want the API application to respond to Json data instead of a page when it encounters an exception.

After installing the extension package, you can set the response format of the global exception information to Json format, or you can set it separately for each basic middleware group.

#### Set the global exception information response format to Json format

Add the `$eJsonResponse` attribute to `app/Providers/RouteServiceProvider.php` and set its value to `true`:

```php
protected $eJsonResponse = true;
```

- The value of the `$eJsonResponse` property needs to be a Boolean value (`true` or `false`).

- The value of the `$eJsonResponse` property defaults to `false`.
    
- When the value of the `$eJsonResponse` attribute is not empty, if the value is of the wrong type, the default value will be used.

- When the value of the `$eJsonResponse` attribute is `true`, the response format of the exception information is Json format regardless of which route is accessed under the basic middleware group.

- If the `$eJsonResponse` attribute does not exist or the value of the `$eJsonResponse` attribute is `false`, the response format of the current exception information is determined according to the setting of the exception information response format of the basic middleware group to which the current route belongs.

    For example, the value of the `$eJsonResponse` attribute is `false`, and the setting of the exception information response format of the basic middleware group uses the default value. Then, when accessing the `web` route, the response format of the exception information is the same as before the extension package is installed. When the `api` route is accessed, the response format of the exception information is Json.

> Note:
> 
> The global exception information response format is set when the service provider of the extension package is registered. That is, if the application throws an exception before registering the service provider of the extension package,the response format of the exception information is consistent  same as  before installing the expansion pack.

#### Independently set the basic middleware group to respond to Json format

Sometimes you may encounter a situation where you have both a web page and an api interface in your Laravel app. In general, we want the web page to respond to the Whoops page when it encounters an exception. The api interface responds to Json data when it encounters an exception, rather than responding to the Whoops page or Json data in general.

Now, after the expansion pack is installed, you can implement this feature.

How to set the exception information response to Json format for each basic middleware group, see the Custom Rules section.

> Note:
>
> The exception information response format of the basic middleware group is set after all service providers have booted. That is to say, no matter whether the response format of the exception information set for the currently accessed route is Json, if the application throws an exception during the service provider registration and startup phase, the response format of the exception information will be the same as before the installation of the extension package.

If you want to respond to the service provider registration, the exception information thrown in the startup phase is Json format, The extension package provides an option to prioritize exception information to Json format. After turning this option on, If an exception is thrown after the service provider of the extension package is registered and before all service providers start up, the response format for the exception information will be in Json format. After all the service providers have started, the exception response formats of the basic middleware groups are used.

To set the priority response exception information to Json format, add the `$ePriorityJson` attribute to `app/Providers/RouteServiceProvider.php` and set its value to `true`:

```php
protected $ePriorityJson = true;
```

- The value of the `$ePriorityJson` attribute needs to be a Boolean value (`true` or `false`).

- The value of the `$ePriorityJson` attribute defaults to `false`.
    
- When the value of the `$ePriorityJson` attribute is not empty, if the value is of the wrong type, the default value will be used.

- When the value of the `$ePriorityJson` attribute is `true`. If an exception is thrown before all service providers start up after the service provider of the extended package is registered, the response format for the exception information will be in Json format. After all service providers are started, the exception information response format settings for each of the basic middleware groups are used.

> Note:
>
> Global settings are prioritized for independent settings. That is, after the Json response with global exception information is set, the independent settings of the basic middleware group and the option value of the priority response to Json are invalid until the Json response with global exception information is cancelled.

### Custom Rules

Custom rules override the default rules, and uncustomized sections will continue to use the default rules.

To set a custom rule, add a method to `app/Providers/RouteServiceProvider.php`:

```php
protected function customMiddlewareGroupsRules(){
    $customRules = array(
        '{baseMiddlewareGroupName}' => array(
            'domain' => '',
            'prefix' => '',
            'name' => '',
            'eJsonResponse' => false,
            'matchRules' => function($routeFilePath){
                // your code
                // The boolean value must be returned
            }
        )
      
        // ...
    );

    return $customRules;
}
```
    
In the `$customRules` two-dimensional array, the one-dimensional key `{baseMiddlewareGroupName}` is the name of the basic middleware group that needs to customize the rules.

The value of `{baseMiddlewareGroupName}` is also an array, and its configurable keys are `domain`, `prefix`, `name`, `eJsonResponse`, `matchRules`. You can configure `domain` for the middleware group, `prefix` for the middleware group, the routing name prefix for the middleware group, The response format of the exception information of the basic middleware group, and the matching rules for the middleware group and the routing file.

- To customize the `domain` of the middleware group, set the `domain` key-value pair under the array of the middleware group.

    - The value of `domain` needs to be a string.
    
    - When the value of `domain` is not empty, an exception will be thrown if the value of the value is incorrect.

- To customize the `prefix` of the basic middleware group, set the `prefix` key-value pair under the array of the basic middleware group.

    - The value of `prefix` needs to be a string.
    
    - When the value of `prefix` is not empty, an exception will be thrown if the value of the value is incorrect.
    
- To customize the routing name prefix for the basic middleware group, set the `name` key-value pair under the array of the basic middleware group.

    - The value of `name` needs to be a string.
    
    - The value of `name` is unique among all basic middleware groups.

    - When the value of `name` is not empty, an exception will be thrown if the value of the value is incorrect or the value of `name` is not unique.

- To set the exception information response format of the basic middleware group to Json format, set the `eJsonResponse` key-value pair under the array of the basic middleware group.

    - The value of `eJsonResponse` needs to be a Boolean value (`true` or `false`).

    - When the value of `eJsonResponse` is `true`, the response format of the exception information of the route under the basic middleware group is Json format.

    - When the value of `eJsonResponse` is not empty, if the value is of the wrong type, an exception will be thrown.
    
- To customize the matching rules for basic middleware groups and routing files, set the `matchRules` key-value pair under the array of the basic middleware group.

    - The value of `matchRules` is an anonymous function.
    
    - The anonymous function passes the path of a routing file. This path is relative to the `routes` directory (Case sensitive). The developer needs to code to determine whether the routing file matches the basic middleware group and then return a Boolean value.
    
        > Note: The path/filename of the default rule is case-insensitive, and the path/filename passed to the anonymous function is case sensitive.
    
    - When the value of `matchRules` is not empty, an exception will be thrown if the value is not an anonymous function or if the return value of the anonymous function is not a Boolean value.

#### FAQs about custom rules

- Is the `domain` configured in the basic middleware group, can you also create subdomain routing in the matching routing file? Which domain name is used for subdomain routing?

    You can create a subdomain routing. The `domain` of the basic middleware group configuration can be understood as the default domain name for accessing this basic middleware group. If a subdomain routing group is created in the routing file, the route in the subdomain routing group will be accessed through the newly configured `domain`. Routes not included in the subdomain routing group continue to use the original configured `domain`.
    
- The name of the named route is determined to be unique, but why does the extension package still prompt the named route name to be duplicated?

    If you confirm that the name of the named route is unique in all routing files, you must have used a custom matching rule, and you must match the same routing file to multiple basic middleware groups. If you've read the code of the Laravel routing service provider, you'll see that the routes you define are actually included in the routing group of the middleware group. When you assign the same route to multiple middleware groups, actually Multiple final routes are generated, but these routes all use the same name, so the named route name is repeated.
    
    If you need to match the same routing file to multiple basic middleware groups, you can set a different routing name prefix for the basic middleware group, or delete the `name` attribute of the route in the routing file.

### Repeated route check

In Laravel, if you define the same URL or a named route with the same name, the last defined route will overwrite the existing route. Duplicate definitions are not what we expect because they can make routing confusing and difficult to maintain management.

The extension will automatically check for duplicate URLs or named routes of the same name in all routes, and if so, will throw an error message. (A duplicate URL is the full URL that contains the domain name)

### Route cache

When the app is published to the build environment, don't forget to generate a route cache in the production environment!

input the command：

```shell
php artisan route:cache
```

This command will generate the `bootstrap/cache/routes.php` route cache file.

It should be noted that the route cache does not act on closure-based routes. To use route caching, all closure routes must be converted to controller classes.

If a closure route is used, an error will be reported when the cache is generated!

If you add any new routes, you must generate a new route cache!

If you want to remove the cached routing file, you can use the command:

```shell
php artisan route:clear
```

## License

The extension package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
