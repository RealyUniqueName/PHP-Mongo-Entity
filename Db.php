<?php

/**
* Mongo connection
*/
class Db extends \MongoDB{

    private static $_database;

    /**
    * Get mongo connection. Define DB_CONNECTION and DB_NAME constants 
    *
    * @return \MongoDB
    */
    public static function dbConn(){

        if( !self::$_database ){

            $conn = new \Mongo(DB_CONNECTION);
            self::$_database = new self($conn, DB_NAME);
        }

        return self::$_database;
    }//function dbConn()


    /**
    * Get MongoCollection
    *
    */
    public static function __callStatic($collection, $args){
        $db = self::dbConn();
        return $db->$collection;
    }//function __callStatic()


}//class Db
