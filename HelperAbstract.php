<?php


/**
* Core class. All database-related data is saved in internal private property.
* Acces to that data is implemented via getters-setter.
*
* See comments for details.
*/
abstract class HelperAbstract implements    \ArrayAccess,
                                            \Countable,
                                            IGetDataArray
{

/**
* :INFO:
*   Getter must start with chars defined by self::$_getterPrefix, setters - self::$_setterPrefix.
*   First argument for getter - current property value.
*   First argument for setter - current property value, second argument - new value.
*   Setter must return value, wich will be assigned to property finally.
*   See EntityAbstract.php for examples
*/

/**
* :WARNING:
*   Since PHP returns copies of arrays, getters for arrays must take and return values by reference!
*   Such behavior will ensure, internal data (data to save in db) will be modified.
*/

    #Prefixes for getters and setters
    private static $_getterPrefix = '_get';
    private static $_setterPrefix = '_set';

    #Data to read from and save to DB
    private $_data;


    /**
    * Constructor
    *
    */
    public function __construct($data = null){
        $this->_data  = ( $data instanceof DocObject ? $data : new DocObject($data) );
    }//function __construct()


    /**
    * IGetDataArray. Get DocObject instance with properties of this object.
    *
    */
    public function data(){
        $data = new DocObject();

        #Convert HelperAbstract instances to StdClass
        foreach($this->_data as $property => $value){
            $data->$property = (
                $value instanceof HelperAbstract
                    ? $this->_data->$property->data()
                    : $this->_data->$property
            );
        }

        return $data;
    }//function data()


    /**
    * Getters processing
    *
    */
    public function &__get($property){
        /**
        * :NOTICE:
        * Getters get current property value as first parameter
        *
        */

        #if getter is defined for this property
        if( method_exists($this, self::$_getterPrefix . $property) ){
            $value = &$this->{self::$_getterPrefix . $property}( $this->_data->$property );
            return $value;

        #if there is no getter for property, return property itself
        }else{

            if( isset($this->_data->$property) ){
                #No setter, no getter. Create direct link to this property to access it faster next time.                
                #also check this is not real property of this class
                if(
                    !method_exists($this, self::$_setterPrefix . $property)
                    && !isset($this->$property)
                ){
                    $this->$property = &$this->_data->$property;
                    return $this->$property;

                #otherwise just return a value
                }else{
                    #return arrays by reference
                    if( is_array($this->_data->$property) ){
                        return $this->_data->$property;
                    #all other properties return by copy to ensure, setter will get correct current value
                    #in case of `+=`-like operations (without copying setter will get result
                    #of += operation instead of real current valued ue to internal php optimisations )
                    }else{
                        $value = $this->_data->$property;
                        return $value;
                    }
                }

            #to make getters able to return values by reference (null cannot be referenced)
            }else{
                $value = null;
                return $value;
            }
        }
    }//function __get()


    /**
    * Setters processing
    *
    */
    public function __set($property, $newValue){
        /**
        * :NOTICE:
        * Setter get current value as first parameter and new value as second parameter.        
        * Setter must return value, wich actually will be stored in property. 
        */

        #Convert assoc arrays to objects
        if( is_array($newValue) ){
            $newValue = DocObject::toObjectRecursive($newValue);
        }

        #if there is setter defined for this property
        if( method_exists($this, self::$_setterPrefix . $property) ){

            $this->_data->$property = $this->{self::$_setterPrefix . $property}( $this->_data->$property, $newValue );

        #no setter, return property
        }else{

            $this->_data->$property = $newValue;

            #no setter, no getter. Set fast access to this property
            if(
                !method_exists($this, self::$_getterPrefix . $property)
                && !isset($this->$property)
            ){
                $this->$property = null;
                $this->$property = &$this->_data->$property;
            }
        }
    }//function __set()


    /**
    * Get property value bypassing getter
    *
    */
    public function getRawProperty($property){
        return $this->_data->$property;
    }//function getRawProperty()


    /**
    * Check property exists in internal data. Returns true even if property value is NULL
    */
    protected function _propertyExists($property){
        return property_exists($this->_data, $property);
    }//function _propertyExists()


    /**
    * Unset internal data property
    *
    */
    public function eraseProperty($property){
        unset($this->_data->{$property});
    }//function eraseProperty()


    /**
    * IGetDataArray. Returns assoc array with properties of this object (recursively converts objects to arrays)
    *
    */
    public function getDataArray(){
        #To make sure getters triggered{
            $data = array();

            foreach($this->_data as $property => $value){
                $data[$property] = $this->$property;

                if( is_object($data[$property]) && $data[$property] instanceof _interface\GetDataArray ){
                    $data[$property] = $data[$property]->getDataArray();

                }elseif( is_object($data[$property]) ){
                    $obj = new DocObject($data[$property]);
                    $data[$property] = $obj->getDataArray();
                }
            }//foreach()
        #}

        return $data;
    }//function getDataArray()


    /**
    * ArrayAccess. Check index exists
    *
    */
    public function offsetExists($offset){
        return !is_null($this->_data->$offset);
    }//function offsetExists()


    /**
    * ArrayAccess. Get value by index
    *
    */
    public function &offsetGet($offset){
        #проверим тип свойства. Если это не массив, то вернём через копию, чтобы обойти
        #ошибку "Indirect modification of overloaded property"
        if( is_array($this->_data->$offset) ){
            return $this->$offset;
        }else{
            $value = $this->$offset;
            return $value;
        }
    }//function offsetGet()


    /**
    * ArrayAccess. Set value by index
    *
    */
    public function offsetSet($offset, $value){
        return $this->$offset = $value;
    }//function offsetSet()


    /**
    * ArrayAccess. Remove index
    *
    */
    public function offsetUnset($offset){
        $this->eraseProperty($offset);
    }//function offsetExists()


    /**
    * Countable. Count internal data properties
    *
    */
    public function count(){
        return count($this->_data);
    }//function count()
}//class HelperAbstract