<?php

/**
 * Project:       Hymie PHP MVC framework
 * File:          Logger.php
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

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * 符合 Psr log 规范的 log 类，根据配置会使用 monolog 或者 Psr\Log\NullLogger。
 */
class Logger extends AbstractLogger
{
    /**
     * 具体的 loger 实例.
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger = null;

    /**
     * 构造函数.
     *
     * 读取 'logger' bean 如存在则使用否则使用 \psr\Log\NullLogger
     */
    public function __construct()
    {
        $config = get_config('logger');
        if (get_config('log_enable', false) === true && count($config) > 0) {
            $path = get_array_item($config, 'path', 'application.log');
            $maxFiles = get_array_item($config, 'max_files', 30);
            $level = get_array_item($config, 'level', LogLevel::INFO);
            $format = get_array_item($config, 'format', ["[%datetime%] %level_name% %channel%: %message% - %context% \n", 'Y-m-d H:i:s']);

            $formatter = new \Monolog\Formatter\LineFormatter($format[0], $format[1]);
            $handler = new \Monolog\Handler\RotatingFileHandler($path, $maxFiles, $level);
            $handler->setFormatter($formatter);

            $this->logger = new \Monolog\Logger(strtoupper($config['name']));
            $this->logger->pushHandler($handler);
        } else {
            $this->logger = new \Psr\Log\NullLogger();
        }
    }

    /**
     * log 方法，记录日志.
     *
     * @param string $level   日志等级
     * @param string $message 日志消息
     * @param array  $context 上下文数组
     */
    public function log($level, $message, array $context = array())
    {
        $this->logger->log($level, $message, $context);
    }

    /**
     * set log 实现类.
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * getter.
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
