<?php

/**
* Singleton to access \Memcached
*/
class Memc{


    //Singleton {
        protected static $_inst = null;

        public static function inst(){
            if( is_null(self::$_inst) ){
                self::$_inst = new \Memcached();
                self::$_inst->addServer("localhost", 11211);
            }
            return self::$_inst;
        }//function inst()
        protected function __construct(){}
        protected function __clone(){}
    //} Singleton


    /**
    * Access to singleton methods
    *
    */
    public static function __callStatic($method, $args){
        return call_user_func_array(array(self::inst(), $method), $args);
    }//function add()


}//class Memc