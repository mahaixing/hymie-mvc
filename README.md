完整的手册请参考 [Hymie PHPMVC framework 手册](http://hymie.iautoo.cn/)

# 变更历史

| 版本号 | 说明 |
| ----- | --- |
| v1.0  | 实现MVC 框架、BeanFactory、路由、过滤器等功能 |
| v1.1  | 实现通过注解方式配置路由 |
| v1.2  | 1. 修改缓存工厂实现 </br> 2. 增加 APCu 缓存 </br> 3. 修改 BeanFactory、Router、Filter 默认缓存
为 APCu 缓存 </br> 4. 调整 Router 处理方式 </br> 5. 调整清理缓存方法|
| v2.0.2 | 增加 twig delimiter 配置 |
| v2.0.5 | 控制器文件名需要以 Controller.php 结尾 |


# 框架介绍
Hymie PHPMVC 是一个轻量级 MVC 框架，实现中借鉴了 [webpy](http://webpy.org/) 的一些设计思路。Hymie 的目标是实现基本的 MVC 模式，规范化的开发目录结构约定，并且易于整合和使用第三方库、框架。Hymie 框架代码文件大小约为 270KB。

框架遵循以下 PSR 标准:

  1. `PSR-2`
  2. `PSR-4`
  3. `PSR-6` 
  4. `PSR-16` 

> 这个帮助文档站点就是使用海米 PHPMVC 框架开发的，代码地址 <a href="https://github.com/mahaixing/hymie-website" target="_blank">Github</a> 或者 <a href="https://gitee.com/mahaixing/hymie-website" target="_blank">Gitee</a>

> 如果有改进建议或 BUG 反馈，请联系 <a href="mailto://mahaixing@gmail.com">mahaixing@gmail.com</a>

## 约定

1. `web_root` 指的是 `web` 应用的主目录

2. `document_root` 指的是 Apache 的 `DocumentRoot` 或者 nginx 的 `root`。

## 一、安装

### 1.1 Composer

  `composer require hymie/mvc`

  代码中引用 `config.php` 即可完成框架配置

### 1.2 源码安装
可以从 github 或者 gitee 下载源码使用框架，下载后需要把框架目录放到 `web_root` 下。

  `git clone https://github.com/mahaixing/hymie-mvc`

  `git clone https://gitee.com/mahaixing/hymie-mvc`

  保存下载的目录到项目主目录，代码中需要手工注册 Hymie 的 autoload，用于加载框架类。

  ```
  <?PHP
    // index.php
    require '[web_root]/hymie/src/Loader.php';

    \hymie\Loader::registerHymieClasses();

    require 'config.php';
  ```
### 1.3 依赖
如果使用 1.2 源码安装则需配置 `composer.json` 以获取依赖包，以下为依赖配置部分

```
    "require": {
      "php": "^7.1",
      "catfan/medoo": "^1.7",
      "doctrine/annotations": "^1.7",
      "mobiledetect/mobiledetectlib": "^2.8",
      "monolog/monolog": "^1.24",
      "predis/predis": "^1.1",
      "symfony/cache": "^3.4",
      "twig/twig": "^2.11",
      "voku/anti-xss": "^4.1",
      "webmozart/assert": "^1.4",
      "filp/whoops": "^2.4"
    }
```
将以上部分放到 `composer.json` 文件后，执行 `composer intall` 命令完成依赖安装。

> composer 安装很慢的话，可以使用 <a href="https://developer.aliyun.com/composer" target="_blank">阿里云 Composer 全量镜像</a>

### 1.4 源码目录说明

  ```
  hymie -+
         |--- docs --- api               (api文档)
         |--- src                        (源代码)
         |--- tests                      (单元测试代码)
         |--- .gitignore   
         |--- composer.json
         |--- config.bean.sample.php     (bean 配置示例)
         |--- config.filter.sample.php   (过滤器配置示例)
         |--- config.router.sample.php   (路由配置示例)
         |--- config.sample.php          (配置文件示例)
         |--- hymie                      (hymie 脚本，如果 composer 安装可以 ./vendor/bin/hymie 调用)
         |--- phpunit.xml                (phpunit 测试套件编排文件)
          --- README.md                  
  ```

### 二、快速入门

### 2.1 新建项目
  1. 在 `document_root` 下新建目录 `mkdir example` 并进入目录 `cd example`
  
  2. 执行命令：

  ```
    composer require hymie/mvc
  ```

  3. composer 会安装框架及依赖库。

### 2.2 使用 hymie 脚本拷贝配置、创建模块

1. 拷贝配置文件

  `./vendor/bin/hymie copy .`

2. 新建模块 `web`

  `./vendor/bin/hymie add web .`

### 2.3 编写入口文件

1. 新建 `index.php` 并放入以下内容

  ```
  <?PHP
  require 'config.php';

  $app = new \hymie\Application(['web']);
  $app->run();
  ```

_现在的项目目录结构_

  ```
  document_root 
   |
   + -- example
          |
          |-- app -+
          |        |
          |         - web -+
          |                |- bean                (可选)
          |                |- controller          (可选)
          |                |- service             (可选)
          |                |- view                (必须, 默认的模块模板路径)
          |                |- filter              (可选)
          |                |- dao                 (可选)
          |                |- config.filter.php   (过滤器配置, 可选)
          |                 - config.router.php   (路由配置, 可选)
          |-- index.php                           (入口文件)
          |-- config.php                          (配置文件)
           -- config.bean.php                     (bean 配置文件)
  ```
### 2.4 编写代码

1. 编写控制器
  
  新建 `app/web/controller/IndexController.php' 并放入以下内容

```
<?PHP
// app/web/controller/IndexController.php
namespace web\controller;

class IndexController
{
    public function index()
    {
        $msg = 'hello world';
        return result()->add("msg", $msg)->setView("index")->success();
    }
}

```

2. 编写视图

  新建 `app/web/view/index.php` 并放入以下内容

  ```
  <!-- app/web/view/index.php -->
  <html>
    <head>
      <title>example index</title>
    </head>
    <body>
      <h1><?PHP echo $msg;?></h1>
    </body>
  </html>
  ```

3. 配置路由

  修改 `app/web/config.router.php` 添加路由

    ```
    <?PHP
    // config.router.php

    return array(
        '/' => 'web\controller\IndexController'
    );
    ```
### 2.4 运行

  访问浏览器地址 `http://localhost/example/index.php`, ok 大功告成。

## 三、核心组件

### 3.1 正则表达式

Hymie 框架开发过程需要大量（至少在路由、过滤器定义中）使用正则表达式，使用正则表达式定义路由可以在一定程度上减少 xss 和 sql 注入类型攻击（参考: 路由部分的 PathVariable 介绍）。

另外，正则表达式也是每个程序员均应该掌握的基本能力。

Hymie 框架使用 PHP pcre 相关函数处理正则表达式，具体请参考 <a href="https://www.php.net/manual/zh/book.pcre.php" target="_blank">PCRE</a>。

### 3.2 MVC 

Hymie 框架实现了 <a href="https://baike.baidu.com/item/MVC%E6%A1%86%E6%9E%B6/9241230?fromtitle=MVC&fromid=85990&fr=aladdin" target="_blank">MVC</a> 设计模式：

  ```

      前端控制器 ------> 具体控制器 ----> Service -----> Dao
        |   ^            |   ^          |   ^         |
        |   |            |   |          |   |         |
        |  (view)--------    (业务数据)--   (data)<----
        |    
     渲染视图
        |
        |
        v            
      浏览器      

  ```

#### 3.2.1 前端控制器

前端控制器是应用入口，Hymie 框架允许使用多个前端控制器，比如 `index.php` 是站点 web 端前端控制器，`admin.php` 是后前端控制器口，`api.php` 为接口前端控制器。

#### 3.2.2 控制器

控制器为遵循 PSR-4 规范的 PHP 类，控制器默认的方法为 `index`。控制器方法可以有参数，参数需要与路由定义的正则表达式匹配，如果表达式中包含可选的匹配项时，参数需要有默认值。

#### 3.2.3 Service

Service 用来实现业务逻辑。控制数据库事务。Service 为遵循 PSR-4 规范的 PHP 类，在控制器中直接实例化 Service 类即可。

> Service 使用具体类即可，其实无必要使用类似于 `ServiceInterface` `ServiceImpl` 这种方式。
> 所以，不建议使用 bean 工厂来管理 Service 类，配置工作量太大，而且对于大部分业务来说，其实也没有必要做过多解耦。

#### 3.2.4 Dao 

建议使用 `PDO` 或者 `Medoo` 来处理数据库操作，配合框架提供的分页能力，可以很容易完成数据操作、分页等业务需求。分页请参考相关章节。

#### 3.2.5 视图

目前框架支持 `json`、`php`、`twig` 三种视图。在配置文件中可以配置默认视图（参考配置章节），视图也可以混用（参考视图章节）。如果请求是 ajax 请求，则会默认返回 json 视图。

框架支持用户自定义视图，在配置文件中注册即可，详细参考视图章节。

> 视图唯一的要求就是需要在 view_root 目录下按照模块名组织目录，每个模块目录下必须有 `view` 子目录用来存放视图文件。

#### 3.2.6 Result 对象

控制器执行后可以返回 `\hymie\Result` 对象，`\hymie\Result` 对象支持链式调用，并且如果是 ajax 请求的话， json 视图会根据 `\hymie\Result` 对象组织 json 数据。

如果控制器不返回 `\hymie\Result` 对象，那么控制器需要自行输出数据到浏览器或者使用 `R($to)` 函数跳转网页。

#### 3.2.7 URL 模式
支持两种 URL 模式，PATHINFO 和 QueryString，在 `config.php` 中的 `url` 部分进行配置。`url` 配置会影响到 `\hymie\Url` 类生成链接的方式。

> 注: nginx 需要进行配置才能支持 PATHINFO，请参考 **服务器配置示例部分**

> 注: 如果要生成 url_rewrite 的 url, 需要在配置文件的 `url` 部分设置 `url_rewrite` = true

### 3.3 路由配置
框架支持两种路由配置方式：
1. 基于配置文件的路由配置  
  ```
    return array(
      '/' => '[module_dir_name]\controller\IndexController:someMethod',

      // 分页， 支持 
      // http://example.com/index.php?g=/a/1/10 （第1页，每页10条）
      // http://example.com/index.php?g=/a/1 （第1页，每页条数使用默认值10条）。
      '/product/list/(\d+)(?:(?:/)(\d+))?' => ['mod1\controller\ProductController', 'list'],
  */);
  ```
2. 基于注解的路由配置。
  ```
    use hymie\annotation\RouterMapping;
    
    /**
     * @RouterMapping(value="/(\w*)")
     */
    class SomeController
    {
        public function index($name)
        {
           // handle path  '/abc' '/def' /ghi
           // $name = "abc" or "def" or "ghi"
        }
    }
  ```

  ```
  use hymie\annotation\RouterMapping;

  /**
    * @RouterMapping(value="/other")
    */
  class SomeOtherController
  {
      public function index()
      {
          // handle path  '/other'
      }

      /**
        * @RouterMapping(value="/foo-(\d{1,3})")
        */
      public function login($number)
      {
          // handle path '/other/foo-1" "/other/foo-123"
          // $number = 1 or $number = 123

          //could not handle '/ohter/foo-1234'
      }
  }
  ```

### 3.4 过滤器

过滤器针对配置的路由进行过滤，支持正则表达式匹配路由，支持正则表达式匹配特定路由地址的排除。

* 当请求后过滤器会以链式方式逐个匹配路由，如不匹配或者匹配后过滤器方法返回 true 则由下一个过滤器继续处理。

* 当路由无法通过过滤器逻辑，过滤器可使用 `R($to)` 函数跳转页面，也可直接返回 `\hymie\Result` 对象。

**过滤器是按照定义顺序执行的，因此定义多个过滤器时要注意顺序。**

### 3.5 Bean 工厂

框架中提供 Bean 工厂能力，在 `config.bean.php` 中配置 bean 信息，由 bean 工厂负责实例化对象。

#### 3.5.1 bean 配置  

  bean 工厂支持的配置:

  1. bean 间依赖:  
    beana 依赖 beanb

  2. 循环依赖（有条件）:  
    beana 依赖 beanb，且 beanb 也依赖 beana

  3. 构造函数:  
    通过构造函数创建对象实例

  4. 工厂方法:  
    通过工厂方法创建对象实例

  4. 属性赋值:  
    为对象实例属性赋值（public、private、protected 实例都可赋值）
    
  5. 函数调用:  
    实例创建后要执行的函数

>配置文件中配置的 bean 默认是单例的

#### 3.5.2 根据类名创建类

  bean 工厂可以直接根据类名创建类实例。

>通过类名创建的类实例默认不是单例的，可以在调用 `get_bean` 方法时指定是否需要单例实例。

bean 工厂详细信息请参考 bean 工厂章节

### 3.6 分页

Hymie 框架基于适配器模式提供了通用的分页能力，分页主类为 `\hymie\pager\Pager` 通过该类的工厂方法 `public static function getPager($adapterObjOrClassName, ...$params)` 创建实例。

分页类在 $_GET $_POST $_REQUEST 中查找 `config.php` 配置文件中指定的分页参数 `p` 页数 `s` 每页数据条数获取分页参数。

目前分页类支持 `\PDO` 以及 `Medoo` 分页适配器。

对于 `\PDO` 适配器已实现 `Mysql` SQL 方言，其他数据库类型后续会逐步完成。

具体请参考分页章节。

### 3.7 缓存

框架的缓存部分支持 PSR-6 PSR-16 标准缓存库，会将所有 PSR-6 缓存适配为 PSR-16 缓存接口，框架实现了 

1. `ArrayCache` 基于数组的缓存，在单次请求中有效

2. `ApcuCache` 基于 APCu 的缓存，单台服务器实例有效。

3. `Psr6Adapter` 适配 PSR-6 缓存实现到 PSR-16 接口规范

#### 3.7.1 `Cache` 工厂

框架通过 `Cache` 工厂来为应用和框架本身生产缓存实例，工厂方法原型为：

  ```
    public static function getInstance(
        $beanNameOrClassName = self::DEFAULT_BAEN_NAME,
        $replaceBeanNameOrClassName = null,
        $useBeanFactory = false,
        $cleanable = false
    ) 
  ```

其中：

1. `$beanNameOrClassName`: 要初始化的缓存 `bean` 名或者类名。

2. `$replaceBeanNameOrClassName`: 如果要初始化的缓存 `bean` 或类无法加载，则替换的缓存实现，默认是 `ArrayCache` 

如果替换缓存实现也无法加载，则最终会返回 `ArrayCache` 实例，以保证使用缓存的代码无需做过多可用性判断，以及可测试性。

3. `$useBeanFactory`: 是否使用 BeanFactory，默认是 true，否则会直接 new 类

4. `$cleanable`: 是否可清理，默认是否，如果为 true 的话会用 `\hymie\cache\impl\CleanableCache` 包装生成的 bean

Application 启动时会调用 Cache::registerCleaner 函数，在脚本结束时检查 ROOT . DIRECTORY_SEPARATOR .clean_cache 文件，如存在则会清理 CleanableCache 实例。

> 注意：由于 PSR-6 PSR-16 标准没有遍历所有 key 的方法，因此清理实际上会清除整个缓存，目前框架的 `BeanFactory` `Router` `Filter` 是可清理的缓存。

> `BeanFactory` 默认使用 `ApcuCache` 如不可用则会使用 `ArrayCache`，
> `Router` `Filter` 默认使用 `ApcuCache` 如不可用则会使用 `config.bean.php` 中配置的缓存实现，默认是系统缓存。
> **建议启用 php 的 `apcu` 来获取更好的性能**

### 3.8 日志
1. 系统日志不能在 `config.bean.php` 中配置, 因为 `BeanFactory` 中使用了日志, 会发生递归调用.

2. 日志使用 `monolog`, 在 `config.php` 中进行配置. 若未配置则默认使用 `\Psr\Log\NullLogger`, 因此所有输出日志的代码不会出错.

3. 日志配置项参考 `config.sample.php` 中相关注释.

4. 可以在 `config.bean.php` 中配置应用要使用的日志 bean. 

### 3.9 RedisSession
框架实现了 RedisSession, 可以在 `config.php` 中配置 `$config['session']['redis'] = true;` 来开启.

实现类为 `\hymie\session\RedisSession` 使用 [predis](https://packagist.org/packages/predis/predis) 与 redis 交互。默认会在 `config.bean.php` 中查找名为 `predis` 的 bean 配置，如未找到则抛出 `\hymie\session\SessionException`.

代码中需要使用 `start_session()` 函数来启动 session, 该函数会根据配置选择启动 php session 或者 redis session. **建议使用此函数来启动session, 而不是内置的 [`session_start`](https://www.php.net/session_start) 函数**

> Web 环境是一个多线程(或进程)的并发环境. 为了保证数据一致性，会对每次页面请求的会话进行加锁. 因此, 需要尽量快的完成 Session 的取值和赋值操作. 这不仅针对 RedisSession 对于其他 Session 实现也适用.