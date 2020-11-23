<?php
/* Include parent class */
sys::import('modules.dynamicdata.xarproperties.deferitem');

/**
 * The Deferred List property delays loading extra information using the database values until they need to be shown.
 *
 * Note: this might be an alternative approach for some of the dataquery gymnastics used in some objects and properties
 *
 * The relationships are defined based on the values in the deferred item property, listing the itemids of Called1.
 *
 * Data Objects:
 *    Caller     
 *     itemid      N   Called1
 * (*) listprop1 ====>  itemid
 *                      propname (+)
 *                      propname2
 * (*) this property
 * (+) as an extension, the deferred list could also refer to another property than the itemid in Called1 (todo)
 * Note: you can have several defer* properties per object, each pointing to a different relationship
 *
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 */
 
 /**
  * This property displays a deferred item list for a value (experimental - do not use in production)
  *
  * Configuration:
  * the defaultvalue can be set to automatically load object properties if the value includes their itemids,
  * or you can use DeferredProperty::set_resolver($resolver, $name) method to set a resolver function
  * or you can inherit this class and override the static override_me_load() method below
  */
class DeferredListProperty extends DeferredItemProperty
{
    public $id         = 18282;
    public $name       = 'deferlist';
    public $desc       = 'Deferred List';
    public $reqmodules = array('dynamicdata');
    public $options    = array();
    public $defername  = null;
    public $objectname = null;
    public $fieldlist  = null;
    public $displaylink = null;
    public static $deferred = array();  // array of $name with deferred 'todo' values and 'cache' items for each
    public static $resolver = array('default' => null);  // array of 'default' and optional $name with resolver function for each

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        // Set for runtime
        $this->template = 'deferlist';
    }

    /**
     * Get the value of this property (= for a particular object item)
     *
     * @return mixed the value for the property
     */
    public function getValue()
    {
        // @todo where to serialize/unserialize?
        return parent::getValue();
    }

    /**
     * Set the value of this property (= for a particular object item)
     *
     * @param mixed $value the new value for the property
     */
    public function setValue($value=null)
    {
        // @todo where to serialize/unserialize?
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
        // @todo where to serialize/unserialize?
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
        // @todo where to serialize/unserialize?
        parent::setItemValue($itemid, $value, $fordisplay);
    }

    /**
     * Set the data to defer here - in this case the property values
     */
    public function setDataToDefer($itemid, $values)
    {
        if (!empty($values)) {
            if (!is_array($values)) {
                $values = array($values);
            }
            foreach ($values as $value) {
                static::add_deferred($this->defername, $value);
            }
        }
    }

    /**
     * Show an input field for setting/modifying the value of this property
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['value'] value of the field (default is the current value)
     * @param $args['id'] id of the field
     * @param $args['tabindex'] tab index of the field
     * @param $args['module'] which module is responsible for the templating
     * @param $args['template'] what's the partial name of the showinput template.
     * @param $args[*] rest of arguments is passed on to the templating method.
     *
     * @return string containing the HTML (or other) text to output in the BL template
     */
    public function showInput(array $data = array())
    {
        return parent::showInput($data);
    }

    /**
     * Show some default output for this property
     *
     * @param mixed $data['value'] value of the property (default is the current value)
     * @return string containing the HTML (or other) text to output in the BL template
     */
    public function showOutput(array $data = array())
    {
        return parent::showOutput($data);
    }

    /**
     * Get the actual deferred data here
     */
    public function getDeferredData(array $data = array())
    {
        $values = null;
        if (isset($data['value'])) {
            $values = $data['value'];
            if (!is_array($values)) {
                $values = array($values);
            }
        } elseif (!empty($this->value)) {
            $values = $this->value;
            if (!is_array($values)) {
                $values = array($values);
            }
            // @checkme for showDisplay(), set data['value'] here
            foreach ($values as $value) {
                static::add_deferred($this->defername, $value);
            }
        }
        if (empty($values)) {
            $data['value'] = '';
            return $data;
        }
        //$data['link'] = xarServer::getObjectURL($this->objectname, 'display', array('itemid' => $value));
        // see if we can use a fixed template for display links here - replace itemid in template per value in array
        if (!isset($data['link']) && !empty($this->displaylink)) {
            $data['link'] = $this->displaylink;
        }
        $items = array();
        foreach ($values as $value) {
            $key = (string) $value;
            $items[$key] = static::get_deferred($this->defername, $value);
        }
        $data['value'] = $items;
        return $data;
    }
}

