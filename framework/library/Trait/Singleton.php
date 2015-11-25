<?php
trait Trait_Singleton {
    /**
     * private construct, generally defined by using class
     */
    //private function __construct() {}
   
    public static function getInstance() {
        static $_instance = NULL;
        $class = __CLASS__;
        return $_instance ?: $_instance = new $class;
    }
   
    public function __clone() {
        trigger_error('Cloning '.__CLASS__.' is not allowed.',E_USER_ERROR);
    }
   
    public function __wakeup() {
        trigger_error('Unserializing '.__CLASS__.' is not allowed.',E_USER_ERROR);
    }
}