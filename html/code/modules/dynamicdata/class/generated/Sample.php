<?php

namespace Xaraya\DataObject\Generated;

use ItemIDProperty;
use TextBoxProperty;
use NumberBoxProperty;
use ImageProperty;
use DeferredItemProperty;
use DeferredListProperty;

/**
 * Generated Sample class exported from DD DataObject configuration
 * with properties mapped to their DataObject properties (experimental)
 *
 * Configuration saved in sample-def.php
 */
class Sample extends GeneratedClass
{
    /** @var string */
    protected static $_objectName = 'sample';
    /** @var ItemIDProperty */
    public $id;
    /** @var TextBoxProperty */
    public $name;
    /** @var NumberBoxProperty */
    public $age;
    /** @var ImageProperty */
    public $location;
    /** @var DeferredItemProperty */
    public $partner;
    /** @var DeferredListProperty */
    public $children;
    /** @var DeferredListProperty */
    public $parents;

    /**
     * Constructor for Sample
     * @param ?int $itemid (optional) itemid to retrieve DataObject item from database
     * @param array<string, mixed> $values (optional) values to set for DataObject properties
     */
    public function __construct($itemid = null, $values = [])
    {
        parent::__construct($itemid, $values);
    }

    /**
     * Get the value of this property (= for a particular object item)
     * @param string $name
     * @return mixed
     */
    public function get($name)
    {
        return parent::get($name);
    }

    /**
     * Set the value of this property (= for a particular object item)
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function set($name, $value = null)
    {
        parent::set($name, $value);
    }

    /**
     * Save DataObject item
     * @return int|null
     */
    public function save()
    {
        return parent::save();
    }
}
