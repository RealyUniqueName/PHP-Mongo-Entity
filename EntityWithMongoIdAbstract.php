<?php


/**  
* Entities with IDs of \MongoId
*/
abstract class EntityWithMongoIdAbstract extends EntityAbstract{

    /**
    * Convert string representation of id to MongoId.
    * Does nothing if parameter is already MongoId instance
    *
    */
    public static function parseId($id){
        return (
            $id instanceof \MongoId
                ? $id
                : new \MongoId($id)
        );
    }//function parseId()


    /**
    * Getter _id. Returns string representation of \MongoId
    *
    */
    public function _get_id($current){
        #if _id is not set, create it
        if( is_null($current) ){
            $current   = self::parseId(null);
            $this->_id = $current;
        }

        return (string)$current;
    }//function _get_id()


    /**
    * Setter _id
    *
    */
    public function _set_id($current, $new){
        return self::parseId($new);
    }//function _set_id()


    /**
    * Getter MongoId instance. Virtual property
    *
    */
    public function _getRawId($current){
        return $this->getRawProperty('_id');
    }//function _getRawId()


    /**
    * Setter for MongoId instance. Virtual property
    *
    */
    public function _setRawId($current, $new){
        trigger_error("Setting rawId is not allowed", E_USER_ERROR);
    }//function _setRawId()
}//class EntityWithMongoIdAbstract
