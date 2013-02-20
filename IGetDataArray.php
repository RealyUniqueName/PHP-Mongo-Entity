<?php

/**
* Interface to get all object properties (recursively) as an array
*/
interface IGetDataArray {

    /**
    * Return array of object properties (recurively converts objects to arrays)
    *
    * @return array
    */
    public function getDataArray();


    /**
    * Get DocObject instance with properties of this object
    *
    * @return StdClass
    */
    public function data();


}//class IGetDataArray