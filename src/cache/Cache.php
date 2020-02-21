<?php

/**
 * Project:       Hymie PHP MVC framework
 * File:          Cache.php
 * Created Date:  2019-08-11.
 *
 * Github:        https://github.com/mahaixing/hymie-mvc
 * Gitee:         https://gitee.com/mahaixing/hymie-mvc
 * Composer:      https://packagist.org/packages/hymie/mvc
 *
 * @author:       mahaixing(mahaixing@gmail.com)
 * @license:      MIT
 */

namespace hymie\cache;

/**
 * 缓存工厂，适配 Psr-6 Psr-16。
 * 默认寻找 bean 配置中名为 'cache' 的 bean 如未配置，则使用 \hymie\cache\ArrayCache (Psr-16).
 */
class Cache
{
    /**
     * bean 定义缓存的默认 bean 名字.
     */
    const DEFAULT_BAEN_NAME = 'cache';

    /**
     * 默认替换的缓存实现.
     *
     * @var string
     */
    public static $defaultCacheClass = '\hymie\cache\impl\ArrayCache';

    /**
     * 静态实例数组.
     *
     * @var array
     */
    private static $instances = [];

    /**
     * 获取缓存方法，供用户端使用.
     *
     * 默认每个缓存实例都是单例的（单个http请求），避免在单个请求中多次创造实例。
     *
     * @param string $beanNameOrClassName        要获取的缓存 bean 名或者类名
     * @param string $replaceBeanNameOrClassName 如果 bean 名或者类名不存在，则使用这个替换，默认是 ArrayCache
     * @param bool   $cleanable                  .clean_cache 文件存在的情况下，是否可以清除这个缓存，默认 false
     */
    public static function getInstance(
        $beanNameOrClassName = self::DEFAULT_BAEN_NAME,
        $replaceBeanNameOrClassName = null,
        $useBeanFactory = true,
        $cleanable = false
    ) {
        //如果已有实例则返回
        if (\array_key_exists($beanNameOrClassName, self::$instances)) {
            log_debug(
                sprintf("%s: found '%s' instance from static instance array, return it.", self::class, $beanNameOrClassName)
            );

            return self::$instances[$beanNameOrClassName];
        }

        $instance = null;
        // 如果替换缓存为提供，则默认使用 ArrayCache
        $replaceBeanNameOrClassName = ($replaceBeanNameOrClassName == null) ?
            self::$defaultCacheClass :
            $replaceBeanNameOrClassName;

        $instance = self::createInstance($beanNameOrClassName, $replaceBeanNameOrClassName, $useBeanFactory);

        $instance = $cleanable === true ? new \hymie\cache\impl\CleanableCache($instance) : $instance;
        // 实例保存实例数组
        self::$instances[$beanNameOrClassName] = $instance;

        return $instance;
    }

    /**
     * 构造缓存，如果不存在则替换.
     *
     * @param string $beanNameOrClassName        要获取的缓存 bean 名或者类名
     * @param string $replaceBeanNameOrClassName 如果 bean 名或者类名不存在，则使用这个替换，默认是 ArrayCache
     */
    private static function createInstance(
        $beanNameOrClassName,
        $replaceBeanNameOrClassName,
        $useBeanFactory
    ) {
        // 如果调试模式，则使用 ArrayCache
        if (get_config('debug', false) === true) {
            $instance = new self::$defaultCacheClass();
        } else {
            // 实例化缓存
            $instance = self::initializeCache($beanNameOrClassName, $useBeanFactory);
        }

        // 如果没有找到，则尝试实例化替代缓存
        if ($instance == null) {
            $instance = self::initializeCache($replaceBeanNameOrClassName, $useBeanFactory);
            log_warning(
                sprintf(
                    "%s: cache bean or class '%s' does not exist, try replacement bean or class '%s'.",
                    self::class,
                    $beanNameOrClassName,
                    $replaceBeanNameOrClassName
                )
            );
        }

        // 如果还没有，则使用 ArrayCache
        if ($instance == null) {
            $instance = new self::$defaultCacheClass();
            log_error(
                sprintf(
                    "%s: replacement cache bean or class '%s' does not exist, use default 'ArrayCache'",
                    self::class,
                    $replaceBeanNameOrClassName
                )
            );
        }

        // 统一执行一次 PSR-6 适配
        return Psr6Adapter::adapt($instance);
    }

    private static function initializeCache($beanNameOrClassname, $useBeanFactory)
    {
        $instance = null;
        try {
            if ($useBeanFactory == true) {
                $instance = get_bean($beanNameOrClassname);
            } else {
                if (class_exists($beanNameOrClassname)) {
                    $instance = new $beanNameOrClassname();
                }
            }
        } catch (\Exception $e) {
            log_error(sprintf("%s: create cache error '%s', use 'ArrayCache' instead.", self::class, $e->getMessage()));
        }

        return $instance;
    }

    /**
     * 由于 PSR-6 PSR-16 规范没有办法枚举所有 key，目前这个类只能清除某个缓存的所有 key。
     *
     * 下一步的初步想法是记录键名到单独的缓存，比如： 文件系统 或者 SQLite3 文件数据库中。
     * 再根据键名来清除缓存。
     */
    public static function cleanCaches()
    {
        if (!\file_exists(self::CLEAN_FILE)) {
            return;
        }

        foreach (self::$instances as $targetCache) {
            if ($targetCache instanceof \hymie\cache\impl\CleanableCache) {
                $targetCache->clear();
            }
        }

        @\unlink(self::CLEAN_FILE);
    }

    /**
     * 注册 shutdown 函数，在脚本执行完毕后清除缓存.
     */
    public static function registerCleaner()
    {
        \register_shutdown_function('\hymie\cache\Cache::cleanCaches');
    }
}
