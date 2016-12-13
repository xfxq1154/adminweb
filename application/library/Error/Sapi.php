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
        '10008' => '数据库查询异常,可能是渠道号已经存在',

        //店铺相关
        '40001' => '店铺名称已存在!',
        '40002' => '该用户已创建过店铺,请直接登录.',

        //渠道相关
        '190001'    => '分成比例过长',
        '190002'    => '结算单位过长',

    ];

    static public function getMessage($code){
        if(!isset(self::$error_code[$code])){
            return "系统未知错误[$code]";
        }
        return self::$error_code[$code];
    }
}

