<?php
/**
 * @name: Kf.php
 * @time: 2016-08-25 下午2:11
 * @author: liuxuefeng
 * @desc:客服错误代码
 */

class Error_Kf {

    static public $error_code = [
        //系统错误码
        '0' => '执行成功',
        //user
        '10001' => '必填参数缺失',
        '10002' => '参数不符合要求',
        '20005' => '账户已经存在',
        '20006' => '账户不存在',
    ];

    static public function getMessage($code){
        if(!isset(self::$error_code[$code])){
            return '系统未知错误';
        }
        return self::$error_code[$code];
    }
}