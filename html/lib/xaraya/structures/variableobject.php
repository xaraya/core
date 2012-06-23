<?php
/**
 * @package core
 * @subpackage structures
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 */
/**
 * Base Variable Object Class
 * Singleton object class which models a module|user|session variable
 * turning it into a self contained, persistent self storing object 
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

/**
 * By its nature, this method is only called once during the lifetime of a 
 * variable (subsequent runs are unserialized from a stored object)
 * If used at all, it's usually to set initial defaults to property values
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
 * Get an instance of the variable object
 * This method will attempt to fetch a stored copy of the variable,
 * falling back to creating a new object instance and storing it for susbequent use
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @params none
 * @return Object current instance
 * @throws none
**/    
    final public static function getInstance($role_id=null)
    {
        if (!isset(static::$instance)) {
            switch (static::$scope) {
                case 'module':
                    static::$instance = @unserialize(xarModVars::get(static::$module, static::$variable));
                break;
                case 'user':
                    $this->_role_id = isset($role_id) ? $role_id : xarSession::getVar('role_id');
                    static::$instance = @unserialize(xarModUserVars::get(static::$module, static::$variable, $this->_role_id));
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
                // NOTE: this is the one and only time the objects __construct() method 
                // will be run unless the variable is deleted outside this class
                static::$instance = new $c;
                // user vars inherit from a parent mod var, if the user var
                // isn't set we need to save the mod var first
                if (static::$scope == 'user')
                    static::$instance->save('module');
                // save the object immediately to the specified variable 
                static::$instance->save();
            }
            // session variables don't go out of scope, so we register
            // a shutdown handler to call the class destructor
            if (static::$scope == 'session') 
                register_shutdown_function(array(static::$instance, '__destruct'));
        }
        return static::$instance;       
    }

    public function getInfo()
    {
        return $this->getPublicProperties();
    }

/**
 * Object destructor
 *
 * This method is called when the object goes out of scope, 
 * typically this will be when xaraya exits but can be forced
 * at any time by unsetting the object. 
 * This method calls the save method which sets the variable defined
 * by the static scope, variable and module properties
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @params none
 * @throws none
 * @return void
**/        
    public function __destruct()

    {
        // save the object 
        $this->save();

    }
/**
 * Save object
 *
 * Save a serialized copy of this object to the variable defined 
 * by the static scope, variable and module properties
 * NOTE: you should place any shutdown operations in the __sleep method,
 * so they are still called should the object be serialized manually
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @params none
 * @throws none
 * @return void
**/
    public function save($scope=null)
    {
        if (!isset($scope))
            $scope = static::$scope;
        // NOTE: the __sleep() method will be called when the object is serialized here
        switch ($scope) {
            case 'module':
                xarModVars::set(static::$module, static::$variable, serialize(static::$instance));
            break;
            case 'user':
                xarModUserVars::set(static::$module, static::$variable, serialize(static::$instance), $this->_role_id);
            break;
            case 'session':
                xarSession::setVar(static::$variable, serialize(static::$instance));
            break;
            default:
                return false;
            break;
        }        
        return true;
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
 * this method gets called when the object is serialized (usually when saving)
 * Classes extending this class can use it to perform any last minute
 * operations before the object is serialized
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @params none
 * @throws none
 * @return array names of properties to store when object is serialized
**/
    public function __sleep()

    {
        // perform any actions before serialize (save) here...
        // return an array of property names to save
        return array_keys($this->getPublicProperties());

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