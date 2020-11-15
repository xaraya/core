<?php
/* Include parent class */
sys::import('modules.dynamicdata.class.properties.base');

/**
 * The Deferred property delays loading extra information using the database values until they need to be shown.
 * It was inspired by how GraphQL-PHP tackles the N+1 problem, but without proxy, callable or promises (sync or async).
 * Right now this would be the equivalent of lazy loading in batch for showOutput() in object lists :-)
 *
 * Note: this might be an alternative approach for some of the dataquery gymnastics used in some objects and properties
 *
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 */
 
 /**
  * This property displays a deferred load for a value (experimental - do not use in production)
  *
  * Config: the defaultvalue can be set to automatically load an object property if the value contains its itemid
  * Format: dataobject:<objectname>.<propname> or dataobject:<objectname>.<propname>,<propname2>,<propname3>
  * Example: dataobject:roles_users:uname will show the username if the property contains the user id
  *       or dataobject:roles_users:name,uname,email will show the name,uname,email if the property contains the user id
  */
class DeferredProperty extends DataProperty
{
    public $id         = 18281;
    public $name       = 'deferred';
    public $desc       = 'Deferred Load';
    public $reqmodules = array('dynamicdata');
    public static $deferred = array();  // array of $name with deferred 'todo' values and 'cache' load for each
    public static $resolver = array('default' => null);  // array of 'default' and optional $name with resolver function for each

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        // Set for runtime
        $this->tplmodule = 'dynamicdata';
        $this->template = 'deferred';
        $this->filepath = 'modules/dynamicdata/xarproperties';

