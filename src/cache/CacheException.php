<?php
/**
 * Project:       Hymie PHP MVC framework
 * File:          CacheException.php
 * Created Date:  2019-08-23.
 *
 * Github:        https://github.com/mahaixing/hymie-mvc
 * Gitee:         https://gitee.com/mahaixing/hymie-mvc
 * Composer:      https://packagist.org/packages/hymie/mvc
 *
 * @author:       mahaixing(mahaixing@gmail.com)
 * @license:      MIT License
 */

namespace hymie\cache;

/**
 * 缓存异常对象
 */
class CacheException extends \hymie\exception\hymieException implements \Psr\Cache\CacheException
{
    public function __construct($msg, $code = 0, $previous = null)
    {
        parent::__construct($msg, 0, $previous);
    }
}
