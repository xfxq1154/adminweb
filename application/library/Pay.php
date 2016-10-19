<?php

/**
 * 调用支付平台类
 */
class Pay {

    static private $_host = PAYMENT_HOST;
    static private $_last_error = NULL;
    static private $_timeout = 3; //接口超时时间 单位s秒 建议不超过3s

    public static function request($uri, $params = array(), $requestMethod = 'GET', $jsonDecode = true, $headers = array()) {
        $url = self::$_host . $uri;
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
        self::$_last_error = ['code' => 0, 'msg' => 'ok'];
        return $result;
    }

    public static function getError() {
        return self::$_last_error;
    }

    public static function getErrorMessage(){
        return Error_Passport::getMessage(self::$_last_error['code']);
    }
    
}