<?php
/**
 * 本地化application用于非Yaf框架使用
 * @author ellis
 */
class Application {

    private static $app = null;
    private $config = null;
    private $rootPath;
    private $applicationPath;

    private function __construct($rootPath) {
        $this->rootPath = $rootPath;


        $this->initAutoLoad();
        $this->initConfig();
        $this->initApplicationAutoLoad();
        $this->initMVCAutoLoad();
    }

    public static function init($rootPath) {
        self::$app = new Application($rootPath);
    }

    private function initConfig() {

        $config = parse_ini_file($this->rootPath . '/conf/application.ini');

        $this->config = Convert::configs2Object($config);



        $this->applicationPath = $this->config->application->directory;
    }

    public function getConfig() {


        return $this->config;
    }

    private function initAutoLoad() {
        spl_autoload_register(function($className) {

            $path = '/library/' . str_replace('_', '/', $className) . '.php';


            if (file_exists($this->rootPath . '/framework' . $path)) {

                require_once $this->rootPath . '/framework' . $path;
            }
        });
    }

    private function initMVCAutoLoad() {
        spl_autoload_register(function($className) {

            $type = '';
            if (strpos($className, 'Model') > 0) {
                $type = 'Model';
            } elseif (strpos($className, 'Controller') > 0) {
                $type = 'Controller';
            }
            $filename = str_replace($type, '', $className);


            $path = "/{$type}s/{$filename}.php";

            if (file_exists($this->applicationPath . $path)) {

                require_once $this->applicationPath . $path;
            }
        });
    }

    private function initApplicationAutoLoad() {
        spl_autoload_register(function($className) {

            $path = '/library/' . str_replace('_', '/', $className) . '.php';


            if (file_exists($this->applicationPath . $path)) {
                require_once $this->applicationPath . $path;
            }
        });
    }

    /**
     * Yaf_Application
     */
    public static function app() {

        if (class_exists('Yaf_Application')) {



            return Yaf_Application::app();
        }


        return self::$app;
    }

}
