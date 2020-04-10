# Laravel RouteServiceProvider

文档详细说明了扩展包的功能，内容较多，但并不复杂。

通常情况下，默认设置可以满足绝大多数的需求，所以安装扩展包后可以无需设置，开箱即用，熟悉一下默认规则即可。

- [English Document](/README.md)

[TOC]

## 关于扩展包

扩展包的 `ServiceProvider` 继承了 Laravel 的 `App\Providers\RouteServiceProvider`。因此，安装扩展包后，依旧可以在 `App\Providers\RouteServiceProvider` 中的 `boot()` 方法中定义的路由模型的显式绑定、过滤器、自定义解析逻辑等。

但需要注意的是，对 `App\Providers\RouteServiceProvider` 中 `map()`，`mapApiRoutes()`，`mapWebRoutes()` 这三个方法的修改是无效的，因为扩展包覆写了 `map()` 方法且 `map()` 方法中不再引用 `mapApiRoutes()`，`mapWebRoutes()` 这两个方法。

## 扩展包功能

- 可以设置允许匹配路由文件的中间件组。

    > 扩展包会使用默认或自定义规则为这些中间件组分配对应的路由文件，并执行加载。

- 可以为同一中间件组创建多个路由文件，并且可以将这些路由文件放在 `routes` 目录下的任何一个地方。

    > 路由文件的名称可以使用默认规则，也可以由开发者自定义路由文件与中间件组的匹配规则。

- 可以将异常信息的响应格式设置为全局默认 Json 输出 ，也可以为各个允许匹配路由文件的中间件组独立设置其异常信息的响应格式是否为 Json。（独立设置优先级高于全局默认设置）

- 在全部路由中检查是否存在重复的 URL（含域名限制的完整的 URL）。

- 可以设置是否允许注册闭包路由。

- 可以设置命名路由的名称是否允许重名。

- 可以设置是否允许重复使用控制器。

- 可以自定义各个允许匹配路由文件的中间件组所使用的根命名空间 `namespace`。

- 可以自定义各个允许匹配路由文件的中间件组所使用的子域名限制 `domain`。

- 可以自定义各个允许匹配路由文件的中间件组所使用的路由前缀 `prefix`。

- 可以自定义各个允许匹配路由文件的中间件组所使用的路由名称前缀 `name`。

- 可以自定义各个允许匹配路由文件的中间件组所使用的路由参数正则表达式约束 `where`。

## 对性能的影响

安装并使用任何扩展包必然会带来额外的性能开销。安装使用本扩展包亦是。

**总体而言，本扩展包带来的额外性能开销是合理的，且可以忽略不计**。但如对应用性能有极致要求，请自行进行测试、评估后再酌情使用。

自行测试时，请务必确认以下要点：

- 确保唯一的变量为：是否安装本扩展包。

- **确保安装扩展包前后，应用加载的路由文件数量和路由数量相同。**

### 使用路由缓存

安装扩展包后，任何环境下使用 `php artisan route:cache` 命令生成路由缓存后，与安装扩展包前使用路由缓存时对比：

- 增加内存消耗（以下数值在 PHP 7.3 环境下测得，仅作参考）：

    - Laravel 7：
    
        理论上，使用路由缓存后，仅会增加加载扩展包服务提供者时的内存消耗（约 55 KB）。但在实际测试中，新增的内存消耗会随路由个数的增加而变大，路由个数超过约 230 个时，新增内存消耗会出现明显波动：
        
        - 路由个数在约 230 个以内时，内存消耗较安装扩展包前增加约（约 55 +（约 0.04 * 路由个数））KB。
        
        - 路由个数在约 230 - 300 个时，内存消耗较安装扩展包前增加约（约 55 +（约 0.2 * 路由个数））KB。
        
        - 路由个数超过约 300 个时，内存消耗较安装扩展包前增加约（约 55 +（约 0.3 * 路由个数））KB。
    
    - Laravel 5.* / Laravel 6：
    
        - 路由个数在约 300 个以内时，内存消耗较安装扩展包前增加约（约 100 +（约 0.6 * 路由个数））KB。
      
        - 路由个数超过约 300 个时，内存消耗较安装扩展包前增加约（约 100 +（约 0.9 * 路由个数））KB。
    
    测试还发现，在不安装本扩展包的情况下，使用路由缓存后，Laravel 7 路由个数超过约 230 个，Laravel 5.* / Laravel 6 路由个数超过约 300 个时，内存消耗也会出现明显波动：
        
    - 未安装扩展包、使用路由缓存、路由个数在约 230（Laravel 7）/ 300（Laravel 5.* / Laravel 6）个以内时，每个路由的内存占用约 4.5 KB。
    
    - 未安装扩展包、使用路由缓存、路由个数超过约 230（Laravel 7）/ 300（Laravel 5.* / Laravel 6）个时，每个路由的内存占用约 9 KB。
    
    在我看来，产生上述内存差异的原因和 PHP7 数组的底层实现有关（自动扩容机制）。PHP7 数组的具体底层实现请自行查找资料。如果你对此有不同的看法，欢迎交流，共同学习。
    
    总的来说，即使你的应用是有着近千路由的大型应用，新增的内存消耗也仅约 300 KB，这几乎是可以忽略的。
    
