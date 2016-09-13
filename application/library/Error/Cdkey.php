<?php
/**
 * @name: Cdkey.php
 * @time: 2016-09-08 下午6:04
 * @author: liuxuefeng
 * @desc:
 */


class Error_Cdkey{

    static public $error_code = [
        //系统错误码
        '0' => '执行成功',
    ];

    static public function getMessage($code){
        if(!isset(self::$error_code[$code])){
            return '系统未知错误';
        }
        return self::$error_code[$code];
    }
}