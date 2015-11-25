<?php
/**
 * 自动拆装箱工具
 * @author ellis
 */
class AutoBox {

    /**
     * 装箱
     * @param object $object
     * @param array $data
     */
    public static function box(&$object, $data) {

        if (!is_array($data)) {
            return false;
        }

        $rc = new ReflectionClass($object);

        foreach ($data as $k => $v) {
            if ($rc->hasProperty($k)) {
                $object->$k = $v;
            }
        }
    }

    /**
     * 拆箱
     * @param object $object
     * @param array $data
     */
    public static function unbox($object) {

        $array = json_decode(json_encode($object), true);

        return $array;
    }

    /**
     * 将数据库对象装箱
     * @param object $object
     * @param array $data 数据库数据
     * @param array $mappingTable 映射表
     */
    public static function boxDbData(&$object, $data, $mappingTable) {

        if (!is_array($data)) {
            return false;
        }

        $rc = new ReflectionClass($object);

        foreach ($data as $k => $v) {
            $var = $mappingTable[$k];

            if ($rc->hasProperty($var)) {
                $object->$var = $v;
            }
        }
    }

}
