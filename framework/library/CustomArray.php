<?php
/**
 * 数组工具类
 * @author ellis
 */
class CustomArray {

    /**
     * 给数组的所有key加上前缀
     * @param type $array
     * @param type $prefix
     */
    public static function addKeyPrefix($array, $prefix) {
        $arr = [];

        foreach ($array as $k => $a) {
            $arr[$prefix . $k] = $a;
        }

        return $arr;
    }

    /**
     * 给数组的所有key去掉前缀
     * @param type $array
     * @param type $prefix
     */
    public static function removeKeyPrefix($array, $prefix) {
        $arr = [];
        $len = strlen($prefix);
        foreach ($array as $k => $a) {

            $k = substr($k, $len);
    
            $arr[$k] = $a;
        }

        return $arr;
    }

}
