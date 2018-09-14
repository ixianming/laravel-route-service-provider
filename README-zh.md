# Laravel RouteServiceProvider

- [English](/README.md)

## 安装

### 安装条件：

- Laravel >= 5.3

### 安装：

```shell
composer require ixianming/laravel-route-service-provider
```

#### 使用包自动发现

- Laravel 5.5+ 使用包自动发现，所以不需要手动添加 `ServiceProvider`。

- 不需要注释 Laravel 的路由服务提供者（除非扩展包抛出提示）。

#### 不使用包自动发现

如果 Laravel 版本小于 5.5 或者不使用包自动发现：

- 将扩展包的服务提供者 `Ixianming\Routing\RouteServiceProvider::class` 添加到 `config/app.php` 中的 `providers` 数组中。

- 在 `config/app.php` 中的 `providers` 数组中注释 Laravel 的路由服务提供者 `App\Providers\RouteServiceProvider::class`。

```php
'providers' => [
    // ...
    
    // App\Providers\RouteServiceProvider::class,
    
    Ixianming\Routing\RouteServiceProvider::class,
    
 ]
```

> 注意：
>
> 卸载扩展包后，记得在 `config/app.php` 中的 `providers` 数组里移除 `Ixianming\Routing\RouteServiceProvider::class` 并取消 `App\Providers\RouteServiceProvider::class` 的注释。

## 关于扩展包

扩展包的 `RouteServiceProvider` 继承了 Laravel 的 `App\Providers\RouteServiceProvider`。因此，安装扩展包后，依旧可以在 `App\Providers\RouteServiceProvider` 中的 `boot()` 方法中定义的路由模型的显式绑定、过滤器、自定义解析逻辑等。但需要注意的是，对 `App\Providers\RouteServiceProvider` 中 `map()`，`mapApiRoutes()`，`mapWebRoutes()` 这三个方法的修改是无效的，因为扩展包覆写了 `map()` 方法且 `map()` 方法中不再引用 `mapApiRoutes()`，`mapWebRoutes()` 这两个方法。

### 扩展包功能

- 扩展包会获取设置的基础中间件组，然后使用默认规则或自定义规则为每个基础中间件组自动分配对应的路由文件并执行加载。

- 可以为同一基础中间件组创建多个路由文件，并且可以将这些路由文件放在 `routes` 目录下的任何一个地方。开发者可以使用默认匹配规则，也可以自定义各个基础中间件组与路由文件的匹配规则。

- 可以设置全局的异常信息的响应格式为 Json 格式，也可以独立设置每个基础中间件组的异常信息的响应格式是否为 Json 格式。

- 可以自定义每个基础中间件组使用的 `domain` 。

- 可以自定义每个基础中间件组使用的 `prefix` 。

- 可以自定义每个基础中间件组的路由名称前缀。

- 在全部路由中检查是否存在重复的 URL（重复的 URL 是指包含域名的完整的 URL），如果存在，将抛出错误信息。扩展包还会指出重复的 URL 所在的路由文件地址，或是指出重复的 URL 所属的基础中间件组，以方便快速定位问题。

- 在全部路由中检查是否存在同名的命名路由，如果存在，将抛出错误信息。扩展包还会指出重复的命名路由所在的路由文件地址，或是指出重复的命名路由所属的基础中间件组，以方便快速定位问题。

> 提醒：扩展包不会检查控制器是否重复使用。所以，同安装本扩展包前一样，如果你重复使用了控制器，那么使用 `action()` 方法生成的控制器的 URL 可能就不是你想要的 URL。

### 关于基础中间件组

是否阅读此节内容不会影响扩展包的使用。

基础中间件组已设有默认值，所以安装扩展包后，开箱即用，无需配置。

如需了解更多，请继续阅读此节内容。

`app/HTTP/Kernel.php` 的 `$middlewareGroups` 属性里定义了中间件组，Laravel 开箱即用 `web` 和 `api` 两个中间件组，开发者也可以按需新增或者修改。中间件组的目的是让一次分配给路由多个中间件的实现更加方便。

基础中间件组是本扩展包的一个概念：允许自动匹配路由文件的中间件组叫基础中间件组。

所以定义基础中间件组的唯一目的是为了给指定的中间件组自动匹配路由文件，而不是笼统的给全部中间件组自动匹配路由文件。

