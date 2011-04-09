<?php
/**
 * General data object
 *
 * Based on God.php and DataObject of Hunstein & Kang GbR
 *
 * The DataObject is an abstract class, which is intended to simplify common
 * set/get/has functionality of data objects.
 * Every protected member of the child class is considered a property of the
 * data object, unless the member name is in the ignore array, set by
 * DataObject::registerIgnoreProps().
 *
 * For example: the class
 *
 * <code>
 * class Test extends DataObject
 * {
 *     protected $testValue;
 *     protected $testNoProperty;
 *
 *     public function __construct()
 *     {
 *         $this->registerIgnoreProps(array('testNoProperty'));
 *         parent::__construct();
 *     }
 * }
 * </code>
 *
 * will provide the following methods:
 *
 * <code>
 * $test = new Test();
 * $test->setTestValue('test');
 * if ($test->hasTestValue()) {
 *     echo $test->getTestValue();
 * }
 * $test->test_value = 'test';
 * echo $test->test_value;
 * </code>
 *
 * Note, that the direct access method uses the underscore-separated form. You can
 * take advantage of that to instantiate objects through database backends, where
 * you usually want the underscore-form for table columns.
 *
 * Also note, that you will have to call the parent constructor too. But after setting
 * the ignore properties.
 *
 * Interal get/set methods are named register/retrieve to avoid collision
 * with properties.
 *
 * @package  Abstract
 * @author   Yani Iliev <yani.iliev@cspath.com>
 */
abstract class DataObject
{
    /**
     * @var ReflectionObject
     */
    private $_self;
    /**
     * Array of all data properties.
     *
     * @var array
     */
    private $_props;
    /**
     * Properties to ignore
     *
     * @var array
     */
    private $_ignoreProps;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_reflect();
    }

    /**
     * Call magic method
     *
     * @param string $name Method name
     * @param Array  $args Arguments
     *
     * @throws Exception
     * @return mixed On get returns the property, on set it returns the instance,
     *               on has boolean.
     */
    public function __call($name, $args)
    {
        $action   = substr($name, 0, 3);
        if(!function_exists("lcfirst")){
        
          $string = substr($name, 3);
          $string{0} = strtolower($string{0});
          $property = $string;
        } else {
            $property = lcfirst(substr($name, 3));
        }
        
        switch ($action) {
        case 'get':
            if (!in_array($property, $this->_props)) {
                throw new Exception('Call to undefined function: '.$name.'.');
            }
            return $this->$property;

        case 'set':
            if (!in_array($property, $this->_props)) {
                throw new Exception('Call to undefined function: '.$name.'.');
            }
            $this->$property = $args[0];
            return $this;

        case 'has':
            return (in_array($property, $this->_props) && isset($this->$property));
        }
        // @codeCoverageIgnoreStart
    }
    // @codeCoverageIgnoreEnd

    /**
     * Get magic method
     *
     * Calls either the corresponding get[Property] method or the call magic method default.
     *
     * @param string $name Column name (not property name)
     *
     * @throws Exception
     * @return mixed The value of the column
     */
    public function __get($name)
    {
        $callback = array(&$this, 'get' . self::camelize($name));
        return call_user_func($callback);
    }

    /**
     * Set magic method
     *
     * Calls either the corresponding set[Property] method or the call magic method default.
     *
     * @param string $name  Column name
     * @param mixed  $value value
     *
     * @throws Exception
     * @return DataObject
     */
    public function __set($name, $value)
    {
        $callback = array(&$this, 'set' . self::camelize($name));
        return call_user_func($callback, $value);
    }

    /**
     * Camelize a column
     *
     * Removes all underscores and treats them as a word separator.
     *
     * @param string $str The string to camelize
     *
     * @return string Camelized string
     */
    public static function camelize($str)
    {
        $tokens = explode('_', $str);
        $result = '';
        $num = count($tokens);
        for ($i = 0; $i < $num; ++$i) {
            $result .= ucfirst(strtolower($tokens[$i]));
        }
        return $result;
    }

    /**
     * Reverse action for camelize()
     *
     * @param string $str Camelized string
     *
     * @return string decamelized string
     */
    public static function decamelize($str)
    {
        $tokens = preg_split('/([A-Z])/', $str, null, (PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE));
        $result = array();
        $len = count($tokens);
        if ($len == 1) {
            return $str;
        }
        $result[] = $tokens[0];
        for ($i = 1; $i < $len; $i += 2) {
            $r = strtolower($tokens[$i]);
            if (isset($tokens[$i+1])) {
                $r .= $tokens[$i+1];
            }
            $result[] = $r;
        }
        return implode('_', $result);
    }

    /**
     * Reflect and find the properties
     *
     * @return void
     */
    private function _reflect()
    {
        $refObj = new ReflectionObject($this);
        $this->_self = $refObj;
        foreach ($refObj->getProperties() as $property) {
            if (!$property->isProtected()) {
                continue;
            }
            if (in_array($property->getName(), $this->_ignoreProps)) {
                continue;
            }
            $this->_props[] = $property->getName();
        }
    }

    /**
     * Sets the (protected) properties to ignore
     *
     * Call this before the constructor
     *
     * @param array $ignore Array with property names
     *
     * @return DataObject
     */
    protected function registerIgnoreProps(array $ignore = array())
    {
        $this->_ignoreProps = $ignore;
        return $this;
    }

    /**
     * Returns the current class name.
     *
     * @return string The class name
     */
    public function retrieveClassName()
    {
        return $this->_self->getName();
    }

    /**
     * Returns the handling properties
     *
     * @return array properties
     */
    public function retrieveProperties()
    {
        return $this->_props;
    }
}
