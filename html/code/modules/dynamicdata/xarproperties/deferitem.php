<?php
/* Include parent class */
sys::import('modules.dynamicdata.class.properties.base');
sys::import('modules.dynamicdata.class.objects.loader');

/**
 * The Deferred Item property delays loading extra information using the database values until they need to be shown.
 * It was inspired by how GraphQL-PHP tackles the N+1 problem, but without proxy, callable or promises (sync or async).
 * Right now this would be the equivalent of lazy loading in batch for showOutput() in object lists :-)
 *
 * Note: this might be an alternative approach for some of the dataquery gymnastics used in some objects and properties
 *
 * The relationships are defined based on the value of the deferred item property, matching the itemid of Called1.
 *
 * Data Objects:
 *    Caller
 *     itemid      1   Called1
 * (*) itemprop1 ---->  itemid
 *                      propname (+)
 *                      propname2
 * (*) this property
 * (+) as an extension, the deferred item could also refer to another property than the itemid in Called1 (todo)
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
  * This property displays a deferred item for a value (experimental - do not use in production)
  *
  * Configuration:
  * the defaultvalue can be set to automatically load an object property if the value contains its itemid,
  */
class DeferredItemProperty extends DataProperty
{
    public $id         = 18281;
    public $name       = 'deferitem';
    public $desc       = 'Deferred Item';
    public $reqmodules = array('dynamicdata');
    public $options    = array();
    public $defername  = null;
    public $objectname = null;
    public $fieldlist  = null;
    public $displaylink = null;
    public $singlevalue = false;
    public static $deferred = array();  // array of $name with deferred data object item loader

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        // Set for runtime
        $this->tplmodule = 'dynamicdata';
        $this->template = 'deferitem';
        $this->filepath = 'modules/dynamicdata/xarproperties';

