<?php
/**
 * @package core
 * @subpackage structures
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 */
/**
 * Base Variable Object Class
 *
 * @author Chris Powis <crisp@xaraya.com>
**/
abstract class xarVariableObject extends Object
{
    // classes overloading this object must supply the following static properties
    protected static $instance;
    protected static $scope = 'module'; // one of module|user|session
    protected static $variable;         // the name of the variable 
    protected static $module;           // name of the module (if scope is module|user)
    
    // classes overloading can supply their own additional properties...
    // the basic premise here is that the values of all public properties will be stored

/*
 * NOTE: unfortunately we can't ever set a private constructor :(
**/
/*
    protected function __contruct()
    {
        // classes overloading this class must not set this private
        // otherwise the getInstance() function will fail
    }
*/

/**
 * Get instance function
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @params none
 * @return Object current instance
 * @throws none
**/    
    final public static function getInstance()
    {
        if (!isset(static::$instance)) {
            switch (static::$scope) {
                case 'module':
                    static::$instance = @unserialize(xarModVars::get(static::$module, static::$variable));
                break;
                case 'user':
                    static::$instance = @unserialize(xarModUserVars::get(static::$module, static::$variable));
                break;
                case 'session':
                    static::$instance = @unserialize(xarSession::getVar(static::$variable));
                break;
            }
            // NOTE: if the object unserialized successfully 
            // the __wakeup() method will be called here...
            // Otherwise a new instance is created...
            if (empty(static::$instance) || !is_object(static::$instance)) {
                $c = get_called_class();
                // NOTE: this is the one and only time the __construct() method 
                // will be run unless the variable is deleted outside this class
                static::$instance = new $c;
            }
        }
        return static::$instance;       
    }

/**
 * Object destructor
 *
 * This method is called when the object goes out of scope, 
 * typically this will be when xaraya exits but can be forced
 * at any time by unsetting the object
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @params none
 * @throws none
 * @return void
**/        
    public function __destruct()
    {
        // NOTE: the __sleep() method will be called when the object is serialized here
        switch (static::$scope) {
            case 'module':
                xarModVars::set(static::$module, static::$variable, serialize($this));
            break;
            case 'user':
                xarModUserVars::set(static::$module, static::$variable, serialize($this));
            break;
            case 'session':
                xarSession::setVar(static::$variable, serialize($this));
            break;
        }
    }

/** 
 * Object wakeup (unserialize)
 * 
 * this method gets called when the object is unserialized by the getInstance method
 * Classes extending this class can use it to perform any initial operations
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @params none
 * @throws none
 * @return void
**/
    public function __wakeup()
    {
        // perform any actions required after unserialize here...
    }

/** 
 * Object sleep (serialize)
 * 
 * this method gets called when the destructor serializes($this)
 * Classes extending this class can use it to perform 
 * any last minute operations before the object is stored
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @params none
 * @throws none
 * @return array names of properties to store when object is serialized
**/
    public function __sleep()
    {
        // perform any actions before serialize (store) here...
        
        // get the names of all public properties...
        $properties = array_keys($this->getPublicProperties());
        // return the array of properties to serialize...
        return $properties;
    }

/**
 * Prevent cloning instance
 * We only ever want there to be one instance of this object 
**/    
    public function __clone()
    {
        throw new ForbiddenOperationException('__clone', 'Not allowed to #(1) this object');
    }

}

/**
 * Simple Example
**/
/*
Class testClass extends xarVariableObject
{
    // required properties, these are never stored
    protected static $instance;
    protected static $variable = 'myvar';
    protected static $scope    = 'module';
    protected static $module   = 'base';
    
    // public properties we want to store
    public $counter  = 1; // first run default
    public $lastrun  = 0; // last run never
    public $mystring = 'foo';

    public function __wakeup()
    {
        // increment the counter
        $this->counter++;
    }

    public function __sleep()
    {
        // set last run time
        $this->lastrun = time();
        return parent::__sleep();
    }
}

// get instance, first run
$foo = testClass::getInstance();
var_dump($foo); // first run, count = 1, lastrun = 0

// change mystring value
$foo->mystring = 'bar';

$foo = testClass::getInstance();
var_dump($foo); // count still 1, lastrun still 0

// the object is stored automatically when this script goes out of scope
// calling unset() implicitly stores the updated variable object immediately
unset($foo); // __sleep() is called, setting lastrun to now

// get the stored instance
$foo = testClass::getInstance(); // __wakeup() is called, incrementing counter
var_dump($foo); 
// mystring is now bar, counter incremented by 1, and lastrun is the time unset() was called :)
*/
?>