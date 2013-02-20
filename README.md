PHP-Mongo-Entity
=========

* Easy way to map your entities to Mongo.
* Getters/setters system, which simplifies getters and setters using:

```php
$obj->counter ++;
//instead of 
$obj->setCounter( $obj->getCounter() + 1 );

$obj->arrayProperty[1] = 10;
$obj->arrayProperty[]  = 22;
//instead of
$arrayProperty = $obj->getArrayProperty();
$arrayProperty[1] = 10;
$arrayProperty[]  = 22;
$obj->setArrayProperty( $arrayProperty );
```

MIT licence.