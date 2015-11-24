<?php
error_reporting(7);
set_time_limit(0);
define('ROOT_PATH', dirname(__FILE__));

include dirname(ROOT_PATH) . '/configs/admincenter.config.php';
$application = new Yaf_Application(ROOT_PATH . "/conf/application.ini");
$request = new Yaf_Request_Simple();

if ($argc < 3) {
    echo "usage:/usr/local/php/bin/php " . __FILE__ . " controllerName actionName [param1,param2,...]" . PHP_EOL;
    $request->setControllerName('index');
    $request->setActionName('clidoc');
} else {
    array_shift($argv);
    $controllerName = array_shift($argv);
    $actionName = array_shift($argv);
    if (empty($actionName)) {
        $actionName = 'index';
    }
    $request->setControllerName($controllerName);
    $request->setActionName($actionName);
    $request->setParam('params', $argv);
}

$application->bootstrap()->getDispatcher()->dispatch($request);