        static::init_deferred($this->name);
        // @checkme set dataobject resolver based on defaultvalue = dataobject:<objectname>.<propname>
        if (!empty($this->defaultvalue) && substr($this->defaultvalue, 0, 11) === 'dataobject:') {
            list($object, $field) = explode('.', substr($this->defaultvalue, 11));
            if (!static::has_resolver($this->name)) {
                // @checkme support dataobject:<objectname>.<propname>,<propname2>,<propname3> here too
                if (strpos($field, ',') !== false) {
                    $fieldlist = explode(',', $field);
                } else {
                    $fieldlist = [$field];
                }
                $resolver = deferred_dataobject_resolver($object, $fieldlist);
                static::set_resolver($resolver, $this->name);
            }
            $this->defaultvalue = '';
        }
    }

    /**
     * Get the value of this property (= for a particular object item)
     *
     * @return mixed the value for the property
     */
    public function getValue()
    {
        $this->log_trace();
        return parent::getValue();
    }

    /**
     * Set the value of this property (= for a particular object item)
     *
     * @param mixed $value the new value for the property
     */
    public function setValue($value=null)
    {
        $this->log_trace();
        $this->value = $value;
    }

    /**
     * Get the value of this property for a particular item (= for object lists)
     *
     * @param int $itemid the item id we want the value for
     * @return mixed
     */
    public function getItemValue($itemid)
    {
        $this->log_trace();
        //return $this->_items[$itemid][$this->name];
        return parent::getItemValue($itemid);
    }

    /**
     * Set the value of this property for a particular item (= for object lists)
     *
     * @param int $itemid
     * @param mixed value
     * @param integer fordisplay
     */
    public function setItemValue($itemid, $value, $fordisplay=0)
    {
        if (isset($value)) {
            static::add_deferred($this->name, $value);
        }
        $this->log_trace();
        parent::setItemValue($itemid, $value, $fordisplay);
    }

    /**
     * Show some default output for this property
     *
     * @param mixed $data['value'] value of the property (default is the current value)
     * @return string containing the HTML (or other) text to output in the BL template
     */
    public function showOutput(array $data = array())
    {
        if (isset($data['value'])) {
            $data['value'] = static::get_deferred($this->name, $data['value']);
        } elseif (isset($this->value)) {
            // @checkme for showDisplay(), set data['value'] here
            static::add_deferred($this->name, $this->value);
            $data['value'] = static::get_deferred($this->name, $this->value);
        }
        $this->log_trace();
        return parent::showOutput($data);
    }

    /**
     * Initialize the deferred load cache for $name
     *
     * @param string $name name of the property
     */
    public static function init_deferred($name)
    {
        if (!isset(static::$deferred[$name])) {
            static::$deferred[$name] = array('todo' => array(), 'cache' => array());
        }
    }

    /**
     * Add $value to list for deferred loading of <whatever>
     *
     * @param string $name name of the property
     * @param mixed $value value for which we'll do a deferred load
     */
    public static function add_deferred($name, $value)
    {
        if (isset($value) &&
            isset(static::$deferred[$name]) &&
            !in_array($value, static::$deferred[$name]['todo']) &&
            !array_key_exists("$value", static::$deferred[$name]['cache'])) {
            static::$deferred[$name]['todo'][] = $value;
        }
    }

    /**
     * Load <whatever> for each $value, using
     * 1. callable static::$resolver[$name] or static::$resolver['default'] function, or
     * 2. custom override_me_load() method
     *
     * @param string $name name of the property
     */
    public static function load_deferred($name)
    {
        if (empty(static::$deferred[$name]['todo'])) {
            return;
        }
        $resolver = static::get_resolver($name);
        // 1. call $name or 'default' resolver if defined, or
        if (!empty($resolver) && is_callable($resolver)) {
            static::$deferred[$name]['cache'] = call_user_func($resolver, $name, static::$deferred[$name]['todo']);
        // 2. call overridden static override_me_load() method in property
        } else {
            static::$deferred[$name]['cache'] = static::override_me_load($name, static::$deferred[$name]['todo']);
        }
        static::$deferred[$name]['todo'] = array();
    }

    /**
     * Get deferred load of <whatever> for $value
     *
     * @param string $name name of the property
     * @param mixed $value value for which we'll do a deferred load
     * @return mixed <whatever> for $value
     */
    public static function get_deferred($name, $value)
    {
        if (!empty(static::$deferred[$name]['todo'])) {
            static::load_deferred($name);
        }
        if (isset($value) &&
            isset(static::$deferred[$name]) &&
            array_key_exists("$value", static::$deferred[$name]['cache'])) {
            return static::$deferred[$name]['cache']["$value"];
        }
        return $value;
    }

    /**
     * Get resolver function to actually load <whatever> for each $value ($name-specific or 'default')
     *
     * @param string $name name of the property
     * @return callable resolver
     */
    public static function get_resolver($name)
    {
        // 1.a. use $name resolver if defined, or
        if (array_key_exists($name, static::$resolver) && is_callable(static::$resolver[$name])) {
            return static::$resolver[$name];
        // 1.b. use 'default' resolver if defined
        } elseif (!empty(static::$resolver['default']) && is_callable(static::$resolver['default'])) {
            return static::$resolver['default'];
        }
        return null;
    }

    /**
     * Set resolver function to actually load <whatever> for each $value ($name-specific or 'default')
     * @todo make resolver configurable via property config someday?
     *
     * @param callable $resolver
     * @param string $name name of the property
     */
    public static function set_resolver($resolver, $name = 'default')
    {
        static::$resolver[$name] = $resolver;
    }

    public static function has_resolver($name = 'default')
    {
        return array_key_exists($name, static::$resolver);
    }

    public function log_trace()
    {
        return;
        $trace = debug_backtrace(false, 3);
        array_shift($trace);
        $caller = array_shift($trace);
        print_r("Caller: <pre>" . var_export($caller, true) . "</pre>");
        print_r("Trace: <pre>" . var_export($trace, true) . "</pre>");
    }

    /**
     * *Override this method* to actually load <whatever> for each $value (if static::$resolver is not used)
     * @todo look up <whatever> in batch based on $values in overridden method here
     *
     * @param string $name name of the property
     * @param array $values list of property values to load <whatever> for
     * @return array associative array of all "$value" => <whatever> that need to be loaded
     */
    public static function override_me_load($name, $values)
    {
        $result = array();
        foreach ($values as $value) {
            //$result["$value"] = array('id' => $value, 'name' => $name, 'whatever' => 'override_me_' . $name . '_' . (string) $value);
            $result["$value"] = 'override_me_' . $name . '_' . (string) $value;
        }
        return $result;
    }
}