        // @checkme set dataobject resolver based on defaultvalue = dataobject:<objectname>.<propname>
        $this->parseConfigValue($this->defaultvalue);
        if (empty($this->defername)) {
            $this->defername = $this->name;
            static::init_deferred($this->defername);
        }
    }

    /**
     * The defaultvalue can be set to automatically load an object property if the value contains its itemid
     *
     * Format:
     *     dataobject:<objectname>.<propname>
     *  or dataobject:<objectname>.<propname>,<propname2>,<propname3>
     * Example:
     *     dataobject:roles_users:uname will show the username if the property contains the user id
     *  or dataobject:roles_users:name,uname,email will show the name,uname,email if the property contains the user id
     *
     * @param string $value the defaultvalue used to configure the dataobject resolver function
     */
    public function parseConfigValue($value)
    {
        if (empty($value) || substr($value, 0, 11) !== 'dataobject:') {
            return;
        }
        $objectpart = substr($value, 11);
        $this->defername = $objectpart;
        list($object, $field) = explode('.', $objectpart);
        // @checkme support dataobject:<objectname>.<propname>,<propname2>,<propname3> here too
        $fieldlist = explode(',', $field);
        static::init_deferred($this->defername);
        $this->objectname = $object;
        $this->fieldlist = $fieldlist;
        //$this->getDeferredLoader();
        // see if we can use a fixed template for display links here
        $this->displaylink = xarServer::getObjectURL($object, 'display', array('itemid' => '[itemid]'));
        if (strpos($this->displaylink, '[itemid]') === false) {
            // sorry, you'll have to deal with it directly in the template
            $this->displaylink = null;
        }
        // reset default value and current value after config parsing
        $this->defaultvalue = '';
        $this->value = '';
    }

    /**
     * Get the value of this property (= for a particular object item)
     *
     * @return mixed the value for the property
     */
    public function getValue()
    {
        $this->log_trace();
        //return $this->value;
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
        $value = $this->setDataToDefer($itemid, $value);
        $this->log_trace();
        //$this->value = $value;
        //$this->_items[$itemid][$this->name] = $this->value;
        parent::setItemValue($itemid, $value, $fordisplay);
    }

    /**
     * Get the deferred data object item loader
     */
    public function getDeferredLoader()
    {
        //static::init_deferred($this->defername);
        if (empty(static::$deferred[$this->defername])) {
            static::$deferred[$this->defername] = new DataObjectItemLoader($this->objectname, $this->fieldlist);
        }
        return static::$deferred[$this->defername];
    }

    /**
     * Set the data to defer here - in this case the property value
     */
    public function setDataToDefer($itemid, $value)
    {
        if (isset($value)) {
            $this->getDeferredLoader()->add($value);
        }
        return $value;
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
        if (!isset($data['options'])) {
            $data['options'] = $this->getOptions();
        }
        // @checkme we *don't* really want to retrieve the data based on the value here - extended in defermany
        //$data = $this->getDeferredData($data);
        $this->log_trace();
        //if(!isset($data['value']))       $data['value']    = $this->value;
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
        if (!$this->singlevalue && count($this->fieldlist) == 1) {
            $this->singlevalue = true;
        }
        $data = $this->getDeferredData($data);
        $this->log_trace();
        //if (empty($data['_itemid'])) $data['_itemid'] = 0;
        //if(!isset($data['value']))     $data['value']    = $this->value;
        return parent::showOutput($data);
    }

    /**
     * Get the actual deferred data here
     */
    public function getDeferredData(array $data = array())
    {
        $value = null;
        if (isset($data['value'])) {
            $value = $data['value'];
        } elseif (!empty($this->value)) {
            // @checkme for showDisplay(), set data['value'] here
            $value = $this->setDataToDefer($this->_itemid, $this->value);
        }
        if (empty($value)) {
            return $data;
        }
        //$data['link'] = xarServer::getObjectURL($this->objectname, 'display', array('itemid' => $value));
        // see if we can use a fixed template for display links here
        if (!isset($data['link']) && !empty($this->displaylink)) {
            $data['link'] = str_replace('[itemid]', (string) $value, $this->displaylink);
            $data['source'] = $value;
        }
        $data['value'] = $this->getDeferredLoader()->get($value);
        if ($this->singlevalue && is_array($data['value']) && array_key_exists($this->fieldlist[0], $data['value'])) {
            $data['value'] = $data['value'][$this->fieldlist[0]];
        }
        return $data;
    }

    /**
     * Retrieve the list of options on demand - only used for showInput() here, not validateValue() or elsewhere
     */
    public function getOptions()
    {
        if (count($this->options) > 0) {
            return $this->options;
        }

        $this->options = array();
        //print_r('Getting options: ' . $this->defername);
        // @checkme (ab)use the resolver to retrieve all items here
        $items = $this->getDeferredLoader()->getValues(array());
        $first = reset($items);
        if (is_array($first)) {
            $field = isset($this->fieldlist) ? reset($this->fieldlist) : 'name';
            if (!array_key_exists($field, $first)) {
                // @checkme pick the first field available here?
                $fieldlist = array_keys($first);
                $field = array_shift($fieldlist);
            }
            foreach ($items as $id => $value) {
                $this->options[] = array('id' => $id, 'name' => $value[$field]);
            }
        } else {
            foreach ($items as $id => $value) {
                $this->options[] = array('id' => $id, 'name' => $value);
            }
        }
        return $this->options;
    }

    /**
     * Initialize the deferred load cache for $name
     *
     * @param string $name name of the property
     */
    public static function init_deferred($name)
    {
        if (!isset(static::$deferred[$name])) {
            static::$deferred[$name] = null;
        }
    }

    public function log_trace()
    {
        return;
        try {
            $trace = debug_backtrace(2, 3);
            array_shift($trace);
            $caller = array_shift($trace);
            print_r("<pre>Caller: " . $this->name . ' (' . $this->_itemid . ")\n");
            print_r($caller);
            //print_r("\nTrace:\n");
            //print_r($trace);
            print_r("</pre>");
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }
}