- 增加 I/O 开销：

    - Laravel 7：
    
        - 正常响应的情况下，增加一次 IO 读取（约 15 KB）。
        
        - 抛出异常的情况下，增加两次 IO 读取（共约 20 KB）。

    - Laravel 5.* / Laravel 6：

        - 正常响应的情况下，增加两次 IO 读取（共约 30 KB）。
    
        - 抛出异常的情况下，增加三次 IO 读取（共约 35 KB）。
        
其他开销与安装扩展包前一致。

### 不使用路由缓存

不使用路由缓存的情况下，额外性能开销主要是 `RouteServiceProvider`、`RouteCollection` 及 `Route` 实例的内存开销，和路由文件与中间件组的匹配运算开销。

安装扩展包后，与安装扩展包前对比：

- 增加可忽略不计的 CPU 运算开销。

- 内存消耗较安装扩展包前增加约（约 110 +（约 1.5 * 路由个数））KB。（该数值在 PHP 7.3 环境下测得，仅作参考）

- 增加 I/O 开销：

    - 正常响应的情况下，增加两次 IO 读取（共约 30 KB）。
    
    - 抛出异常的情况下，增加三次 IO 读取（共约 35 KB）。

其他开销与安装扩展包前一致。

## 安装

### 安装条件

- PHP >= 7.0

- Laravel >= 5.3

    > Laravel 6、Laravel 7 可以安装使用噢！

### 安装

```shell
composer require ixianming/laravel-route-service-provider
```

#### 使用包自动发现

- Laravel 5.5+ 使用包自动发现，所以不需要手动添加 `ServiceProvider`。

- 不需要注释 Laravel 的路由服务提供者（除非扩展包抛出提示）。

#### 不使用包自动发现

如果 Laravel 版本小于 5.5 或者不使用包自动发现：

- 在 `config/app.php` 的 `providers` 数组中注释 Laravel 的路由服务提供者 `App\Providers\RouteServiceProvider::class`。

- 将扩展包的服务提供者 `Ixianming\Routing\RouteServiceProvider::class` 添加到 `config/app.php` 的 `providers` 数组中、原路由服务提供者 `App\Providers\RouteServiceProvider::class` 的下方。

```php
'providers' => [
    /*
     * Laravel Framework Service Providers...
     */
    
    /*
     * Package Service Providers...
     */
    
    /*
     * Application Service Providers...
     */
     
    // App\Providers\RouteServiceProvider::class,
    Ixianming\Routing\RouteServiceProvider::class,
    
 ]
```

### 手动添加处理异常信息输出格式的函数

在 `App\Exceptions\Handler` 类的 `render` 方法的 `return` 前添加代码：

```php
if (method_exists(\Ixianming\Routing\ExceptionResponse::class, 'wantsJson')) {
    list($request, $exception) = \Ixianming\Routing\ExceptionResponse::wantsJson($request, $exception);
}
```

修改后，`render` 方法应该是这样的：

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

### 卸载注意事项

- 卸载扩展包后，记得将 `App\Exceptions\Handler` 类的 `render` 方法中，添加的处理异常信息输出格式的代码移除。

- 卸载扩展包后，记得将 `App\Providers\RouteServiceProvider` 中添加的本扩展包使用的属性和方法的代码移除。

- 卸载扩展包后，如果 Laravel 版本小于 5.5 或者未使用包自动发现，记得在 `config/app.php` 中的 `providers` 数组里移除 `Ixianming\Routing\RouteServiceProvider::class` 并取消 `App\Providers\RouteServiceProvider::class` 的注释。

## 使用

### 设置允许匹配路由文件的中间件组

