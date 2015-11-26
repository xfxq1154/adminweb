<?php
/**
 * Description of ApiFactory
 * API工厂类
 * @author ellis
 */
class ApiFactory {

    static $api = array();

    /**
     * 
     * @param string(url) $ApiName
     * @return ApiUrl
     */
    public static function factory($ApiName) {

        if (!isset(self::$api[$ApiName])) {
            $host = Yaf_Application::app()->getConfig()->api->$ApiName->host;
            if (empty($host)) {
                return null;
            }
            
            $api = new Api($host);

            self::$api[$ApiName] = $api;
        }
        return self::$api[$ApiName];
    }

}
