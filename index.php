<?php
error_reporting(7);
session_start();
/**
 * 指向public的上一级
 */
header("Content-type: text/html; charset=utf-8");
define("ROOT_PATH",  realpath(dirname(__FILE__) . '/'));
//设置报错级别
include_once dirname(ROOT_PATH).'/configs/admincenter.config.php';
switch (ENVIRONMENT){
    case 'develop':
        error_reporting(7);
        break;
    case 'produce':
        error_reporting(0);
        break;
}

try {
    $app  = new Yaf_Application(ROOT_PATH . "/conf/application.ini");
} catch (Exception $e) {
    echo 'Message: ' .$e->getMessage();
}
$app->bootstrap()->run();
