<?php


/**
* Abstract for entities
*
*/
abstract class EntityAbstract extends HelperAbstract{

/**
* :TODO:
* Rewrite methods for random objects requesting from DB
*/

/**
* :INFO:
* 1. In children classes mongo collection name must be defined in static::$_dbCollectionName.
* 2. To get instances from DB, use static::getInstance($criteria), where $criteria - mongo query, (see docs for MongoCollection::findOne())
* 3. When document from mongo is received, it is passed to entity constructor and will be stored as internal data.
* 4. If `_autosave` is enabled, internal data will be saved to mongo on __destruct()
*    To enable autosave, use ->autosave(true)
*    To enable _autosave for all entity instances set default value for $_autosave proeprty
*/

    #set this to any string to enable Memcached caching
    protected static $cachePrefix = false;

    #String with collection name for entity
    protected static $_dbCollectionName;
    #MongoCollection instances for all entities classes
    private static $_dbCollections = array();

    #Autosave on object destruction?
    protected $_autosave = false;


    /**
    * Get collection instance
    *
    */
    final public static function getDbCollection(){

        #If collection instance exists in cache
        if(
            isset(self::$_dbCollections[static::$_dbCollectionName])
            && self::$_dbCollections[static::$_dbCollectionName] instanceof \MongoCollection
        ){
            return self::$_dbCollections[static::$_dbCollectionName];
        }

        if( !static::$_dbCollectionName ){
            trigger_error('static property $_dbCollectionName should be set in the entity class');
        }

        $collection = static::$_dbCollectionName;
        self::$_dbCollections[static::$_dbCollectionName] = \lib\Db::$collection();

        return self::$_dbCollections[static::$_dbCollectionName];
    }//function getDbCollection()


    /**
    * Get entity instance
    *
    * @param array $criteriaOrData - mongo query to search document in DB or already received data for instance creation
    * @param bool $preparedData - if true, than $criteriaOrData is treated as entity data, and we will not perfrom query to MongoDB
    *
    * @return EntityAbstract
    */
    public static function getInstance($criteriaOrData, $preparedData = false){
        if( !$preparedData ){
            #Receive document from Mongo
            $criteriaOrData = static::getDbCollection()->findOne($criteriaOrData);
            if( !$criteriaOrData ){
                return null;
            }
        }

        #get classname for entity
        #you can change entity class by overriding getClassFor() method
        $useClass = static::getClassFor($criteriaOrData);

        if( $useClass ){
            $inst = new $useClass( $criteriaOrData );

        }else{
            $inst = new static( $criteriaOrData );
        }

        return $inst;
    }//function getClassInstance()


    /**
    * Override this method to change class for new entity instances. 
    * E.g. Fuit::getInstance() can return Apple instances etc.
    *
    * @param array|object $data - data, wich will be used to create entity instance
    *
    * @return string - fully-qualified class name. Return null to keep class as is.
    */
    public static function getClassFor($data){
        return null;
    }//function getClassFor()


    /**
    * To be overriden. Generate correct id required for entity.
    *
    * @param mixed $id
    */
    public static function parseId($id){
        return $id;
    }//function parseId()


    /**
    * Run ::parseId() for each array element
    *
    * @param array $idList
    *
    * @return array
    */
    public static function parseIdArray($idList){
        $cIds = count($idList);

        for($i = 0; $i < $cIds; $i++){
            $idList[$i] = static::parseId($idList[$i]);
        }

        return $idList;
    }//function parseIdArray()


    /**
    * Get entity instance by id.
    *
    * @param mixed $id
    */
    public static function getById($id){
        #If caching is enabled, search in cache first
        if( static::$cachePrefix ){
            $obj = Memc::get(static::$cachePrefix . $id);

            #if instance was not found in cache, query MongoDB
            if( !$obj ){
                $obj = static::getInstance( array('_id' => static::parseId($id)) );

                #object found, cache it
                if( $obj ){
                    Memc::set(static::$cachePrefix . $id, $obj);
                }
            }else{
                $obj->_autosave = false;
            }

            return $obj;

        #If caching is disabled, query object from Mongo
        }else{
            return static::getInstance( array('_id' => static::parseId($id)) );
        }
    }//function getById()


    /**
    * Get batch of entity instances
    *
    * @param array $criteria - mongo query
    * @param int $limit - maximum amount of instances returned
    * @param bool $asObjects - create entity instances or return as arrays
    * @param array $sort - sorting parameters for query
    * @param int $skip - amount of documents to skip for query
    *
    * @return array<EntityAbstract>
    */
    public static function getBatch($criteria, $limit = null, $asObjects = true, $sort = null, $skip = null){
        #get mongo cursor
        $cursor = static::getDbCollection()->find($criteria);

        if( $sort ){
            $cursor->sort($sort);
        }

        if( !is_null($skip) ){
            $cursor->skip($skip);
        }

        if( $limit ){
            $cursor->limit($limit);
        }

        #if objects are not required, return arrays
        if(!$asObjects){
            return iterator_to_array($cursor, false);
        }

        $instances = array();

        #create objects
        foreach ($cursor as $data){

            $inst        = static::getInstance($data, true);
            $instances[] = $inst;
        }

        return $instances;
    }//function getBatch()


