<?php

/**
 * @name Bootstrap
 * @author ellis
 * @desc 所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
 * @see http://www.php.net/manual/en/class.yaf-bootstrap-abstract.php
 * 这些方法, 都接受一个参数:Yaf_Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 */
class DdBootstrap extends Yaf_Bootstrap_Abstract {

    public function _initConfig() {
        //把配置保存起来
        $arrConfig = Application::app()->getConfig();
        Yaf_Registry::set('config', $arrConfig);
    }

    public function _initPlugin(Yaf_Dispatcher $dispatcher) {
        //注册一个插件
        $objSamplePlugin = new SamplePlugin();
        $dispatcher->registerPlugin($objSamplePlugin);
    }

    public function _initRoute(Yaf_Dispatcher $dispatcher) {
        //在这里注册自己的路由协议,默认使用简单路由
    }

    public function _initView(Yaf_Dispatcher $dispatcher) {
        //在这里注册自己的view控制器，例如smarty,firekylin
    }

    public function _initAutoLoad() {
        ini_set('yaf.use_spl_autoload', true); 
        spl_autoload_register(function($className) {
           
            $path = ROOT_PATH . '/application/library/' . str_replace('_', '/',$className) . '.php';         
             
            if (file_exists($path)) {

                require_once $path;
            }
        });
    }

}
