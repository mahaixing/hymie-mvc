<?PHP

/**
 * Project:       Hymie PHP MVC framework
 * File:          Loader.php
 * Created Date:  2019-08-11
 * 
 * Github:        https://github.com/mahaixing/hymie-mvc
 * Gitee:         https://gitee.com/mahaixing/hymie-mvc
 * Composer:      https://packagist.org/packages/hymie/mvc
 * 
 * @author:       mahaixing(mahaixing@gmail.com)
 * @license:      MIT
 */

namespace hymie;

/**
 * 简单的类加载器，遵循 psr4 规范，仅仅用于加载 app 目录下的类。
 */
class Loader
{
    /**
     * 当前 hymie 主目录
     *
     * @var string
     */
    private static $hymieRoot;

    /**
     * 加载应用的class
     *
     * @param string $class
     * @return boolean
     */
    public static function loadClass($class)
    {
        $classFile = APP_ROOT . DIRECTORY_SEPARATOR . strtr($class, '\\', DIRECTORY_SEPARATOR) . '.php';

        if (file_exists($classFile)) {
            include_file($classFile);
            return true;
        }

        return false;
    }

    /**
     * 注册应用类加载器
     *
     * @return void
     */
    public static function register()
    {
        spl_autoload_register('\hymie\Loader::loadClass', true, true);
    }

    /**
     * 如果不是 composer 安装，需要使用这个方法加载框架类
     *
     * @param string $class
     * @return boolean
     */
    public static function loadHymieClasses($class)
    {
        $classFile = self::$hymieRoot . DIRECTORY_SEPARATOR . strtr($class, '\\', DIRECTORY_SEPARATOR) . '.php';
        if (file_exists($classFile)) {
            include_file($classFile);
            return true;
        }

        return false;
    }

    /**
     * 如果不是 composer 安装，则需要手工调用这个方法注册框架类加载器
     *
     * @return void
     */
    public static function registerHymieClasses()
    {
        self::$hymieRoot = dirname(__DIR__);
        spl_autoload_register('\hymie\Loader::loadHymieClasses', true, true);
    }
}

if (!function_exists('include_file')) {
    /**
     * include php 文件，单独的函数是为了避免被包含文件中获取 $this 对象
     * 
     * @param string $file 文件名
     * @return void
     */
    function include_file($file)
    {
        include $file;
    }
}
