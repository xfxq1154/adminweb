<?php
/**
 * Redis工厂类
 * @author ellis
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
            $instance->connect($c->host, $c->port );           
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