比如开发者定义了一个中间件组，目的只是为了引用更方便，而不是像 `web` 和 `api` 中间件组一样为其匹配路由文件。这时，就需要定义基础中间组，来告诉扩展包哪些中间件组是需要自动匹配路由文件的。

扩展包默认 `web` 和 `api` 中间件组为基础中间组。

如需新增基础中间件组，在 `app/Providers/RouteServiceProvider.php` 中添加 `$baseMiddlewareGroups` 属性：

```php
protected $baseMiddlewareGroups = ['custom1', 'custom2'];
```

- `$baseMiddlewareGroups` 属性的值需为数组，数组的值为中间件组的名称。

- `$baseMiddlewareGroups` 属性的默认值为：`$baseMiddlewareGroups = ['web', 'api'];`。

- 定义的 `$baseMiddlewareGroups` 属性的值最终会与默认值合并，所以只需在 `$baseMiddlewareGroups` 属性中定义新增的基础中间件组即可。
    
- `$baseMiddlewareGroups` 属性的值不为空时，若值的类型错误，将使用默认值。

#### FAQs

- `web`，`api`，`custom1`，`custom2` 是中间件组，`web`，`api`，`custom1` 是基础中间件组。在 `web` 基础中间件组下的路由中，可以引用 `custom1` 中间件组吗？

    可以。基础中间件组本质就是中间件组，所以在 `web` 基础中间件组下的路由中引用 `custom1` 中间件组是没有问题的，即使引用的中间组被定义为基础中间件组。
    
    基础中间件组的唯一作用是给这个中间件组自动匹配相应的路由文件。
    
    在这个例子中，你可能好奇的是，如何匹配路由文件。`web` 和 `custom1` 都是基础中间件组，所以扩展包会分别为 `web` 和 `custom1` 中间件组匹配路由。虽然 `web` 基础中间件组下的路由引用了 `custom1` 中间件组，但这些路由是属于 `web` 中间件组的。

### 默认的路由匹配规则

安装扩展包后，会自动为每个基础中间件组匹配对应的路由文件。这些路由文件可以放在 `routes` 目录下的任何一个地方。

默认匹配规则：

以 `{基础中间件组名}.php` 命名，或以`{基础中间件组名}_` 开头、或以`_{基础中间件组名}.php` 结尾的路由文件将会分配至对应名称的基础中间件组。

> 注意：路径/文件名 不区分大小写。

例如：

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

- `web.php`、`welcome_web.php`、`web_User.php`、`Role/role_WEB.php` 这几个路由文件会被分配 `web` 基础中间件组。

- `api.php`、`welcome_api.php`、`api_User.php`、`Role/role_API.php` 这几个路由文件会被分配 `api` 基础中间件组。

### 默认的路由前缀

- `web` 基础中间件组的 `prefix` 默认为空。

- 其他基础中间件组的 `prefix` 将默认使用该基础中间件组的名称。

例如：

默认情况下，`web` 基础中间件组的 `prefix` 为空；`api` 基础中间件组的 `prefix` 使用该基础中间件组的名称 `api`。以此类推。

### 默认的路由名称前缀

所有基础中间件组的 `name` 默认为空，即不设置路由名称前缀。

### 默认使用的 `domain`

所有基础中间件组的 `domain` 默认为空，即不限制访问的子域名。

### 默认的异常信息的响应格式

- `api` 基础中间件组的异常信息的响应格式默认为 Json。

- 其他基础中间件组的异常信息的响应格式与安装扩展包前保持一致。

### 将异常信息的响应格式设置为 Json 格式

我们都知道，Laravel 抛出异常时，会显示 Whoops 页面，页面内容虽好，但对于 API 应用来说，就不那么友好了。

多数情况下，我们希望 API 应用在遇到异常时也能响应 Json 数据而不是一个页面。

安装扩展包后，你可以将全局的异常信息的响应格式设置为 Json 格式，也可以独立为每个基础中间组分别设置。

#### 设置全局的异常信息的响应格式设置为 Json 格式

在 `app/Providers/RouteServiceProvider.php` 中添加`$eJsonResponse` 属性，并将其值设为 `true`：

```php
protected $eJsonResponse = true;
```

