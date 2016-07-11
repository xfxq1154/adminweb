<?php

/**
 * @name Error_Passport
 * @author yanbo
 * @desc passport错误码与pc错误码对应关系
 */
class Error_Passport {
    static public $error_code = [
        //系统错误码
        '10001' => '参数缺失',
        //user
        '10011' => '参数不合法',
        '10012' => '该用户已绑定手机号',
        '10014' => '密码修改失败',
        '10015' => '用户不存在',
        '10016' => '密码错误',
        '10019' => '手机号已存在',
    ];
    
    static public function getMessage($code){
        if(!isset(self::$error_code[$code])){
            return '系统未知错误';
        }
        return self::$error_code[$code];
    }
}

