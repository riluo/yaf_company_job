<?php
namespace Gram\Entity;

use Gram\Utility\Collection\CollectionBase;
use Respect\Validation\Validatable;
use Respect\Validation\Validator as V;
use ZuoYeah\Entity\Feedback;


/**
 * Class EntityTrait
 * @method static ints($keys)
 * @method static strings($keys)
 * @method static floats($keys)
 * @method static arrs($keys)
 * @method static ins($keys)
 * @method static instances($keys,$instance)
 * @method static needs($keys)
 * @method static emails($keys)
 * @method static mobiles($keys)
 * @method static lengths($keys)
 * @method static ids($keys)
 * @method static regexs($keys)
 * @method static ignores($keys)
 * @package Gram\Entity
 */
class EntityColumn
{
    public $_columnName = '';
    public $_columnType = 'string';
    public $_isPrimaryKey = false;
    public $_notEmpty = false;
    public $_maxLength = 99999;
    public $_minLength = 0;
    public $_max;
    public $_min;
    public $_email = false;
    public $_mobile = false;
    public $_regex = '';
    public $_instance = null;
    public $_in = [];

    public $_notDb = false;
    public $_toDbFunc = null;
    public $_fromDbFunc = null;

    const REGEX_MOBILE = '(\\+\\d+)?1[3458]\\d{9}$';

    const TYPE_STRING = 'string';
    const TYPE_INT = 'int';
    const TYPE_FLOAT = 'float';
    const TYPE_ARRAY = 'arr';

    public $types = [self::TYPE_STRING, self::TYPE_INT, self::TYPE_ARRAY, self::TYPE_FLOAT];

    function __construct($columnName, $columnType = 'string')
    {
        $this->_columnName = $columnName;
        if (in_array($columnType, $this->types)) {
            $this->_columnType = $columnType;
        } else {
            $this->instance($columnType);

        }
    }

    function int()
    {
        $this->_columnType = 'int';
        return $this;
    }

    function string()
    {
        $this->_columnType = 'string';
        return $this;
    }

    function float()
    {
        $this->_columnType = 'float';
        return $this;
    }

    function arr()
    {
        $this->_columnType = 'arr';
        return $this;
    }


    function validate($entity)
    {
        $columnType = $this->_columnType;
        $columnName = $this->_columnName;

        $value = $entity->$columnName;
        /** @var V $v */
        $v = V::$columnType($this->_instance);
        if (empty($value)) {
            if ($this->_notEmpty) {
                $v->notEmpty();
            }
        } else {
            $v->length($this->_minLength, $this->_maxLength);

            if (isset($this->_max)) {
                $v->max($this->_max);
            }

            if (isset($this->_min)) {
                $v->min($this->_min);
            }

            if ($this->_email) {
                $v->email();
            }

            if ($this->_regex) {
                $v->regex($this->_regex);
            }

            if (!empty($this->_in)) {
                $v->in($this->_in);
            }
        }

        V::attribute($this->_columnName, $v)->assert($entity);
    }


    function in($in)
    {
        $this->_in = $in;
        return $this;
    }

    function instance($instance)
    {
        $this->_columnType = 'instance';
        $this->_instance = $instance;
        return $this;
    }

    function need()
    {
        $this->_notEmpty = true;
        return true;
    }

    function email($maxLength, $minLength = -1)
    {
        $this->_email = true;
        return $this->length($maxLength, $minLength);
    }

    function mobile()
    {
        return $this->regex(self::REGEX_MOBILE);
    }

    function length($maxLength, $minLength = -1)
    {
        $this->_maxLength = $maxLength;
        if ($minLength >= 0) {
            $this->_minLength = $minLength;
        }
        return $this;

    }

    function id()
    {
        $this->_isPrimaryKey = true;
        return $this;
    }

    function regex($regex)
    {
        $this->_regex = $regex;
        return $this;
    }

    function ignore()
    {
        $this->_notDb = true;
        return $this;
    }

    function toDb($toDbFunc)
    {
        $this->_toDbFunc = $toDbFunc;
        return $this;
    }

    function fromDb($fromDbFunc)
    {
        $this->_fromDbFunc = $fromDbFunc;
        return $this;
    }


    /**
     * @param $method
     * @param array $arguments
     * @return EntityColumn
     * @internal param string $ruleName
     */
    public static function __callStatic($method, $arguments)
    {
        $backtrace = debug_backtrace();
        $class = $backtrace[2]['class'];
        $keys = $arguments[0];
        $instance = null;
        if(isset($arguments[1])){
            $instance = $arguments[1];
        }

        if(substr($method,count($method)-2)!=='s'){
            return;
        }
        $method = substr($method,0,count($method)-2);


        $class::cols($method,$keys,$instance);
    }
}