- `$eJsonResponse` 属性的值需为布尔值（`true` 或 `false`）。

- `$eJsonResponse` 属性的值默认值为 `false`。
    
- `$eJsonResponse` 属性的值不为空时，若值的类型错误，将使用默认值。

- `$eJsonResponse` 属性的值为 `true` 时，无论访问哪个基础中间件组下的路由，异常信息的响应格式均为 Json 格式。

- `$eJsonResponse` 属性不存在或 `$eJsonResponse` 属性的值为 `false` 时，会根据当前路由所属的基础中间组的异常信息响应格式的设置来决定当前异常信息的响应格式。

    例如：`$eJsonResponse` 属性的值为 `false`，基础中间组的异常信息响应格式的设置使用默认值。那么访问 `web` 路由时，异常信息的响应格式与安装扩展包前保持一致，访问 `api` 路由时，异常信息的响应格式为 Json。

> 注意：
> 
> 全局的异常信息响应格式的设置是在注册扩展包的服务提供者时完成的，也就是说，如果在注册扩展包的服务提供者前，应用就抛出异常，那么异常信息的响应格式会与安装扩展包前一致。

#### 为每个基础中间件组设置是否响应为 Json 格式

有时可能会遇到这样一种情况，在你的 Laravel 应用中，既存在 web 页面又存在 api 接口。通常来说，我们希望 web 页面遇到异常时就响应 Whoops 页面，api 接口遇到异常时就响应 Json 数据，而不是笼统的全部都响应 Whoops 页面或者 Json 数据。

现在，扩展包安装后，就可以实现了这一功能了。

如何为每个基础中间件组设置是否将异常信息响应为 Json 格式，请参看自定义规则一节。

> 需要注意的是:
>
> 基础中间件组的异常信息响应格式是在所有的服务提供者启动完成后被设置的。也就是说，无论你为当前访问的路由设置的异常信息响应格式是否为 Json，如果应用在服务提供者注册、启动阶段抛出异常，那么异常信息的响应格式将与安装扩展包前一致。

如果你希望将服务提供者注册、启动阶段抛出的异常信息响应为 Json 格式，扩展包提供了一个将异常信息优先响应为 Json 的选项，开启这个选项后，如果应用在扩展包的服务提供者注册后，全部服务提供者启动完成前抛出异常，异常信息的响应格式将为 Json 格式。等全部服务提供者启动完成后，再使用基础中间件组各自的异常信息响应格式设置。

如需设置优先响应异常信息为 Json 格式，在 `app/Providers/RouteServiceProvider.php` 中添加`$ePriorityJson` 属性，并将其值设为 `true`：

```php
protected $ePriorityJson = true;
```

- `$ePriorityJson` 属性的值需为布尔值（`true` 或 `false`）。

- `$ePriorityJson` 属性的值默认值为 `false`。
    
- `$ePriorityJson` 属性的值不为空时，若值的类型错误，将使用默认值。

- `$ePriorityJson` 属性的值为 `true` 时，如果应用在扩展包的服务提供者注册后，全部服务提供者启动完成前抛出异常，异常信息的响应格式将为 Json 格式。全部服务提供者启动完成后，将使用基础中间件组各自的异常信息响应格式设置。

> 注意：
>
> 全局设置优先独立设置。即设置了全局异常信息的 Json 响应后，基础中间件组的独立设置以及优先响应 Json 的选项值都将失效，直至取消全局异常信息的 Json 响应。

### 自定义规则

自定义的规则会覆盖默认规则，未自定义的部分则将继续使用默认规则。

如需设置自定义规则，在 `app/Providers/RouteServiceProvider.php` 中添加如下方法：

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
    
在 `$customRules` 二维数组中，一维键 `{baseMiddlewareGroupName}` 是需要自定义规则的基础中间件组的名字。

`{baseMiddlewareGroupName}` 的值也是数组，其可配置的键有 `domain`， `prefix`， `name`，`eJsonResponse`，`matchRules`。分别可配置该基础中间件组的 `domain`，该基础中间件组的 `prefix`，该基础中间件组的路由名称前缀，该基础中间件组的异常信息的响应格式，以及该基础中间件组与路由文件的匹配规则。

