<?php

namespace Xaraya\DataObject\Generated;

use DataContainer;
use DataObjectDescriptor;
use DataObject;
use Exception;
use VirtualObjectDescriptor;
use xarCoreCache;
use sys;

/**
 * Generated DataObject Class exported from DD DataObject configuration
 * with properties mapped to their DataObject properties (experimental)
 *
 * use Xaraya\DataObject\Generated\Sample;
 * use Xaraya\DataObject\Generated\Format;
 *
 * // dummy sample object
 * $sample = new Sample();
 * // actual sample object with itemid = 1
 * $sample = new Sample(1);
 * // dummy sample object with name and age
 * $sample = new Sample(null, ['name' => 'Mike', 'age' => 20]);
 * // actual sample object with itemid = 1 and different age
 * $sample = new Sample(1, ['age' => 33]);
 *
 * $coll = new ArrayObject();
 * for ($i = 0; $i < 10000; $i++) {
 *     $coll[] = new Sample(null, ['name' => "Item $i", 'age' => $i]);
 * }
 */
class GeneratedClass extends DataContainer
{
    /** @var string */
    protected static $_objectName = 'OVERRIDE';
    /** @var ?DataObject */
    protected static $_object;
    /** @var ?DataObjectDescriptor */
    protected static $_descriptor;
    /** @var array<string, mixed> */
    protected static $_descriptorArgs = [];
    /** @var list<array<string, mixed>> */
    protected static $_propertyArgs = [];
    /** @var ?int */
    protected $_itemid = null;
    /** @var array<string, mixed> */
    protected $_values = [];

    /**
     * Constructor for GeneratedClass
     * @param ?int $itemid (optional) itemid to retrieve DataObject item from database
     * @param array<string, mixed> $values (optional) values to set for DataObject properties
     */
    public function __construct($itemid = null, $values = [])
    {
        $this->initialize($itemid);
        if (!empty($values)) {
            $this->refresh($values);
        }
        $this->store();
    }

    /**
     * Get the value of this property (= for a particular object item)
     * @param string $name
     * @return mixed
     */
    public function get($name)
    {
        // don't use the property getValue() here
        //return $this->$name->getValue();
        return $this->_values[$name] ?? null;
    }

    /**
     * Set the value of this property (= for a particular object item)
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function set($name, $value = null)
    {
        // use the property setValue() and getValue() here
        $this->$name->setValue($value);
        $this->_values[$name] = $this->$name->getValue();
    }

    /**
     * Return this object as an array
     * @return array<string, mixed>
     */
    public function toArray()
    {
        $values = [];
        foreach (static::getPropertyNames() as $name) {
            $values[$name] = $this->get($name);
        }
        return $values;
    }

    /**
     * Initialize DataObject and instance
     * @param mixed $itemid
     * @return void
     */
    public function initialize($itemid = null)
    {
        $this->_itemid = null;
        $this->_values = [];
        if (!empty($itemid)) {
            $this->_itemid = $this->retrieve($itemid);
        } else {
            $this->clear();
        }
        $this->connect();
    }

    /**
     * Refresh DataObject values from instance or values
     * @param ?array<string, mixed> $values
     * @return void
     */
    public function refresh($values = null)
    {
        if (isset($values)) {
            static::getObject()->setFieldValues($values);
        } else {
            static::getObject()->setFieldValues($this->_values);
        }
    }

    /**
     * Store DataObject values in instance
     * @return void
     */
    public function store()
    {
        $this->_values = static::getObject()->getFieldValues();
    }

    /**
     * Retrieve DataObject item from database
     * @param mixed $itemid
     * @return mixed
     */
    public function retrieve($itemid)
    {
        if (empty($itemid) || $itemid == $this->_itemid) {
            return $itemid;
        }
        return static::getObject()->getItem(['itemid' => $itemid]);
    }

    /**
     * Clear DataObject values
     * @return void
     */
    public function clear()
    {
        static::getObject()->clearFieldValues();
    }

    /**
     * Connect DataObject properties to instance
     * @return void
     */
    public function connect()
    {
        foreach (static::getPropertyNames() as $name) {
            $this->$name = static::getObject()->properties[$name];
        }
    }

    /**
     * Only serialize the current itemid and values
     * @return array<string>
     */
    public function __sleep()
    {
        return ['_itemid', '_values'];
    }

    /**
     * Reconnect the properties to the DataObject
     * @return void
     */
    public function __wakeup()
    {
        //$this->refresh();
        $this->connect();
    }

    /**
     * Get the data object
     * @return DataObject
     */
    public static function getObject()
    {
        if (!isset(static::$_object)) {
            $clazz = static::$_descriptorArgs['class'] ?? 'DataObject';
            $filepath = static::$_descriptorArgs['filepath'] ?? 'auto';
            if(!empty($filepath) && ($filepath != 'auto')) {
                include_once(sys::code() . $filepath);
            }
            static::$_object = new $clazz(static::getDescriptor());
        }
        return static::$_object;
    }

    /**
     * Get the object descriptor
     * @return DataObjectDescriptor
     */
    public static function getDescriptor()
    {
        if (!isset(static::$_descriptor)) {
            // support *virtual* DataObject classes (= not defined in database) too
            static::$_descriptor = new VirtualObjectDescriptor(static::getDescriptorArgs());
        }
        return static::$_descriptor;
    }

    /**
     * Get the object descriptor args
     * @return array<string, mixed>
     */
    public static function getDescriptorArgs()
    {
        if (empty(static::$_descriptorArgs)) {
            $filepath = sys::varpath() . '/cache/variables/' . static::$_objectName . '.descriptor.php';
            if (!is_file($filepath)) {
                throw new Exception('No descriptor cached yet - you need to export this object to php first');
            }
            static::$_descriptorArgs = require $filepath;
        }
        $args = static::$_descriptorArgs;
        $args['propertyargs'] = static::getPropertyArgs();
        return $args;
    }

    /**
     * Get the property descriptor args
     * @return list<array<string, mixed>>
     */
    public static function getPropertyArgs()
    {
        if (empty(static::$_propertyArgs)) {
            $filepath = sys::varpath() . '/cache/variables/' . static::$_objectName . '.properties.php';
            if (!is_file($filepath)) {
                throw new Exception('No properties cached yet - you need to export this object to php first');
            }
            static::$_propertyArgs = require $filepath;
        }
        return static::$_propertyArgs;
    }

    /**
     * Get the list of property names
     * @return array<string>
     */
    public static function getPropertyNames()
    {
        $names = [];
        foreach (static::getPropertyArgs() as $propertyArg) {
            $names[] = $propertyArg['name'];
        }
        return $names;
    }

    /**
     * Load core cache with property types and configurations
     * @return void
     */
    public static function loadCoreCache()
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $filepath = sys::varpath() . '/cache/variables/DynamicData.PropertyTypes.php';
        if (!is_file($filepath)) {
            throw new Exception('No property types cached yet - you need to export at least 1 object to php');
        }
        $proptypes = include $filepath;
        xarCoreCache::setCached('DynamicData', 'PropertyTypes', $proptypes);
        $filepath = sys::varpath() . '/cache/variables/DynamicData.Configurations.php';
        $configprops = include $filepath;
        xarCoreCache::setCached('DynamicData', 'Configurations', $configprops);
        $loaded = true;
    }
}
