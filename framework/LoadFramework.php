<?php

/*
 * 使没有使用Yaf框架的项目可以引入framework类库
 */

require_once dirname(__FILE__).'/library/Lt/Application.php';

$root = dirname(dirname(__FILE__));

Application::init($root);