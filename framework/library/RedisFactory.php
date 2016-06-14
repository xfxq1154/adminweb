<?php
/**
 * Redis工厂类
 */
class RedisFactory {

    static $store = array();

    public static function factory($name) {

        if (!isset(self::$store[$name])) {

            $c = Application::app()->getConfig()->redis->$name;

            if (empty($c)) {
                return null;
            }
            $instance = new Redis();

            if ($instance->connect($c->host, $c->port) == false) {
                return null;
            }

            if (isset($c->passwd) && $instance->auth($c->passwd) === false ) {
                return null;
            }

            self::$store[$name] = $instance;
        }
        return self::$store[$name];
    }

    private static function choiceHost($host) {
        $hosts = explode('|', $host);
        $noOfHost = sizeof($hosts);
        if ($noOfHost > 1) {
            return $hosts[time() % $noOfHost];
        }
        return $host;
    }
}

?>