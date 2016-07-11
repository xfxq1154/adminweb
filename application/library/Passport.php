<?php

/**
 * 调用passport类
 */
class Passport {

    static private $_host = PASSPORT_HOST;
    static private $_syscode = PASSPORT_SYSCODE;
    static private $_last_error = NULL;
    static private $_timeout = 3; //接口超时时间 单位s秒 建议不超过3s

    public static function request($uri, $params = array(), $requestMethod = 'GET', $jsonDecode = true, $headers = array()) {
        $url = self::$_host . $uri;
        $params['sys_code'] = self::$_syscode;
        $result = Curl::request($url, $params, $requestMethod, $jsonDecode, $headers, self::$_timeout);
        return self::tidyResult($result, $jsonDecode);
    }

    public static function tidyResult($result, $jsonDecode) {
        if ($result === false) {
            self::$_last_error = ['code' => 500, 'msg' => Curl::get_request_error()];
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

    public static function getErrorMessage(){
        return Error_Passport::getMessage(self::$_last_error['code']);
    }
    
}