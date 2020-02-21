<?php

/**
 * bean工厂配置文件，根据 bean 配置数组来构造对象，需要遵循 PSR-4 标准的
 * 自动加载机制。
 *
 * bean 工厂创建的 bean 是单例的，如不能使用单例 bean，需自行创建对象。
 * bean 工厂创建的 类对象 不是单例的，每次创建的类均为新的实例。
 *
 * 支持：
 *  1. 构造函数，构造函数参数。
 *  2. 工厂类，工厂类参数
 *  3. 属性赋值
 *  4. 类循环引用（有限制）
 *
 * 以数组形式定义 bean:
 *  <code>$beans = array();</code>
 *
 * 1. bean 定义，使用 'class' 定义 bean，定义需要包含 namespace
 *      $beans['url']['class'] = '\hymie\Url';
 *
 * 2. 定义构造函数
 *  $beans['mockbean'] = [
 *      'class' => 'beans\MockBean',
 *      'construct-args' => [
 *          'propa' => 1,
 *          'propb' => 2
 *      ]
 *  ];
 *
 * 3. 定义工厂方法，
 * 注意：如果工厂函数如果不是静态的，那么工厂类需要有无参构造函数。
 *      如果工厂函数是静态的，则对工厂类的构造函数无要求。
 *  $beans['mockbean2'] = [
 *      'factory-class' => 'beans\MockBean',
 *      'factory-method' => 'getInstance',
 *      'factory-method-args' => [
 *          'propa' => 4,
 *          'propb' => 5
 *      ]
 *  ];
 *
 * 4 定义属性（构造函数和工厂方法定义一致，这里使用构造函数做实例）
 *  $beans['mockbean3'] = [
 *      'factory-class' => 'beans\MockBean',
 *      'factory-method' => 'getInstance',
 *      'factory-method-args' => [
 *          'propa' => 4,
 *          'propb' => 5
 *      ],
 *      'props' => [
 *          'propa' => 6,
 *          'propb' => 7
 *      ]
 *  ];
 *
 * 5 定义 bean 依赖
 *  $beans['refa1'] = [
 *      'class' => 'beans\RefA',
 *      'construct-args' => [
 *          'refb' => 'ref:refb'
 *      ]
 *  ];
 *
 *  $beans['refa2'] = [
 *      'factory-class' => 'beans\RefA',
 *      'factory-method' => 'getInstance',
 *      'factory-method-args' => [
 *          'refb' => 'ref:refb'
 *      ]
 *  ];
 *
 *  $beans['refb'] = [
 *      'class' => 'beans\RefB'
 *  ]
 *
 * 6. 定义循环依赖，beanA 依赖 beanB，同时 beanB 也依赖 beanA，因此在定义 bean 时需要注意：
 *  6.1 不能都使用 构造函数 或者 工厂方法 或者 构造函数-工厂方法 的方式，会死循环。
 *  6.2 beanA 可以使用 构造函数 或 工厂方法 的方式定义 beanB 的依赖关系，beanB 使用属性方式定义 beansA 的依赖关系。
 *  6.3 可以同时使用属性的方式定义双方依赖关系。
 *
 *  $beans['cyca'] = [
 *      'class' => 'beans\CycleA',
 *      'construct-args' => [
 *          'cycleB' => 'ref:cycb'
 *      ]
 *  ];
 *
 *  $beans['cycb'] = [
 *      'class' => 'beans\CycleB',
 *      'props' => [
 *          'cycleA' => 'ref:cyca'
 *      ]
 *  ];
 */
$beans = array();

// predis bean
// $beans['predis'] = [
//     'class' => 'Predis\Client',
//     'construct-args' => [
//         'parameters' => ['scheme' => 'tcp', 'host' => '10.0.0.1', 'port' => 6379],
//         'options' => ['prefix' => 'hymie:']
//     ]
// ];

// cache bean
// $beans['cache'] = [
//     // 'class' => '\Symfony\Component\Cache\Adapter\FilesystemAdapter',
//     'class' => '\Symfony\Component\Cache\Adapter\RedisAdapter',
//     'construct-args' => [
//         'ref:predis'
//     ]
// ];

// $beans['twig_loader'] = [
//     'class' => '\Twig\Loader\FilesystemLoader',
//     'construct-args' => [
//         get_config('view/view_root', APP_ROOT) . DIRECTORY_SEPARATOR
//     ]
// ];

// $beans['twig'] = [
//     'class' => '\Twig\Environment',
//     'construct-args' => [
//         'ref:twig_loader',
//         ['cache' => get_config('view/cache_root', ROOT).DIRECTORY_SEPARATOR.'twig', 'debug' => get_config('debug', false)],
//     ],
// ];

//medoo bean
// $beans['medoo'] = [
//     'class' => 'Medoo\\Medoo',
//     'construct-arg' => [
//         'database_type' => 'mysql',
//         'database_name' => 'name',
//         'server' => 'localhost',
//         'username' => 'your_username',
//         'password' => 'your_password',
//     ],
// ];

// pdo bean
// $beans['pdo'] = [
//     'class' => 'PDO',
//     'construct-args' => [
//         'dsn' => 'mysql:dbname=gszx;host=127.0.0.1',
//         'user' => 'root',
//         'password' => '123456',
//         [\PDO::ATTR_PERSISTENT => true]
//     ]
// ];

$GLOBALS['_beans'] = &$beans;
