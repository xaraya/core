<?php
/* Include parent class */
sys::import('modules.dynamicdata.xarproperties.deferitem');
sys::import('modules.dynamicdata.class.objects.loader');

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
  * This property displays a deferred item list for a value array (experimental - do not use in production)
  * In general, $this->value should be encoded, and $data['value'] and *ItemValue should be in decoded format
  *
  * Configuration:
  * the defaultvalue can be set to automatically load object properties if the value includes their itemids,
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
    public $singlevalue = false;
    public static $deferred = array();  // array of $name with deferred data object list loader

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
        // already encoded
        return parent::getValue();
    }

    /**
     * Set the value of this property (= for a particular object item)
     *
     * @param mixed $value the new value for the property
     */
    public function setValue($value=null)
    {
        // 1. in construct() set to defaultvalue - skip
        // 2. in showForm() set for input preview and update - encode
        if (!empty($value) && is_array($value)) {
            $value = json_encode($value);
        }
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
        // already decoded in setItemValue for deferred lookup and showView()
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
        // 1. in getItems() set to value from datastore - decode for deferred lookup and showView()
        parent::setItemValue($itemid, $value, $fordisplay);
    }

    /**
     * Get the deferred data object list loader
     */
    public function getDeferredLoader()
    {
        //static::init_deferred($this->defername);
        if (empty(static::$deferred[$this->defername])) {
            static::$deferred[$this->defername] = new DataObjectListLoader($this->objectname, $this->fieldlist);
        }
        return static::$deferred[$this->defername];
    }

    /**
     * Set the data to defer here - in this case the property values
     */
    public function setDataToDefer($itemid, $values)
    {
        if (!empty($values)) {
            if (is_string($values) && !is_numeric($values)) {
                $values = json_decode($values, true);
            }
            if (!is_array($values)) {
                $values = array($values);
            }
            $this->getDeferredLoader()->add($values);
        }
        return $values;
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
        // 1. in showForm() get value from property - not set via setValue in datastore, except in preview - decode
        if (!isset($data['value']) && !empty($this->value)) {
            // @checkme for showForm(), set data['value'] here
            $data['value'] = $this->setDataToDefer($this->_itemid, $this->value);
        }
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
        // 1. in showView() get value from data = from objectlist via getItemValue/setItemValue - skip
        // 2. in showDisplay() get value from property - not set via setValue in datastore - decode
        if (!isset($data['value']) && !empty($this->value)) {
            // @checkme for showDisplay(), set data['value'] here
            $data['value'] = $this->setDataToDefer($this->_itemid, $this->value);
        }
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
            // @checkme for showDisplay(), set data['value'] here
            $values = $this->setDataToDefer($this->_itemid, $this->value);
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
        $data['value'] = $this->getDeferredLoader()->get($values);
        if ($this->singlevalue && is_array($data['value']) && array_key_exists($this->fieldlist[0], reset($data['value']))) {
            $field = $this->fieldlist[0];
            $values = array();
            foreach ($data['value'] as $key => $props) {
                $values[$key] = $props[$field];
            }
            $data['value'] = $values;
        }
        return $data;
    }
}
