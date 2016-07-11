<?php

/**
 * @name Error_Sapi
 * @author yanbo
 */
class Error_Sapi {
    static public $error_code = [
        //系统错误码
        '10006' => '参数无效',
        '10007' => '参数缺失',

        //店铺相关
        '40001' => '店铺名称已存在!',
        '40002' => '该用户已创建过店铺,请直接登录.'

    ];

    static public function getMessage($code){
        if(!isset(self::$error_code[$code])){
            return '系统错误';
        }
        return self::$error_code[$code];
    }
}