**扩展包默认 `web` 和 `api` 中间件组可以匹配路由文件。**

如需新增允许匹配路由文件的中间件组，在 `app/Providers/RouteServiceProvider.php` 中添加 `$allowMatchRouteMiddlewareGroups` 属性：

```php
protected $allowMatchRouteMiddlewareGroups = ['middlewareGroup_1', 'middlewareGroup_2'];
```

- `$allowMatchRouteMiddlewareGroups` 属性的值为一维数组，数组的值为允许匹配路由文件的中间件组的名称。

- 定义的 `$allowMatchRouteMiddlewareGroups` 属性的值会与默认值合并，所以只需在 `$allowMatchRouteMiddlewareGroups` 属性中定义新增的允许匹配路由文件的中间件组即可。

- `$allowMatchRouteMiddlewareGroups` 属性的值类型错误时，将使用默认值。

### 设置全局默认的异常信息的响应格式是否为 Json 格式

**使用本功能前，需在 `App\Exceptions\Handler` 类的 `render` 方法中手动添加处理异常信息输出格式的函数，否则这个功能无效。**

如需将全局默认的异常信息响应格式设置为 Json 格式，在 `app/Providers/RouteServiceProvider.php` 中添加 `$defaultExceptionJsonResponse` 属性，并将其值设为 `true`：

```php
protected $defaultExceptionJsonResponse = true;
```

- `$defaultExceptionJsonResponse` 属性的值需为**布尔值（`true` 或 `false`）**。

- `$defaultExceptionJsonResponse` 属性的**默认值为 `false`**。

- `$defaultExceptionJsonResponse` 属性的值设置错误时，将使用默认值。

- `$defaultExceptionJsonResponse` 属性的值为 `true` 时，**访问未知路由**或**访问未自定义异常信息响应格式的中间件组下的路由**时，应用抛出的异常信息的响应格式为 Json 格式。

- `$defaultExceptionJsonResponse` 属性不存在或值为 `null` 或值为 `false` 时，**访问未知路由**或**访问未自定义异常信息响应格式的中间件组下的路由**时，应用抛出的异常信息的响应格式由请求头 `Accept` 参数决定。

- 访问**已自定义异常信息响应格式的中间件组下的路由**时，无论 `$defaultExceptionJsonResponse` 属性的值如何设置，应用抛出的异常信息的响应格式由该中间件组的自定义设置决定。（自定义规则的详情见后文）

### 设置是否允许注册闭包路由

**默认情况下，扩展包禁止注册、使用闭包路由。**

> 为什么要禁止注册、使用闭包路由：
> 
> - 应用上线时，通常会对 Laravel 进行优化，路由缓存是优化项之一。但路由缓存并不会作用在基于闭包的路由。如果使用了闭包路由，在生成缓存时，则会报错！为了避免代码推上线时无法使用路由缓存，最好的解决方案就是始终禁止使用闭包路由。
>
> - 在团队开发时，通过此配置可以强行约束各开发人员在注册路由时的方式，降低失误风险。

如需允许注册使用闭包路由，在 `app/Providers/RouteServiceProvider.php` 中添加 `$closureRoute` 属性，并将其值设为 `true`：

```php
protected $closureRoute = true;
```

- `$closureRoute` 属性的值需为**布尔值（`true` 或 `false`）**。

- `$closureRoute` 属性的**默认值为 `false`**。

- `$closureRoute` 属性的值设置错误时，将使用默认值。

- `$closureRoute` 属性的值为 `true` 时，表示允许注册使用闭包路由。

- `$closureRoute` 属性的值为 `false` 时，表示禁止注册使用闭包路由。

### 设置命名路由的名称是否允许重名

**默认情况下，扩展包禁止命名路由的名称重名。**

**注意：命名路由的名称不应以 `.` （英文点号）结尾。在 laravel 中，若以 `.` 结尾，将会被认为是路由名称前缀，而非完整的命名。**

为什么需要命名路由的名称是唯一的：

- 在一些使用命名路由来控制权限或者生成 URL 的场景中，重名的命名路由会带来业务上的混乱。

- 在一些要求命名路由名字唯一的场景中，在定义路由时，如果没有重复检查，开发者甚至意识不到命名路由的名字重复了。

- 如果命名路由的名称允许重复。那么使用 `route()` 方法生成的命名路由的 URL 可能不是你想要的 URL。