/**
 * Example of creating a deferred resolver to assign to static::$resolver
 *
 * @param mixed $what something you want to use in the resolver function perhaps
 * @return callable resolver
 */
function deferred_example_resolver($what = 'resolve_me')
{
    /**
     * Deferred resolver function
     * @param string $name name of the property
     * @param array $values list of property values to load <whatever> for
     * @uses mixed $what something you want to use in the resolver function perhaps
     * @return array associative array of all "$value" => <whatever> that need to be loaded
     */
    $resolver = function ($name, $values) use ($what) {
        $result = array();
        // @todo look up <whatever> in batch based on $values here
        foreach ($values as $value) {
            //$result["$value"] = array('what' => $what, 'id' => $value, 'name' => $name, 'whatever' => $what . '_' . $name . '_' . (string) $value);
            $result["$value"] = $what . '_' . $name . '_' . (string) $value;
        }
        return $result;
    };
    return $resolver;
}

/**
 * Deferred resolver for dynamic dataobjects to assign to static::$resolver
 * @todo make resolver configurable via property config (instead of defaultvalue) someday?
 * @todo support combining property lookups on different fields? Not really a use case for this yet...
 *
 * @param string $object the name of the dataobject you want to look up
 * @param array $fieldlist the list of dataobject properties to return for each value=itemid
 * @return callable resolver to return a single value per itemid, or an assoc array per itemid
 */
function deferred_dataobject_resolver($object = 'modules', $fieldlist = ['name'])
{
    /**
     * Deferred resolver function
     * @param string $name name of the property
     * @param array $values list of property values to load <whatever> for (= assumed itemids here)
     * @uses mixed $object the name of the dataobject you want to look up
     * @uses array $fieldlist the list of dataobject properties to return for each value=itemid
     * @return array associative array of all "$value" => <whatever> that need to be loaded
     */
    $resolver = function ($name, $values) use ($object, $fieldlist) {
        $params = array('name' => $object, 'fieldlist' => $fieldlist);
        //$params = array('name' => $object, 'fieldlist' => $fieldlist, 'itemids' => $values);
        $objectlist = DataObjectMaster::getObjectList($params);
        $params = array('itemids' => $values);
        $result = $objectlist->getItems($params);
        // return array("$itemid" => assoc array of $fields)
        if (count($fieldlist) > 1) {
            return $result;
        }
        // return array("$itemid" => single $field value)
        $field = array_pop($fieldlist);
        $values = array();
        foreach ($result as $itemid => $props) {
            $values[$itemid] = $props[$field];
        }
        return $values;
    };
    return $resolver;
}

// set 'default' deferred resolver as a test here
//DeferredProperty::$resolver['default'] = deferred_example_resolver();
//DeferredProperty::set_resolver(deferred_example_resolver());
// replacing "Username" or "User List" propertytype - for showOutput() only
//DeferredProperty::set_resolver(deferred_dataobject_resolver('roles_users'));
// retrieving more than 1 property - requires adapting showOutput() template too, to deal with assoc array
//DeferredProperty::set_resolver(deferred_dataobject_resolver('roles_users', ['name', 'uname', 'email']));
// replacing dynamic_properties.objectid = "Object" propertytype
//DeferredProperty::set_resolver(deferred_dataobject_resolver('objects'));
//DeferredProperty::set_resolver(deferred_dataobject_resolver('properties', ['label']));
