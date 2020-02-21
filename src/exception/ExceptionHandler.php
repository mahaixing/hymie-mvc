<?php

/**
 * Project:       Hymie PHP MVC framework
 * File:          ExceptionHandler.php
 * Created Date:  2019-08-11.
 *
 * Github:        https://github.com/mahaixing/hymie-mvc
 * Gitee:         https://gitee.com/mahaixing/hymie-mvc
 * Composer:      https://packagist.org/packages/hymie/mvc
 *
 * @author:       mahaixing(mahaixing@gmail.com)
 * @license:      MIT
 */

namespace hymie\exception;

/**
 * 异常处理类，注册 php 系统异常、错误以及 shutdownhandler。
 *
 * 这个类在 Application.php 中调用，只会执行一次
 */
class ExceptionHandler
{
    /**
     * 注册错误、异常、shutdown 等 handler.
     *
     * debug 模式下使用 whoops.
     * 正式模式下使用当前类定义的方法。
     */
    public static function register()
    {
        if (get_config('debug', false) === true) {
            error_reporting(-1);
            ini_set('display_errors', 1);

            $whoops = new \Whoops\Run();
            $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
            $whoops->register();
        } else {
            ini_set('display_errors', 0);
            error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

            $handler = new self();
            set_error_handler(array($handler, 'errorHandler'));
            set_exception_handler(array($handler, 'exceptionHandler'));
            register_shutdown_function(array($handler, 'shutdownHandler'));
        }
    }

    /**
     * 非 debug 环境 错误处理 handler.
     *
     * 目前只记录日志以及输出 500 http header。
     *
     * @param int    $errno
     * @param stirng $errstr
     * @param string $errfile
     * @param int    $errline
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        log_error("Error: error_no '$errno', error_str '$errstr', error_file '$errfile', error_line '$errline' .");
        set_status_header(500);
        exit(1);
    }

    /**
     * 非 debug 环境下异常 handler.
     *
     * 目前只记录日志以及输出 500 http header。
     *
     * @param \Exception $exception
     */
    public function exceptionHandler($exception)
    {
        if ($exception instanceof \hymie\router\RouterException) {
            log_info($exception, [$exception]);
            set_status_header(404);
        } else {
            log_error($exception, [$exception]);
            set_status_header(500);
        }
        exit(1);
    }

    /**
     * shutdown handler.
     */
    public function shutdownHandler()
    {
        $last_error = error_get_last();
        if ($last_error != null) {
            log_error(sprintf(
                "Last Error: type '%s', message '%s', file '%s', line '%s'",
                $last_error['type'],
                $last_error['message'],
                $last_error['file'],
                $last_error['line']
            ), $last_error);
        }
    }
}
