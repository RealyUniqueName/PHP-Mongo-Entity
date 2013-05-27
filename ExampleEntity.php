<?php

/**
* Example entity
*/
class ExampleEntity extends EntityAbstract{

    #use memcache with this prefix
    protected static $cachePrefix = 'ability_';

    #collection name for this entity
    protected static $_dbCollectionName = 'ExampleEntity';


    /**
    * Getter for virtual property. This property does not exist in database
    *
    */
    public function _getSomeProperty($current){
        #do some magic
        return round(mt_rand(100, 1000) / 2);
    }//function _getRow()


    /**
    * Setter for virtual property. Prevent setting this property to ensure it won't be saved to database
    *
    */
    public function _setSomeProperty($current, $new){
        trigger_error('someProperty can not be set', E_USER_ERROR);
    }//function _getRow()


    /**
    * Getter for friends list. Let's say in mongo we have this property with ["Andrew", "Alex", "Kate"]
    * Than you can add friends like this:
    *       $this->friends[] = "Peter";
    * Or remove like this:
    *       unset($this->friends[0]);
    * And these changes will be saved to mongo on .save()
    */
    public function &_getFriends(&$friends){
        #keep friends list not too long
        while( count($friends) > 10 ){
            unset($friends[10]);
        }

        return $friends;
    }//function _getFriends()

}//class ExampleEntity
