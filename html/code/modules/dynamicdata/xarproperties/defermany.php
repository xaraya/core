<?php
/* Include parent class */
sys::import('modules.dynamicdata.xarproperties.deferitem');

/**
 * The Deferred Many property delays loading related objects based on the itemids until they need to be shown.
 *
 * Note: this is for many-to-many relationships stored in a separate object, not for one-to-many objectlinks or subitems
 *
 * Data Objects:
 *    Caller
 *     itemid   --+    LinkName1
 * (*) manyprop1  +-->  caller_id       Called1
 *                      called_id  --->  itemid
 *                                       otherprop
 * (*) this property
 *
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 */
 
 /**
  * This property displays deferred related objects for an item (experimental - do not use in production)
  *
  * Configuration:
  * the defaultvalue can be set to automatically load related object link properties based on the itemids,
  * or you can use DeferredProperty::set_resolver($resolver, $name) method to set a resolver function
  * or you can inherit this class and override the static override_me_load() method below
  */
class DeferredManyProperty extends DeferredItemProperty
{
    public $id         = 18283;
    public $name       = 'defermany';
    public $desc       = 'Deferred Many';
    public $reqmodules = array('dynamicdata');
    public $options    = array();
    public $linkname   = null;
    public $caller_id  = null;
    public $called_id  = null;
    public $displaylink = null;

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        // Set for runtime
        $this->template = 'defermany';
    }

    /**
     * The defaultvalue can be set to automatically load related object link properties based on the itemids
     *
     * Format:
     *     linkobject:<linkname>.<caller_id>.<called_id>
     * Example:
     *     linkobject:api_films_people.films_id.people_id will show the people involved in the films id (SWAPI)
     *
     * @param string $value the defaultvalue used to configure the linkobject resolver function
     */
    public function parseConfigValue($value)
    {
        if (empty($value) || substr($value, 0, 11) !== 'linkobject:') {
            return;
        }
        list($linkname, $caller_id, $called_id) = explode('.', substr($value, 11));
        if (!static::has_resolver($this->name)) {
            $resolver = deferred_linkobject_resolver($linkname, $caller_id, $called_id);
            static::set_resolver($resolver, $this->name);
            $this->linkname = $linkname;
            $this->caller_id = $caller_id;
            $this->called_id = $called_id;
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
        return parent::getValue();
    }

    /**
     * Set the value of this property (= for a particular object item)
     *
     * @param mixed $value the new value for the property
     */
    public function setValue($value=null)
    {
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
        parent::setItemValue($itemid, $value, $fordisplay);
    }

    /**
     * Set the data to defer here - based on the object itemid here
     */
    public function setDataToDefer($itemid, $value)
    {
        // @checkme we use the itemid as value here
        if (isset($itemid)) {
            static::add_deferred($this->name, $itemid);
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
     * Get the actual deferred data here - based on the object itemid here
     */
    public function getDeferredData(array $data = array())
    {
        // @checkme we use the itemid as value here
        if (isset($data['_itemid'])) {
            $data['value'] = static::get_deferred($this->name, $data['_itemid']);
        } elseif (!empty($this->_itemid)) {
            // @checkme for showDisplay(), set data['value'] here
            static::add_deferred($this->name, $this->_itemid);
            $data['value'] = static::get_deferred($this->name, $this->_itemid);
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
        /**
        // @checkme (ab)use the resolver to retrieve all items here
        $resolver = static::get_resolver($this->name);
        if (empty($resolver) || !is_callable($resolver)) {
            return $this->options;
        }
        $items = call_user_func($resolver, $this->name, array());
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
         */
        return $this->options;
    }
}

/**
 * Deferred resolver for dynamic object links to assign to static::$resolver
 * @todo make resolver configurable via property config (instead of defaultvalue) someday?
 * @todo support combining property lookups on different fields? Not really a use case for this yet...
 *
 * @param string $linkname the name of the linkobject you want to look up
 * @param string $caller_id the name of the caller_id you want to look up
 * @param string $called_id the name of the called_id you want to look up
 * @return callable resolver to return an array of called_id per caller_id
 */
function deferred_linkobject_resolver($linkname = 'api_films_people', $caller_id = 'films_id', $called_id = 'people_id')
{
    /**
     * Deferred resolver function
     * @param string $name name of the property
     * @param array $values list of itemids to load related objects for
     * @uses string $linkname the name of the linkobject you want to look up
     * @uses string $caller_id the name of the caller_id you want to look up
     * @uses string $called_id the name of the called_id you want to look up
     * @return array associative array of all "$caller_id" => array of $called_ids
     */
    $resolver = function ($name, $values) use ($linkname, $caller_id, $called_id) {
        $fieldlist = array($caller_id, $called_id);
        $params = array('name' => $linkname, 'fieldlist' => $fieldlist);
        //$params = array('name' => $object, 'fieldlist' => $fieldlist, 'itemids' => $values);
        $objectlist = DataObjectMaster::getObjectList($params);
        // @todo select where caller_id in $values
        //$params = array('where' => [$caller_id . ' in ' . implode(',', $values)]);
        //$result = $objectlist->getItems($params);
        //print_r('Query ' . $linkname . ' with ' . $caller_id . ' IN (' . implode(',', $values) . ')');
        $objectlist->addWhere($caller_id, 'IN (' . implode(',', $values) . ')');
        $result = $objectlist->getItems();
        $values = array();
        foreach ($result as $itemid => $props) {
            $key = (string) $props[$caller_id];
            if (!array_key_exists($key, $values)) {
                $values[$key] = array();
            }
            $values[$key][] = $props[$called_id];
        }
        return $values;
    };
    return $resolver;
}

