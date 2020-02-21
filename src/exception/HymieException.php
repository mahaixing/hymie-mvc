<?PHP
/**
 * Project:       Hymie PHP MVC framework
 * File:          HimiException.php
 * Created Date:  2019-08-11
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
 * 框架只会抛出这个异常以及这个异常的子类
 */
class hymieException extends \Exception
{

    public function __construct($msg, $code = 0, $previous = null)
    {
        parent::__construct($msg, 0, $previous);
    }
}
