<?php
/**
 * The MongoDB BSON property tries to deal with various MongoDB BSON data formats
 *
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 */

namespace Xaraya\DataObject\Properties\MongoDB;

use DataProperty;
use ObjectDescriptor;
use JsonSerializable;
use sys;

/* Include parent class */
sys::import('modules.dynamicdata.class.properties.base');

/**
 * The MongoDB BSON property tries to deal with various MongoDB BSON data formats
 */
class BSONProperty extends DataProperty
{
    public $id         = 18290;
    public $name       = 'mongodb_bson';
    public $desc       = 'MongoDB BSON';
    public $reqmodules = ['dynamicdata'];
    public $options    = [];

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        if (!class_exists('\\MongoDB\\Client')) {
            $this->desc .= ' (autoload)';
        }
        // Set for runtime
        $this->tplmodule = 'dynamicdata';
        $this->template = 'mongodb_bson';
        $this->filepath = 'modules/dynamicdata/xarproperties';
    }

    /**
     * Get the value of this property (= for a particular object item)
     *
     * @return mixed the value for the property
     */
    public function getValue()
    {
        //return $this->value;
        return parent::getValue();
    }

    /**
     * Set the value of this property (= for a particular object item)
     *
     * @param mixed $value the new value for the property
     */
    public function setValue($value = null)
    {
        if (is_object($value)) {
            if ($value instanceof JsonSerializable) {
                // leave the value as is here and deal with it in template
                //$value = json_encode($value, JSON_PRETTY_PRINT);
            } else {
                $value = var_export($value, true);
            }
        }
        //$this->value = $value;
        parent::setValue($value);
    }

    /**
     * Get the value of this property for a particular item (= for object lists)
     *
     * @param int $itemid the item id we want the value for
     * @return mixed
     */
    public function getItemValue($itemid)
    {
        //return $this->_items[$itemid][$this->name];
        return parent::getItemValue($itemid);
    }

    /**
     * Set the value of this property for a particular item (= for object lists)
     *
     * @param int $itemid
     * @param mixed $value
     * @param integer $fordisplay
     */
    public function setItemValue($itemid, $value, $fordisplay = 0)
    {
        if (is_object($value)) {
            if ($value instanceof JsonSerializable) {
                // leave the value as is here and deal with it in template
                //$value = json_encode($value, JSON_PRETTY_PRINT);
            } else {
                $value = var_export($value, true);
            }
        }
        //$this->value = $value;
        //$this->_items[$itemid][$this->name] = $this->value;
        parent::setItemValue($itemid, $value, $fordisplay);
    }

    /**
     * Summary of castType
     * @param mixed $value
     * @return mixed
     */
    public function castType($value = null)
    {
        // leave the value as is here and deal with it in template
        return $value;
    }
}
