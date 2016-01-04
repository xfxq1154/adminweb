<?php

/**
 * 调用Sapi工具类
 * @author
 */
class Sapi {

    static private $_host = SAPI_HOST;
    static private $_sourceid = SAPI_SOURCE_ID;
    static private $_last_error = NULL;
    static private $_timeout = 5; //接口超时时间 单位s秒 建议不超过5s

    public function __construct() {
        
    }

    public static function request($uri, $params = array(), $requestMethod = 'GET', $jsonDecode = true, $headers = array()) {
        $url = self::$_host . $uri;
        $params['sourceid'] = self::$_sourceid;
        $params['timestamp'] = time();
        $result = Curl::request($url, $params, $requestMethod, $jsonDecode, $headers, self::$_timeout);
        return self::tidyResult($result, $jsonDecode);
    }

    public static function tidyResult($result, $jsonDecode) {
        if ($result === false) {
            self::$_last_error = ['code' => 500, 'msg' => 'http error'];
            return false;
        }
        if (!$jsonDecode) {
            $result = json_decode($result, TRUE);
        }
        self::$_last_error = ['code' => $result['status_code'], 'msg' => $result['status_msg']];
        if (isset($result['status_code']) && $result['status_code'] == 0) {
            return isset($result['data']) ? $result['data'] : array();
        } else {
            return false;
        }
    }

    public static function getError() {
        return self::$_last_error;
    }

}
 