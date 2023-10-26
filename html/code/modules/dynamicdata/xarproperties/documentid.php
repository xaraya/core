<?php
/**
 * The Document ID property holds a unique string identifier for an item or document
 *
 * This provides an alternative for ItemIDProperty when the id's are generated outside
 * Xaraya and are in string format, e.g. hexadecimal, Base64 or free-format
 *
 * Note: this only affects the format type. Please continue using 'itemid' as the variable
 * and/or URL parameter to pass along the item id's anywhere else in Xaraya as usual
 *
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 */

namespace Xaraya\DataObject\Properties;

use DataProperty;
use ObjectDescriptor;
use sys;

/* Include parent class */
sys::import('modules.dynamicdata.class.properties.base');

/**
 * The Document ID property holds a unique string identifier for an item or document
 */
class DocumentIDProperty extends DataProperty
{
    public $id         = 18221;
    public $name       = 'documentid';
    public $desc       = 'Document ID';
    public $reqmodules = ['dynamicdata'];
    public $basetype   = 'string';

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        // Set for runtime
        $this->tplmodule = 'dynamicdata';
        $this->template = 'documentid';
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
            $value = (string) $value;
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
            $value = (string) $value;
        }
        //$this->value = $value;
        //$this->_items[$itemid][$this->name] = $this->value;
        parent::setItemValue($itemid, $value, $fordisplay);
    }
}
