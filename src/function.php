<?PHP

use Webmozart\Assert\Assert;


if (!function_exists('get_type')) {
    /**
     * 获取类型.
     *
     * @param mixed $target 待获取类型的对象
     *
     * @return string
     */
    function get_type($target)
    {
        return is_object($target) ? \get_class($target) : \gettype($target);
    }
}


if (!function_exists('get_array_item')) {
    /**
     * 获取数组指定 key 的值，若对应 key 不存在或者值 empty ，则范湖默认值。
     * 默认值默认为 null。
     *
     * @param array      $array   目标数组
     * @param string/int $key     键
     * @param mixed      $default 默认值，默认为 null
     *
     * @return mixed
     */
    function get_array_item($array, $key, $default = null)
    {
        Assert::isArray($array, 'Parameter $array need an Array!');
        Assert::notEmpty($key, 'Parameter $key could not empty!');

        if (array_key_exists($key, $array)) {
            return (!empty($array[$key])) ? $array[$key] : $default;
        } else {
            return $default;
        }
    }
}

if (!function_exists('really_exists')) {
    /**
     * 键存在且值已经设置（is_set 返回 true).
     *
     * @param array  $array 查找的数组
     * @param string $key   键名
     *
     * @return bool
     */
    function really_exists($array, $key)
    {
        if (!is_array($array) || is_null($key)) {
            return false;
        }

        if (!array_key_exists($key, $array) || !isset($array[$key])) {
            return false;
        }

        return true;
    }
}
