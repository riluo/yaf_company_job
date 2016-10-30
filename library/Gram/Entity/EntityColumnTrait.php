<?php
namespace Gram\Entity;

use Gram\Utility\Collection\CollectionBase;

/**
 * Class EntityTrait
 * @package Gram\Entity
 */
trait EntityColumnTrait
{
    static protected $_columns = [];



    public static function initColumns(){

    }


    /**
     * @param $columnName
     * @param string $columnType
     * @return EntityColumn
     */
    protected static function col($columnName,$columnType = 'string'){
        if(!isset( self::$_columns[$columnName])){
            self::$_columns[$columnName] = new EntityColumn($columnName,$columnType);
        }
        return self::$_columns[$columnName] ;
    }

    public static function cols($define,$keys,$instance=null){
        foreach($keys as $key){
            if(!isset( self::$_columns[$key])){
                self::$_columns[$key] = new EntityColumn($key);
            }
            self::$_columns[$key]->$define($instance);
        }
    }


    /**
     *
     */
    function validate()
    {
        foreach(self::$_columns as $column){
            /** @var EntityColumn  $column */
            $column->validate($this);
        }
    }


}