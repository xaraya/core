<?php

namespace Xaraya\DataObject\Generated;

use DataContainer;
use DataObjectDescriptor;
use DataObject;
use VirtualObjectDescriptor;
use sys;

/**
 * Generated DataObject Class exported from DD DataObject configuration (experimental)
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
 * // actual sample object with itemid = 1 and differen age
 * $sample = new Sample(1, ['age' => 33]);
 *
 * $coll = new ArrayObject();
 * for ($i = 0; $i < 10000; $i++) {
 *     $coll[] = new Sample(null, ['name' => "Item $i", 'age' => $i]);
 * }
 */
class GeneratedClass extends DataContainer
{
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
     * Summary of __construct
     * @param ?int $itemid
     * @param array<string, mixed> $args
     */
    public function __construct($itemid = null, $args = [])
    {
        $this->_itemid = null;
        $this->_values = [];
        if (!empty($itemid)) {
            $this->_itemid = static::getObject()->getItem(['itemid' => $itemid]);
        } else {
            $this->_itemid = static::getObject()->clearFieldValues();
        }
        if (!empty($args)) {
            static::getObject()->setFieldValues($args);
        }
        $this->_values = static::getObject()->getFieldValues();
        foreach (static::getPropertyNames() as $name) {
            $this->$name = static::getObject()->properties[$name];
        }
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
        //static::getObject()->setFieldValues($this->_values);
        foreach (static::getPropertyNames() as $name) {
            $this->$name = static::getObject()->properties[$name];
        }
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
}
