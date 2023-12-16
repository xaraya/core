<?php

namespace Xaraya\DataObject\Generated;

use Xaraya\Core\Traits\ContextInterface;
use Xaraya\Core\Traits\ContextTrait;
use DataContainer;
use DataObjectDescriptor;
use DataObject;
use Exception;
use VirtualObjectFactory;
use ArrayObject;
use sys;

interface iGeneratedClass
{
    /**
     * Constructor for GeneratedClass
     * @param ?int $itemid (optional) itemid to retrieve DataObject item from database
     * @param array<string, mixed> $values (optional) values to set for DataObject properties
     */
    public function __construct($itemid = null, $values = []);

    /**
     * Get the value of this property (= for a particular object item)
     * @param string $name
     * @return mixed
     */
    public function get($name);

    /**
     * Set the value of this property (= for a particular object item)
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function set($name, $value = null);

    /**
     * Return this object as an array
     * @return array<string, mixed>
     */
    public function toArray();

    /**
     * Get a list of instances of this class
     * @return \ArrayObject<(int|string), mixed>
     */
    public static function list(int $startnum = 0, int $numitems = -1);
}

/**
 * Generated DataObject Class exported from DD DataObject configuration
 * with properties mapped to their DataObject properties (experimental)
 *
 * use Xaraya\DataObject\Generated\Sample;
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
class GeneratedClass extends DataContainer implements iGeneratedClass, ContextInterface
{
    use ContextTrait;

    /** @var string */
    protected static $_objectName = 'OVERRIDE';
    /** @var ?DataObject */
    public static $_object;
    /** @var ?DataObjectDescriptor */
    public static $_descriptor;
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
        $this->load($itemid);
        if (!empty($values)) {
            $this->refresh($values);
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
     * Load DataObject item and instance
     * @param mixed $itemid
     * @return void
     */
    public function load($itemid = null)
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
        $this->store();
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
        $this->_itemid = static::getObject()->getItem(['itemid' => $itemid]);
        $this->store();
        return $this->_itemid;
    }

    /**
     * Clear DataObject values
     * @return void
     */
    public function clear()
    {
        static::getObject()->clearFieldValues();
        $this->store();
    }

    /**
     * Save DataObject item
     * @return int|null
     */
    public function save()
    {
        if (empty($this->_itemid)) {
            $this->_itemid = static::getObject()->createItem();
        } else {
            $this->_itemid = static::getObject()->updateItem();
        }
        $this->store();
        return $this->_itemid;
    }

    /**
     * Delete DataObject item and re-initialize
     * @return void
     */
    public function delete()
    {
        if (!empty($this->_itemid)) {
            static::getObject()->deleteItem();
        }
        $this->load();
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

    public function setContext($context)
    {
        $this->context = $context;
        // @todo set context if available in generated class
        static::getObject()->setContext($this->getContext());
    }

    /**
     * Get a list of instances of this class
     * @param int $startnum start number (default 1)
     * @param int $numitems number of items to retrieve (default 0 = all)
     * @return \ArrayObject<(int|string), mixed>
     */
    public static function list(int $startnum = 1, int $numitems = 0)
    {
        $objectlist = VirtualObjectFactory::makeObjectList(static::getDescriptor());
        if ($numitems > 0) {
            $items = $objectlist->getItems(['startnum' => $startnum, 'numitems' => $numitems]);
        } else {
            $items = $objectlist->getItems();
        }

        $classname = static::class;
        $base = new $classname();

        $result = new ArrayObject();
        foreach ($items as $itemid => $values) {
            $item = clone $base;
            $item->_itemid = $itemid;
            $item->_values = $values;
            /** @phpstan-ignore-next-line */
            $result[$itemid] = $item;
        }
        return $result;
    }

    /**
     * Get the data object
     * @return DataObject
     */
    public static function getObject()
    {
        if (!isset(static::$_object)) {
            static::$_object = VirtualObjectFactory::makeObject(static::getDescriptor());
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
            $offline = true;
            static::$_descriptor = VirtualObjectFactory::getObjectDescriptor(static::getDescriptorArgs(), $offline);
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
            $filepath = sys::varpath() . '/cache/variables/' . static::$_objectName . '-def.php';
            if (!is_file($filepath)) {
                throw new Exception('No descriptor cached yet - you need to export this object to php first');
            }
            $args = require $filepath;
            //$args = VirtualObjectFactory::prepareDescriptorArgs($args);
            $args['propertyargs'] ??= [];
            static::$_descriptorArgs = $args;
        }
        return static::$_descriptorArgs;
    }

    /**
     * Get the property descriptor args
     * @return list<array<string, mixed>>
     */
    public static function getPropertyArgs()
    {
        $args = static::getDescriptorArgs();
        return $args['propertyargs'] ?? [];
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