如需允许命名路由的名称重名，在 `app/Providers/RouteServiceProvider.php` 中添加 `$uniqueRouteName` 属性，并将其值设为 `false`：

```php
protected $uniqueRouteName = false;
```

- `$uniqueRouteName` 属性的值需为**布尔值（`true` 或 `false`）**。

- `$uniqueRouteName` 属性的**默认值为 `true`**。

- `$uniqueRouteName` 属性的值设置错误时，将使用默认值。

- `$uniqueRouteName` 属性的值为 `true` 时，表示命名路由的名称需唯一。

- `$uniqueRouteName` 属性的值为 `false` 时，表示允许命名路由名称重复。

禁止命名路由的名称重名后，若在全部路由中存在同名的命名路由，将抛出错误信息。扩展包还会指出重名的命名路由所在的路由文件地址及所在行，以及所属的中间件组，以方便快速定位问题。

### 设置控制器能否重复使用

**默认情况下，扩展包允许重复使用控制器。**

为什么要禁止控制器重复使用：

- 通常情况下，一个控制器方法对应着一个业务逻辑。多个路由使用同一控制器意味着这一业务可以通过多个 URL 进行访问，这对 URL 的管理和维护是不友好的，而且在一些基于 URL 进行权限管控的应用中很容易造成纰漏。

- 如果允许重复使用控制器。那么使用 `action()` 方法生成的控制器的 URL 可能不是你想要的 URL。

如需禁止控制器重复使用，在 `app/Providers/RouteServiceProvider.php` 中添加 `$allowReuseAction` 属性，并将其值设为 `false`：

```php
protected $allowReuseAction = false;
```

- `$allowReuseAction` 属性的值需为**布尔值（`true` 或 `false`）**。

- `$allowReuseAction` 属性的**默认值为 `true`**。

- `$allowReuseAction` 属性的值设置错误时，将使用默认值。

- `$allowReuseAction` 属性的值为 `true` 时，表示允许重复使用控制器。

- `$allowReuseAction` 属性的值为 `false` 时，表示禁止重复使用控制器。

禁止控制器重复使用后，会在全部路由中检查是否重复使用了控制器，如果重复使用了，将抛出错误信息。扩展包还会指出重复使用控制器的路由所在的路由文件地址及所在行，以及所属的中间件组，以方便快速定位问题。

### 检查重复定义的 URL

**这项功能是强制的，不提供开关选项。**

在 Laravel 中，如果你定义了相同的 URL，后定义的路由会覆盖掉已存在的路由。重复定义并不是我们所期望的，因为这会使得路由变得混乱、不易维护、不易管理。

安装扩展包后，扩展包会在全部路由中检查是否存在重复的 URL，如果存在，将抛出异常信息。扩展包还会指出重复的 URL 所在的路由文件地址及所在行，以及所属的中间件组，以方便快速定位问题。

> URL 指包含域名限制的完整的 URL。

### 默认规则

#### 默认的路由文件匹配规则

安装扩展包后，会自动为每个允许匹配路由文件的中间件组匹配对应的路由文件。这些路由文件可以放在 `routes` 目录下的任何一个地方。

**默认匹配规则：**

- 以 `{中间件组名}.php` 命名。

- 以 `{中间件组名}_` 开头。

- 以 `_{中间件组名}.php` 结尾。

文件名符合以上规则的路由文件会被分配至对应名称的中间件组。

**注意：`routes` 目录下的每个路由文件仅允许被加载一次。**

**注意：在默认规则中，路径/文件名不区分大小写。**

例如：

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

- `web.php`、`welcome_web.PHP`、`web_User.php`、`Role/role_WEB.php` 这几个路由文件会被分配至 `web` 中间件组。

- `api.php`、`welcome_api.PHP`、`api_User.php`、`Role/role_API.php` 这几个路由文件会被分配至 `api` 中间件组。

- 加载 `web_errorTest_api.php` 路由文件时，扩展包将抛出错误，因为该文件被同时分配至 `web` 和 `api` 两个中间件组（被加载了 2 次）。开发者在使用默认规则命名路由文件时应注意这一点。

#### 中间件组默认使用的根命名空间 namespace

所有允许匹配路由文件的中间件组使用的根命名空间 `namespace` 默认为 `App\Providers\RouteServiceProvider` 中，属性 `$namespace` 的值。通常情况下，这个值为 `App\Http\Controllers`。

#### 中间件组默认使用的子域名限制 domain

