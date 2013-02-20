<?php

/**
* Array <-> Object conversion
*
*/
class DocObject implements  \ArrayAccess,
                            \Countable,
                            IGetDataArray
{
    /**
    * :INFO:
    * 1. Recursively converts arrays to objects
    * 2. If requested property does not exist, returns NULL
    * 3. No own properties is allowed for this class (neither public nor private)
    */


    /**
    * Convert arrays to objects recursively. But numeric arrays stay arrays.
    *
    */
    public static function toObjectRecursive( $data ){
        $keys   = array_keys($data);
        $cKeys  = count($keys);

        if( !$cKeys ) return array();

        $assoc = true;

        #Check array keys to ensure this is numeric array
        if( is_int($keys[0]) ){
            $assoc  = false;

            #Check every key
            for($i = 0; $i < $cKeys; $i++){
                if( !is_int($keys[$i]) ){
                    $assoc = true;
                    break;
                }
            }
        }//if(first key is numeric)

        #Convert associative arrays to objects
        if( $assoc ){
            return new self($data);

        #For numeric arrays process every element
        }else{

            for($i = 0; $i < $cKeys; $i++){

                #If element is array or object, convert it {
                    #array
                    if( is_array($data[ $keys[$i] ]) ){
                        $data[ $keys[$i] ] = self::toObjectRecursive($data[ $keys[$i] ]);
                    #object
                    }elseif( is_object($data[ $keys[$i] ]) ){
                        $data[ $keys[$i] ] = new self($data[ $keys[$i] ]);
                    }
                #}
            }//for()

            return $data;
        }//if($assoc)...else
    }//function toObjectRecursive()


    /**
    * Convert object ot array recursively
    *
    */
    public static function toArrayRecursive($obj){
        if( is_null($obj) || is_scalar($obj) ){
            return $obj;
        }

        $data = array();

        foreach($obj as $property => $value){
            $data[$property] = self::toArrayRecursive($value);
        }

        return $data;
    }//function objToArray()


    /**
    * Constructor. Set instance properties from passed array/object
    *
    * @param array|object $data - data source for instance properties
    *
    */
    public function __construct($data = null){
        #if data is object
        if( is_object($data) ){
            $data = (array)$data;
        }

        if( is_array($data) ){

            $properties  = array_keys($data);
            $cProperties = count($properties);

            for($i = 0; $i < $cProperties; $i++ ){

                #process arrays too, since their elements may be objects
                if( is_array($data[ $properties[$i] ]) ){

                    $this->{$properties[$i]} = self::toObjectRecursive($data[ $properties[$i] ]);

                }else{
                    $this->{$properties[$i]} = $data[ $properties[$i] ];
                }
            }//for(properties)
        }//if()
    }//function __construct()


    /**
    * Return null for non-existent properties
    *
    */
    public function __get($property){
        return null;
    }//function __get()


    /**
    * IGetDataArray. Create array from this object
    *
    */
    public function getDataArray(){
        return self::toArrayRecursive($this);
    }//function getDataArray()


    /**
    * IGetDataArray.
    *
    */
    public function data(){
        return $this;
    }//function data()


    /**
    * ArrayAccess. Check offset exists
    *
    */
    public function offsetExists($offset){
        return is_null($this->$offset) ? false : true;
    }//function offsetExists()


    /**
    * ArrayAccess. Get value by offset
    *
    */
    public function &offsetGet($offset){
        if( !is_null($this->$offset) ){
            $value = &$this->$offset;
        }else{
            $value = null;
        }
        return $value;
    }//function offsetGet()


    /**
    * ArrayAccess. Set value by offset
    *
    */
    public function offsetSet($offset, $value){
        return $this->$offset = $value;
    }//function offsetSet()


    /**
    * ArrayAccess. Remove element
    *
    */
    public function offsetUnset($offset){
        unset($this->$offset);
    }//function offsetExists()


    /**
    * Countable. Count elements
    *
    */
    public function count(){
        return count((array)$this);
    }//function count()


}//class DocObject
