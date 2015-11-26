<?php
/**
 * Description of Redis
 * @author ellis
 */
class Redis extends Redis {

    /**
     * 获得redis中所有定义的key
     */
    public function getAllKeys() {
        $res = $this->Keys('*');
        return $res;
    }

    /**
     * 设置一个数组
     * @param type $key
     * @param type $array
     * @param type $timeout seconds
     */
    public function setArray($key, $array, $timeout = 0) {

        $r = $this->set($key, serialize($array));

        if ($timeout > 0) {
            $this->expireAt($key, time() + $timeout);
        }

        return $r;
    }

    /**
     * 获得一个数组
     * @param type $key
     */
    public function getArray($key) {
        return unserialize($this->get($key));
    }

    /**
     * 从hash中获得key下所有数组
     */
    public function hGetAllArray($key) {
        return unserialize($this->hGetAll($key));
    }

    /**
     * 返回 key 指定的哈希集中所有字段的名字。
     */
    public function getKeys($key) {
        return $this->hKeys($key);
    }

    /**
     * 从hash中获得key下某hashkey数组
     * @param string $key, 类似于对象的name
     * @param string|int $hashKey   类似于对象中的键
     */
    public function hGetArray($key, $hashKey) {
        $res = unserialize($this->hGet($key, $hashKey));
        // 是否过期
        if (intval($res['_expire_time']) > 0 && $res['_expire_time'] < time()) {
            $this->hDel($key, $hashKey);
            return array();
        }
        unset($res['_expire_time']);
        return $res;
    }

    /**
     * 设置 key 指定的哈希集中指定字段的值
     * @param string $key
     * @param int|string $field 哈希集中的字段
     * @param array $array 数据数组
     * @param int $expire 存活期,从当前时间开始,秒为单位, -1为永久存在(默认)
     */
    public function hSetArray($key, $field, $array, $expire = -1) {
        if ($expire > 0) {
            // 记录存入hash中的时间,作为失效判断
            $array['_expire_time'] = time() + $expire;
        }
        return $this->hSet($key, $field, serialize($array));
    }

    /**
     * 批量设置hash数组
     * @param type $key
     * @param int $expire 存活期,从当前时间开始,秒为单位, -1为永久存在(默认)
     */
    public function hMSetArray($key, $array, $expire = -1) {
        if ($expire > 0) {
            // 记录存入hash中的时间,作为失效判断
            $_expire_time = time() + $expire;
        }

        $serializeArray = array();
        foreach ($array as $k => $a) {
            if (isset($_expire_time))
                $a['_expire_time'] = $_expire_time;

            $serializeArray[$k] = serialize($a);
        }
        return $this->hMSet($key, $serializeArray);
    }

    /**
     * 批量返回 key 指定的hash集中指定fields的值。
     * @param type $key
     */
    public function hMGetArray($key, $keyArray) {

        $array = $this->hMGet($key, $keyArray);

        $unserializeArray = array();
        foreach ($array as $k => $a) {
            $a = unserialize($a);
            // 是否过期
            if (intval($a['_expire_time']) > 0 && $a['_expire_time'] < time()) {
                $this->hDel($key, $k);
                $a = false;
            }
            $unserializeArray[$k] = $a;
        }

        return $unserializeArray;
    }

}
