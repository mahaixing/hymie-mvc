<?php

/**
 * Project:       Hymie PHP MVC framework
 * File:          BeanFactory.php
 * Created Date:  2019-08-11.
 *
 * Github:        https://github.com/mahaixing/hymie-mvc
 * Gitee:         https://gitee.com/mahaixing/hymie-mvc
 * Composer:      https://packagist.org/packages/hymie/mvc
 *
 * @author:       mahaixing(mahaixing@gmail.com)
 * @license:      MIT
 */

namespace hymie;

use ReflectionClass;

/**
 * bean工厂，根据 bean 配置数组来构造对象，需要遵循 PSR-4 标准的
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
 *    1. 不能同时使用 构造函数参数 或者 工厂方法参数 定义依赖关系
 *    2. 不能使用 构造函数-工厂方法 或 工厂方法-构造函数 的方式定义依赖关系
 *    3. beanA 可以使用 构造函数 或 工厂方法 的方式定义 beanB 的依赖关系，beanB 使用属性方式定义 beansA 的依赖关系。
 *    4. 可以同时使用属性的方式定义双方依赖关系。
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
class BeanFactory
{
    /**
     * 属性名的常量.
     */
    const BEANS_KEY                 = '_beans';
    const CLASS_KEY                 = 'class';
    const CONSTRUCT_ARG_KEY         = 'construct-args';
    const FACTORY_KEY               = 'factory-class';
    const FACTORY_METHOD            = 'factory-method';
    const FACTORY_METHOD_ARG_KEY    = 'factory-method-args';
    const PROPS_KEY                 = 'props';
    const FUNCTIONS_KEY             = 'functions';
    const CONFIG_FILE               = ROOT . DIRECTORY_SEPARATOR . 'config.bean.php';

    const CACHE_BEAN_KEY_PREFIX     = 'framework.bean.';

    /**
     * 已初始化过的 bean，不再进行初始化。
     */
    private $cache;

    /**
     * bean 定义.
     */
    private $beansDef;

    /**
     * 静态实例.
     *
     * @var object
     */
    private static $instance;

    /**
     * 构造函数，先读取全局数组中的 bean 定义，如存在则引用，并合并传入的 $beansDef 数组.
     *
     * @param array $beansDef 传入的 bean 定义
     */
    public function __construct($beansDef = array())
    {
        if (file_exists(self::CONFIG_FILE)) {
            include_file(self::CONFIG_FILE);
        }

        if (array_key_exists(self::BEANS_KEY, $GLOBALS)) {
            $this->beansDef = &$GLOBALS[self::BEANS_KEY];
            if (is_array($beansDef)) {
                $this->beansDef = array_merge($this->beansDef, $beansDef);
            }
        } else {
            $this->beansDef = $beansDef;
        }

        $this->cache = \hymie\cache\Cache::getInstance(
            '\hymie\cache\impl\ApcuCache',
            \hymie\cache\Cache::DEFAULT_BAEN_NAME,
            false,
            true
        );
    }

    /**
     * 覆盖 trait 中的 getInstance 目的增加参数.
     *
     * @param array $beansDef
     */
    public static function getInstance($beansDef = array())
    {
        if (self::$instance == null) {
            self::$instance = new self($beansDef);
        }

        return self::$instance;
    }

    /**
     * setter.
     *
     * @param mixed $value
     */
    public function setBeansDef($value)
    {
        $this->beansDef = $value;
    }

    /**
     * getter.
     *
     * @return mixed
     */
    public function getBeansDef()
    {
        return $this->beansDef;
    }

    public function addBeansDef($beansDef)
    {
        if (is_array($beansDef)) {
            $this->beansDef = array_merge($this->beansDef, $beansDef);
        }
    }

    /**
     * 处理定义值，如果包含 ref: 则加载 ref: 定义的 bean。
     * 否则返回原值。
     *
     * @param mixed $value
     */
    private function resolvValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $tmp = explode(':', $value);
        if (count($tmp) == 0) {
            return $value;
        } elseif ($tmp[0] === 'ref') {
            return $this->getBean(trim($tmp[1]));
        } else {
            return $value;
        }
    }

    /**
     * 处理参数，检查参数是否存在，参数定义格式是否正确。
     *
     * 如果参数中包含 ref: 定义，则构造相应的 bean
     *
     * @param array  $beanDef
     * @param string $argKey
     */
    private function resolvArgs($beanDef, $argKey)
    {
        if (!array_key_exists($argKey, $beanDef)) {
            log_debug(sprintf('%s: no argument defined!', self::class, $beanDef));

            return null;
        }

        if (!is_array($beanDef[$argKey])) {
            log_error(sprintf('%s: argument definition must be an array!', self::class, $beanDef));

            return null;
        }

        return $this->resolvArgsArray($beanDef[$argKey]);
    }

    /**
     * 解析参数数组.
     *
     * @param array $array
     */
    private function resolvArgsArray($array)
    {
        if (count($array) == 0) {
            return null;
        }

        $args = array();

        foreach ($array as $key => $value) {
            $args[$key] = $this->resolvValue($value);
        }

        return $args;
    }

    /**
     * 调用对象方法.
     *
     * @param object $obj    目标对象
     * @param string $method 方法名
     * @param array  $args   参数列表
     */
    private function invokeMethod($obj, $method, $args)
    {
        if (is_null($args)) {
            return $method->invoke($obj);
        } else {
            return $method->invokeArgs($obj, $args);
        }
    }

    /**
     * 设置属性值
     *
     * @param \ReflectionClass    $reflectionClass    反射类
     * @param object              $instance           实例
     * @param \ReflectionProperty $reflectionProperty 反射属性
     * @param string              $prop               属性名
     * @param mixed               $value              属性值
     */
    private function setPropertyValue($reflectionClass, $instance, $reflectionProperty, $prop, $value)
    {
        // $methodName = 'set' .  \ucfirst($prop);
        // if ($reflectionClass->hasMethod($methodName)) {
        //     $this->invokeMethod($instance, $reflectionClass->getMethod($methodName), [$prop=>$value]);
        //     return;
        // }

        if (!$reflectionProperty->isPublic()) {
            $reflectionProperty->setAccessible(true);
        }

        if ($reflectionProperty->isStatic()) {
            $reflectionProperty->setValue($value);
        } else {
            $reflectionProperty->setValue($instance, $value);
        }
    }

    /**
     * 循环设置 bean 属性.
     *
     * @param object           $instance        生成的 bean
     * @param \ReflectionClass $reflectionClass 反射类
     * @param array            $beanDef         bean 定义
     * @param string           $name            bean 名
     */
    private function setInstanceProperties($instance, $reflectionClass, $beanDef, $name)
    {
        if (!array_key_exists(self::PROPS_KEY, $beanDef)) {
            log_info(sprintf('%s: bean "%s" does not define any properties, return instance directly.', self::class, $name), $beanDef);

            return $instance;
        }

        $props = $beanDef[self::PROPS_KEY];
        if (!is_array($props)) {
            log_error(sprintf('%s: bean "%s" properties definition must be an array', self::class, $name), $beanDef);

            return $instance;
        }

        foreach ($props as $prop => $value) {
            $value = $this->resolvValue($value);

            if ($reflectionClass->hasProperty($prop)) {
                $reflectionProperty = $reflectionClass->getProperty($prop);
                $this->setPropertyValue($reflectionClass, $instance, $reflectionProperty, $prop, $value);
            } else {
                log_error(sprintf('%s: bean "%s" does not have property named "%s"', self::class, $name, $prop), $beanDef);
            }
        }

        return $instance;
    }

    /**
     * 调用初始化方法.
     */
    private function invokeFunctions($instance, $reflectionClass, $beanDef, $name)
    {
        if (!array_key_exists(self::FUNCTIONS_KEY, $beanDef)) {
            return;
        }

        $functions = $beanDef[self::FUNCTIONS_KEY];
        foreach ($functions as $funcName => $args) {
            if (!$reflectionClass->hasMethod($funcName)) {
                log_error(sprintf('%s: bean "%s" does not have method "%s", skip it.', self::class, $name, $funcName), $beanDef);
                continue;
            }
            $reflectionMethod = $reflectionClass->getMethod($funcName);
            if ($args != null) {
                $args = (is_array($args)) ? $args : [$args];
                $args = $this->resolvArgsArray($args);
                $this->invokeMethod($instance, $reflectionMethod, $args);
            } else {
                $this->invokeMethod($instance, $reflectionMethod, null);
            }
        }
    }

    /**
     * 使用工厂方法生成对象
     *
     * @param array  $beanDef bean定义
     * @param string $name    bean 名
     */
    private function createInstanceByFactory($beanDef, $name)
    {
        $instance = null;

        $className = $beanDef[self::FACTORY_KEY];

        //如果工厂类不存在
        if (!class_exists($className, true)) {
            log_error(sprintf('%s: the classname "%s" of factory "%s" does not exist, please check your bean definition.', self::class, $className, $name), $beanDef);

            return null;
        }

        //如果没有定义工厂方法
        if (!array_key_exists(self::FACTORY_METHOD, $beanDef)) {
            log_error(sprintf('%s: the factory class does not contain method definition "%s"', self::class, self::FACTORY_METHOD), $beanDef);

            return null;
        }

        $factoryMethod = $beanDef[self::FACTORY_METHOD];

        $reflectionClass = new ReflectionClass($className);

        //如果工厂函数不存在
        if (!$reflectionClass->hasMethod($factoryMethod)) {
            log_error(sprintf('%s: factory method "%s" does not exists in class %s', self::class, $factoryMethod, $className), $beanDef);

            return null;
        }

        $factoryMethodArgs = $this->resolvArgs($beanDef, self::FACTORY_METHOD_ARG_KEY);
        $reflectionMethod = $reflectionClass->getMethod($factoryMethod);

        if (!$reflectionMethod->isStatic()) {
            $instance = $this->invokeMethod($reflectionClass->newInstance(), $reflectionMethod, $factoryMethodArgs);
        } else {
            $instance = $this->invokeMethod(null, $reflectionMethod, $factoryMethodArgs);
        }

        return [$instance, new ReflectionClass($instance)];
        // return [$instance, $reflectionClass];
        // return $this->setInstanceProperties($instance, $reflectionClass, $beanDef, $name);
    }

    /**
     * 使用构造函数生成 bean.
     *
     * @param array  $beanDef bean 定义
     * @param string $name    bean 名
     *
     * @return object
     */
    private function createInstanceByConstruct($beanDef, $name)
    {
        $instance = null;

        $className = $beanDef[self::CLASS_KEY];
        if (!class_exists($className, true)) {
            log_error(sprintf('%s: the classname "%s" of bean "%s" does not exist, please check your bean definition.', self::class, $className, $name), $beanDef);

            return null;
        }

        $reflectionClass = new ReflectionClass($beanDef[self::CLASS_KEY]);
        //解析参数
        $constructArgs = $this->resolvArgs($beanDef, self::CONSTRUCT_ARG_KEY);
        if (is_null($constructArgs)) {
            //没有参数
            $instance = $reflectionClass->newInstance();
        } else {
            //调用构造函数
            $instance = $reflectionClass->newInstanceArgs($constructArgs);
        }

        return [$instance, $reflectionClass];
        // return $this->setInstanceProperties($instance, $reflectionClass, $beanDef, $name);
    }

    /**
     * 根据类名称创建bean.
     *
     * @param string     $name   类名
     * @param array|null $params 参数数组
     */
    private function createInstanceByClassName($name, $params)
    {
        try {
            $reflectionClass = new ReflectionClass($name);
            $instance = null;
            if ($params == null || !is_array($params) || count($params) == 0) {
                $instance = $reflectionClass->newInstance();
            } else {
                $instance = $reflectionClass->newInstanceArgs($params);
            }

            return $instance;
        } catch (\ReflectionException $e) {
            log_error(sprintf('%s: could not create class "%s", error is: "%s"', self::class, $name, $e->getMessage()));

            return null;
        }
    }

    /**
     * 生产 bean。
     * 有两种方式：
     * 1. 通过 bean 配置文件，此时值会使用 $bean 参数。
     * 2. 通过类名和参数，此时会使用反射方法构造对象，会用到 $bean、$params 参数.
     *
     * @param string     $name        bean名
     * @param array|null $params      通过类名创建类时，构造函数的参数
     * @param bool       $isSingleton 用于控制适用类名构造对象时是否单例，不适用于 bean 定义情况（后续可能会调整）
     */
    public function getBean($name, $params = null, $isSingleton = false)
    {
        if ($this->cache->has(self::CACHE_BEAN_KEY_PREFIX . $name)) {
            log_debug(sprintf('%s: found bean "%s" in cache, return it!', self::class, $name));

            return $this->cache->get(self::CACHE_BEAN_KEY_PREFIX . $name);
        }

        //未定义bean，可能是类名，尝试初始化类
        if (!array_key_exists($name, $this->beansDef)) {
            log_debug(sprintf('%s: bean "%s" definition does not exists, try to initialize by classname.', self::class, $name));
            if (class_exists($name)) {
                log_debug(sprintf('%s:  class "%s" exist, will create an instance by classname.', self::class, $name));
                $classIntance = $this->createInstanceByClassName($name, $params);
                if ($isSingleton == true) {
                    $this->cache->set(self::CACHE_BEAN_KEY_PREFIX . $name, $classIntance);
                }

                return $classIntance;
            }

            log_error(sprintf('%s: "%s" is not a bean name or classname, please check your config or classname', self::class, $name), $this->beansDef);

            return null;
        }

        $beanDef = $this->beansDef[$name];
        $instance = null;

        try {
            $instance = null;
            $reflectionClass = null;
            if (array_key_exists(self::CLASS_KEY, $beanDef)) {
                [$instance, $reflectionClass] = $this->createInstanceByConstruct($beanDef, $name);
            } elseif (array_key_exists(self::FACTORY_KEY, $beanDef)) {
                [$instance, $reflectionClass] = $this->createInstanceByFactory($beanDef, $name);
            } else {
                log_error(sprintf('%s: bean "%s" definition error, need "%s" or "%s" in bean definition.', self::class, $name, self::CLASS_KEY, self::FACTORY_KEY), $beanDef);

                return null;
            }

            //在这里先把 bean 加到已创建 bean 缓存中，是为了处理循环引用问题。
            $this->cache->set(self::CACHE_BEAN_KEY_PREFIX . $name, $instance);

            $this->setInstanceProperties($instance, $reflectionClass, $beanDef, $name);
            $this->invokeFunctions($instance, $reflectionClass, $beanDef, $name);

            // log_debug(sprintf('bean "%s" not found in cache, create a new one!', $name));
            return $instance;
        } catch (\ReflectionException $e) {
            log_error(sprintf('%s: could not create bean "%s", error is: "%s"', self::class, $name, $e->getMessage()), $beanDef);

            return null;
        }
    }
}
