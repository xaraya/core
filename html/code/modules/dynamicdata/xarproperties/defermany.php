<?php
/* Include parent class */
sys::import('modules.dynamicdata.xarproperties.deferitem');

/**
 * The Deferred Many property delays loading related objects based on the itemids until they need to be shown.
 *
 * @todo make this query work for relational datastores: select where caller_id in $values
 *
 * Note: this is for many-to-many relationships stored in a separate object, not for one-to-many objectlinks or subitems
 * The relationships are defined based on the itemid of the source & target objects, stored via a separate link object.
 * The property itself holds no significant value in the database - it may be used to store a cached version someday...
 *
 * Data Objects:
 *    Caller    1
 *     itemid  ---+    LinkName1
 * (*) manyprop1  +-->  caller_id   N   Called1
 *                      called_id  ===>  itemid
 *                                       propname
 *                | M               1|   propname2
 *                +===            <--+   manyprop2 (+)
 * (*) this property
 * (+) For many-to-many relationships, you'll typically have a manyprop2 property in Called1 that points back to Caller
 * For example, films have many actors, and actors play in many films (hopefully).
 *
 * Note: you can have several defer* properties per object, each pointing to a different relationship
 * As a special case, you could have an itemprop on one side and a manyprop on the other side, e.g. an actor only has
 * one home town, but a home town may hold many actors. That case could also be implemented via a listprop (todo)
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
    public $defername  = null;
    public $linkname   = null;
    public $caller_id  = null;
    public $called_id  = null;
    public $targetname = null;
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
     *     linkobject:<linkname>.<caller_id>.<called_id>:<calledname> (= for display link only)
     *     linkobject:<linkname>.<caller_id>.<called_id>:<calledname>.<propname> (for loading propname too - TODO)
     *     linkobject:<linkname>.<caller_id>.<called_id>:<calledname>.<propname>,<propname2>,<propname3>
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
        // make sure we always have at least two parts here
        list($linkpart, $targetpart) = explode(':', substr($value, 11) . ':');
        $this->defername = $linkpart;
        list($linkname, $caller_id, $called_id) = explode('.', $linkpart);
        if (!static::has_resolver($this->defername)) {
            $resolver = deferred_linkobject_resolver($linkname, $caller_id, $called_id);
            static::set_resolver($resolver, $this->defername);
        }
        static::init_deferred($this->defername);
        $this->linkname = $linkname;
        $this->caller_id = $caller_id;
        $this->called_id = $called_id;
        // sorry, you'll have to deal with it directly in the template
        $this->displaylink = null;
        if (!empty($targetpart)) {
            // make sure we always have at least two parts here
            list($object, $field) = explode('.', $targetpart . '.');
            // @checkme support <objectname>.<propname>,<propname2>,<propname3> here too
            $fieldlist = explode(',', $field);
            // @todo add and call resolver for target dataobject once we loaded all links
            if (!empty($fieldlist)) {
                $this->targetname = $targetpart;
                if (!static::has_resolver($this->targetname)) {
                    $resolver = deferred_dataobject_resolver($object, $fieldlist);
                    static::set_resolver($resolver, $this->targetname);
                }
                static::init_deferred($this->targetname);
                static::set_deferred_target($this->defername, $this->targetname);
            }
            $this->objectname = $object;
            $this->fieldlist = $fieldlist;
            // see if we can use a fixed template for display links here
            $this->displaylink = xarServer::getObjectURL($object, 'display', array('itemid' => '[itemid]'));
            if (strpos($this->displaylink, '[itemid]') === false) {
                // sorry, you'll have to deal with it directly in the template
                $this->displaylink = null;
            }
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
            static::add_deferred($this->defername, $itemid);
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
        // @checkme we *do* want to retrieve the data based on the itemid here - extension on deferitem
        $data = $this->getDeferredData($data);
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
            // see if we can use a fixed template for display links - replace itemid in template per value in array
            if (!isset($data['link']) && !empty($this->displaylink) && !empty($data['_itemid'])) {
                //$data['link'] = str_replace('[itemid]', (string) $data['value'], $this->displaylink);
                $data['link'] = $this->displaylink;
            }
            $data['value'] = static::get_deferred($this->defername, $data['_itemid']);
        } elseif (!empty($this->_itemid)) {
            // @checkme for showDisplay(), set data['value'] here
            static::add_deferred($this->defername, $this->_itemid);
            // see if we can use a fixed template for display links - replace itemid in template per value in array
            if (!isset($data['link']) && !empty($this->displaylink) && !empty($this->_itemid)) {
                //$data['link'] = str_replace('[itemid]', (string) $this->_itemid, $this->displaylink);
                $data['link'] = $this->displaylink;
            }
            $data['value'] = static::get_deferred($this->defername, $this->_itemid);
        } else {
            $data['value'] = '';
        }
        $this->value = $data['value'];
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
        if (empty($this->targetname)) {
            return $this->options;
        }
        /**
        // @checkme (ab)use the resolver to retrieve all items from the target here
        $resolver = static::get_resolver($this->targetname);
        if (empty($resolver) || !is_callable($resolver)) {
            return $this->options;
        }
        $items = call_user_func($resolver, $this->targetname, array());
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

    /**
     * Post-processing after loading <whatever> for each $value from 'todo' to 'cache'
     * @todo override in defermany to look up target props if requested
     *
     * @param string $name name of the property
     */
    public static function post_load_deferred($name)
    {
        if (!array_key_exists('target', static::$deferred[$name]) || empty(static::$deferred[$name]['target'])) {
            return true;
        }
        $target = static::$deferred[$name]['target'];
        // @checkme we need to find the unique itemids across all values here - this is probably more memory-efficient
        $distinct = array();
        foreach (static::$deferred[$name]['cache'] as $key => $values) {
            $distinct = array_unique(array_merge($distinct, $values));
        }
        //print_r('We have ' . count($distinct) . ' items for ' . $target . ' to process from ' . $name);
        if (empty($distinct)) {
            return true;
        }
        $resolver = static::get_resolver($target);
        if (empty($resolver) || !is_callable($resolver)) {
            return false;
        }
        $items = call_user_func($resolver, $target, $distinct);
        //print_r('We got ' . count($items) . ' items for ' . $target);
        //var_export($items);
        foreach (array_keys(static::$deferred[$name]['cache']) as $key) {
            $oldvalues = static::$deferred[$name]['cache'][$key];
            $newvalues = array();
            foreach ($oldvalues as $itemid) {
                $id = (string) $itemid;
                if (array_key_exists($id, $items)) {
                    $newvalues[$id] = $items[$id];
                } else {
                    $newvalues[$id] = $id;
                }
            }
            static::$deferred[$name]['cache'][$key] = $newvalues;
        }
        return true;
    }

    /**
     * Set target for post-processing after loading <whatever> for each $value from 'todo' to 'cache'
     *
     * @param string $name name of the deferred property
     * @param string $target name of the target to retrieve dataobject props from - deferred & resolver must exist
     */
    public static function set_deferred_target($name, $target)
    {
        if (array_key_exists('target', static::$deferred[$name]) && $target != static::$deferred[$name]['target']) {
            throw new Exception('Not allowed to switch target for deferred property ' . $name);
        }
        if (!array_key_exists($target, static::$deferred) || !array_key_exists($target, static::$resolver)) {
            throw new Exception('Unknown target ' . $target . ' for deferred property ' . $name);
        }
	static::$deferred[$name]['target'] = $target;
        return true;
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
        // @todo make this query work for relational datastores: select where caller_id in $values
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

