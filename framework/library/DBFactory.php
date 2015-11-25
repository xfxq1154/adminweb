<?php
/**
 * 
 * 数据库工厂类
 * @author ellis
 */
class DBFactory {

    static $db = array();

    /**
     * 
     * @param type $name
     * @return PDO
     */
    public static function factory($name) {

        if (!isset(self::$db[$name])) {

            $c = Application::app()->getConfig()->database->$name;

            if (empty($c)) {
                return null;
            }

            $charset = (isset($c->charset) && $c->charset != null) ? $c->charset : 'UTF8';

            $dsn = sprintf("%s:dbname=%s;host=%s;port=%s"
                    , $c->driver, $c->dbname, self::choiceHost($c->host), $c->port);            

            $instance = new PDO($dsn, $c->username, $c->password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '{$charset}'"));
            $instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$db[$name] = $instance;
        }
        return self::$db[$name];
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