- 如需自定义基础中间件组的 `domain`，请在该基础中间件组的数组下设置 `domain` 键值对。

    - `domain` 的值需为字符串。
    
    - `domain` 的值不为空时，若值的类型错误，将抛出异常。

- 如需自定义基础中间件组的 `prefix`，请在该基础中间件组的数组下设置 `prefix` 键值对。

    - `prefix` 的值需为字符串。
    
    - `prefix` 的值不为空时，若值的类型错误，将抛出异常。
    
- 如需自定义基础中间件组的路由名称前缀，请在该基础中间件组的数组下设置 `name` 键值对。

    - `name` 的值需为字符串。
    
    - `name` 的值不为空时，需在全部基础中间件组中唯一。

    - `name` 的值不为空时，若值的类型错误或 `name` 的值不唯一时，将抛出异常。
    
- 如需将基础中间件组的异常信息响应格式设置为 Json 格式，请在该基础中间件组的数组下设置 `eJsonResponse` 键值对。

    - `eJsonResponse` 的值需为布尔值（`true` 或 `false`）。

    - `eJsonResponse` 的值为 `true` 时，该基础中间件组下的路由的异常信息的响应格式为 Json 格式。

    - `eJsonResponse` 的值不为空时，若值的类型错误，将抛出异常。
    
- 如需自定义基础中间件组与路由文件的匹配规则，请在该基础中间件组的数组下设置 `matchRules` 键值对。

    - `matchRules` 的值是一个匿名函数。
    
    - 匿名函数传入一个路由文件的路径，这个路径是相对于 `routes` 目录的相对路径，开发者需要自行编码判断该路由文件是否匹配至该中间件组，然后返回布尔值。
    
        > 注意：与默认规则的路径/文件名不区分大小写不同，传入匿名函数的路径/文件名是区分大小写的。
    
    - `matchRules` 的值不为空时，若值不是匿名函数或匿名函数返回值不是布尔值时，将抛出异常。

#### 关于自定义规则的 FAQs
    
- 在基础中间件组配置了 `domain`，还可以在与之匹配的路由文件中创建子域名路由吗？子域名路由又使用哪个域名呢？

    可以创建子域名路由。基础中间件组配置的 `domain` 可以理解为访问这个基础中间件组的默认域名，如果在路由文件中创建子域名路由组，将通过新配置的 `domain` 访问子域名路由组中的路由。未包含在子域名路由组中的路由继续使用原配置的 `domain`。
    
- 命名路由的名字确定是唯一的，但为什么扩展包还是提示命名路由名字重复？

    如果确定命名路由的名字在全部路由文件中是唯一的，你肯定是使用了自定义的匹配规则，且你肯定是将同一个路由文件匹配到了多个基础中间件组中。如果你读过 Laravel 路由服务提供者的代码，你就会发现，你所定义的路由实际都包含在基础中间件组的路由组下，当你把同一路由分配给多个基础中间件组时，实际上会生成多个最终的路由，但这些路由又都使用了相同的名称，因此会提示命名路由名字重复。
    
    如果你需要将同一个路由文件匹配到了多个基础中间件组，你可以为基础中间件组设置不同的路由名称前缀，或者删除这个路由文件中路由的 `name` 属性。

### 重复路由的检查

在 Laravel 中，如果你定义了相同的 URL 或同名的命名路由，后定义的路由会覆盖掉已存在的路由。重复定义并不是我们所期望的，因为这会使得路由变得混乱、不易维护管理。

现在，扩展包会自动在全部路由中检查是否存在重复的 URL 或同名的命名路由，如果存在，将抛出错误信息。（重复的 URL 是指包含域名的完整的 URL）

### 路由缓存

当应用发布到生成环境时，一定别忘了在生产环境生成路由缓存！

输入命令：

```shell
php artisan route:cache
```

上面这个命令会生成 `bootstrap/cache/routes.php` 路由缓存文件。

需要注意的是，路由缓存并不会作用在基于闭包的路由。要使用路由缓存，必须将所有闭包路由转换为控制器类。

使用闭包路由，在生成缓存时，将报错！

另外，如果添加了任何新的路由，都必须生成新的路由缓存噢！

如果想要移除缓存路由文件的话，可以使用命令：

```shell
php artisan route:clear
```

## 许可证

本扩展包基于 [MIT license](https://opensource.org/licenses/MIT) 开源。