    /**
    * Get batch of random entity instances
    *
    * @param int $quantity - batch size
    * @param array $criteria - mongo query
    * @param bool $unique - is it forbidden to return the same objects in batch?
    * @param int $limit - limit of documents collection to select random ones from
    * @param array $sort - to sort documents before selecting random ones
    * @param bool $asObjects - create entity instances or return as arrays
    *
    * @return array
    */
    public static function getRandomBatch($quantity, $criteria, $unique = false, $limit = null, $sort = null, $asObjects = true){
        $cursor = static::getDbCollection()->find(
            $criteria,
            array('_id')
        );

        if( $sort ){
            $cursor->sort($sort);
        }

        if( $limit ){
            $cursor->limit($limit);
        }

        $data = iterator_to_array($cursor, false);
        $cIds = count($data);

        if( $cIds == 0 ){
            return null;
        }

        #choose random id's
        $result = array();
        while( $quantity > 0 && $cIds > 0 ){
            $idx = mt_rand(0, $cIds-1);
            $result[] = $data[$idx]['_id'];

            #no duplicates allowed
            if( $unique ){
                array_splice($data, $idx, 1);
                $cIds = count($data);
            }

            $quantity--;
        }

        #get selected documents{
            #unique
            if( $unique ){
                return static::getBatch( array('_id' => array('$in' => $result)), null, $asObjects );
            }
    
            #non unique            
            $data = static::getBatch( array('_id' => array('$in' => $result)), null, $asObjects );
            foreach ($result as $idx => $id) {
                foreach ($data as $obj) {
                    if( $id == $obj->_id ){
                        $result[$idx] = $obj;
                    }
                }
            }
        #}

        return $result;
    }//function getRandomBatch()


    /**
    * Request one random instance
    *
    */
    public static function getRandom($criteria, $limit = null, $sort = null, $asObjects = true){
        $objects = static::getRandomBatch(1, $criteria, false, $limit, $sort, $asObjects);

        return ( !$objects ? null : $objects[0] );
    }//function getRandom()


    /**
    * Delete document from mongo by _id
    *
    */
    public static function delete($id, $options = array()){
        $result = (bool)static::getDbCollection()->remove(array( '_id' => static::parseId($id) ), $options);

        #If document was deleted and caching is enabled, remove document cache
        if( $result && static::$cachePrefix ){
            Memc::delete(static::$cachePrefix . $id);
        }

        return $result;
    }//function delete()



    /**
    * Save instance to mongo
    *
    * @param array $options - see doc for MongoCollection::save()
    * @param array|object $setObject - if this parameter is passed, use update({$set => $setObject}) instead of save()
    *
    * @return null|bool|array - null - if data was not changed (so there was no need to send it to mongo),
    *                           bool|array - see docs for MongoCollection::insert()
    */
    public function save( $options = array('secure' => true), $setObject = null ){
        #if need update() instead of save()
        if( $setObject && !is_scalar($setObject) ){
            $options['upsert']   = false;
            $options['multiple'] = false;

            $saveResult = static::getDbCollection()->update(
                array(
                    '_id' => static::parseId($this->_id)
                ),
                array(
                    '$set' => $setObject
                ),
                $options
            );

        #run save
        }else{

            $data = $this->data();

            #if _id is set, just save data
            if( isset($data->_id) ){
                $saveResult = static::getDbCollection()->save($data, $options);

            #otherwise save data and set _id after
            }else{
                #сохраняем
                $saveResult = static::getDbCollection()->save($data, $options);
                #if mongo created _id, run _id setter to ensure, instance will get correct _id
                if( isset($data->_id) ){
                    $this->_id = $data->_id;
                }
            }

            #update cache if caching is enabled
            if( static::$cachePrefix ){
                Memc::set(static::$cachePrefix . $this->_id, $this);
            }
        }

        return $saveResult;
    }//function save()


    /**
    * On/off autosave
    *
    * @param bool $enable - true:on, false:off
    */
    public function autosave($enable = true){
        $this->_autosave = $enable;
    }//function autosave()


    /**
    * Destructor. Send data to Mongo if autosave is enabled.
    *
    */
    public function __destruct(){
        if( $this->_autosave ){
            $this->save( array('secure' => false) );
        }
    }//function __destruct()


    /**
    * Entity name getter. Virtual property (no representation in internal data)
    *
    */
    public function _getEntity($current){
        $cls = get_class($this);
        if( $cls !== false ){
            $parts = explode('\\', $cls);
            $cls = $parts[ count($parts) - 1 ];
        }

        return $cls;
    }//function _getEntity()


    /**
    * Forbid setting entity name
    *
    */
    public function _setEntity($current, $new){
        trigger_error('Setting entity is not allowed', E_USER_ERROR);
    }//function _setEntity()


}//class EntityAbstract