所有允许匹配路由文件的中间件组使用的子域名限制 `domain` 默认为空，即不限制访问的子域名。

#### 中间件组默认使用的路由前缀 prefix

- `web` 中间件组的 `prefix` 默认为空。

- 其他允许匹配路由文件的中间件组的路由前缀 `prefix` 默认使用该中间件组的名称。

例如：

默认情况下，`web` 中间件组的 `prefix` 为空；`api` 中间件组的 `prefix` 使用该中间件组的名称 `api`。以此类推。

#### 中间件组默认使用的路由名称前缀 name

所有允许匹配路由文件的中间件组使用的路由名称前缀 `name` 默认为空，即不设置路由名称前缀。

#### 中间件组默认使用的路由参数正则表达式约束 where

所有允许匹配路由文件的中间件组使用的路由参数正则表达式约束 `where` 默认为空。

#### 中间件组默认的异常信息的响应格式

**使用本功能前，需在 `App\Exceptions\Handler` 类的 `render` 方法中手动添加处理异常信息输出格式的函数，否则这个功能无效。**

- **`api` 中间件组的异常信息的响应格式默认为 Json。**

- 其他允许匹配路由文件的中间件组的异常信息响应格式**由全局默认的异常信息响应格式的设置决定**。

### 自定义规则

**自定义的规则会覆盖默认规则，未自定义的部分则将继续使用默认规则。**

**提示：在设置自定义规则时，若设置错误会抛出异常，该异常信息的响应格式将由默认规则决定。**

自定义规则仅对允许匹配路由文件的中间件组有效，如果某中间件组未被允许匹配路由文件，即便自定义了规则也无用。

如需设置自定义规则，在 `app/Providers/RouteServiceProvider.php` 中添加如下方法：

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

在方法返回的二维数组中，一维键 `{middlewareGroupName}` 是需要自定义规则的允许匹配路由文件的中间件组的名字。

`{middlewareGroupName}` 的值也是数组，其可配置的键有 `namespace`，`domain`，`prefix`，`name`，`where`，`eJsonResponse`，`matchRule`。

#### 自定义中间件组使用的根命名空间 namespace

**提醒：如果中间件组自定义了根命名空间，且与 `App\Providers\RouteServiceProvider` 中属性 `$namespace` 的值不相同。那么，在使用 `action()`、`redirectToAction()`  等传入参数为控制器字符串的方法时，传入参数属于该中间件组下的控制器时，应传入以 `\` 开头的完整命名空间的控制器字符串。**

**建议：无论自定义根命名空间与否，在使用 `action()`、`redirectToAction()`  等传入参数为控制器字符串的方法时，均传入以 `\` 开头的完整命名空间的控制器字符串。**

> e.g.
> 
> `Welcome@index` 控制器属于 `web` 中间件组，使用默认的根命名空间 `App\Http\Controllers`。
>
> 自定义 `web` 中间件组的根命名空间前，使用 `action()` 方法时，可如此调用：`action('Welcome@index');` 或 `action('\App\Http\Controllers\Welcome@index');`。
>
> 自定义根命名空间后，需如此调用：`action('\Custom\App\Http\Controllers\Welcome@index');`。

如需自定义中间件组使用的根命名空间 `namespace`，请在该中间件组的配置数组下设置 `namespace` 键值对。

- `namespace` 的值需为**字符串或 `null`**。

- `namespace` 的值类型错误时，将抛出异常。

由于根命名空间有默认值（`App\Http\Controllers`），若无需自定义中间件组的根命名空间，请勿在该中间件组的配置数组下设置本项的键值对，否则默认值会被覆盖。

#### 自定义中间件组使用的子域名限制 domain

如需自定义中间件组使用的子域名限制 `domain`，请在该中间件组的配置数组下设置 `domain` 键值对。

- `domain` 的值需为**字符串或 `null`**。

- `domain` 的值类型错误时，将抛出异常。

若无需自定义中间件组的子域名限制，请勿在该中间件组的配置数组下设置本项的键值对，否则默认值会被覆盖。

#### 自定义中间件组使用的路由前缀 prefix

如需自定义中间件组使用的路由前缀 `prefix`，请在该中间件组的配置数组下设置 `prefix` 键值对。

- `prefix` 的值需为**字符串或 `null`**。

- `prefix` 的值类型错误时，将抛出异常。

若无需自定义中间件组的路由前缀，请勿在该中间件组的配置数组下设置本项的键值对，否则默认值会被覆盖。

#### 自定义中间件组使用的路由名称前缀 name

如需自定义中间件组使用的路由名称前缀 `name`，请在该中间件组的配置数组下设置 `name` 键值对。

- `name` 的值需为**字符串或 `null`**。

- `name` 的值类型错误时，将抛出异常。

若无需自定义中间件组的路由名称前缀，请勿在该中间件组的配置数组下设置本项的键值对，否则默认值会被覆盖。

#### 自定义中间件组使用的路由参数正则表达式约束 where

如需自定义中间件组使用的路由参数正则表达式约束 `where`，请在该中间件组的配置数组下设置 `where` 键值对。

- `where` 的值需为**数组或 `null`**。

- `where` 的值类型错误时，将抛出异常。

- 如何正确设置路由参数正则表达式约束，请参见 [Laravel 文档 - 路由 - 正则表达式约束](https://learnku.com/docs/laravel/7.x/routing/7458#parameters-regular-expression-constraints)。

若无需自定义中间件组的路由参数正则表达式约束，请勿在该中间件组的配置数组下设置本项的键值对，否则默认值会被覆盖。

#### 自定义中间件组的异常信息响应格式是否为 Json

**使用本功能前，需在 `App\Exceptions\Handler` 类的 `render` 方法中手动添加异常信息输出格式的处理函数，否则这个功能无效。**

如需自定义中间件组的异常信息响应格式是否为 Json，请在该中间件组的配置数组下设置 `eJsonResponse` 键值对。

- `eJsonResponse` 的值需为**布尔值（`true` 或 `false`）**。

- `eJsonResponse` 的值类型错误时，将抛出异常。

- `eJsonResponse` 配置项不存在或值为 `null` 时，应用抛出的异常信息的响应格式由**默认规则和全局默认设置 `$defaultExceptionJsonResponse` 决定**。

- `eJsonResponse` 的值为 `true` 时，**访问该中间件组下的路由时**，应用抛出的异常信息的响应格式为 Json 格式。

- `eJsonResponse` 的值为 `false` 时，**访问该中间件组下的路由时**，应用抛出的异常信息的响应格式由请求头 `Accept` 参数决定。

若无需自定义中间件组的异常信息响应格式，请勿在该中间件组的配置数组下设置本项的键值对，否则默认值会被覆盖。

#### 自定义中间件组的路由文件匹配规则

如需自定义中间件组与路由文件的匹配规则，请在该中间件组的配置数组下设置 `matchRule` 键值对。

**注意：每个路由文件仅允许被加载一次。**

- `matchRule` 的值是一个**匿名函数**。

- 匿名函数的传入参数是**一个路由文件的路径，这个路径是相对于 `routes` 目录的相对路径**，开发者需要自行编码**路由文件路径与该中间件组的匹配规则**。若满足匹配条件，则返回 `true`，否则返回 `false`。

    **注意：传入匿名函数的路径是区分大小写的。**

    ```php
    //e.g.
    //自定义 web 中间件组的匹配规则
    'web' => array(
        'matchRule' => function ($fileRelativePath) {
            $fileRelativePath = strtolower($fileRelativePath); //转小写
            if (Str::endsWith($fileRelativePath, '_web.php')) {
                  //如果该路由文件以 `_web.php` 结尾，则把它分配给 web 中间件组
                  return true;
            } else {
                  return false;
            }
        }
    )
    ```

- **匿名函数的返回值必须是布尔值（`true` 或 `false`）。**

- `matchRule` 的值不是匿名函数或匿名函数返回值不是布尔值时，将抛出异常。

若无需自定义中间件组与路由文件的匹配规则，请勿在该中间件组的配置数组下设置本项的键值对，否则默认值会被覆盖。

### 生成路由缓存

当应用发布到生产环境时，一定别忘了在生产环境生成路由缓存！

输入命令：

```shell
php artisan route:cache
```

上面这个命令会在 `bootstrap/cache` 目录下生成路由缓存文件。

需要注意的是，路由缓存并不会作用在基于闭包的路由。要使用路由缓存，必须将所有闭包路由转换为控制器类。

如果使用了闭包路由，在生成缓存时，将报错！

另外，如果添加了任何新的路由，都必须生成新的路由缓存噢！

如果想要移除缓存路由文件的话，可以使用命令：

```shell
php artisan route:clear
```

## 许可证

本扩展包基于 [MIT license](https://opensource.org/licenses/MIT) 开源。
