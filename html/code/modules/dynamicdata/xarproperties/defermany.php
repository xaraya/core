<?php

/* Include parent class */
sys::import('modules.dynamicdata.xarproperties.deferitem');
sys::import('modules.dynamicdata.class.objects.loader');

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
  */
class DeferredManyProperty extends DeferredItemProperty
{
    public $id         = 18283;
    public $name       = 'defermany';
    public $desc       = 'Deferred Many';
    public $reqmodules = ['dynamicdata'];
    public $options    = [];
    public $defername  = null;
    public $linkname   = null;
    public $caller_id  = null;
    public $called_id  = null;
    public $targetname = null;
    public $displaylink = null;
    public $singlevalue = false;
    public static $deferred = [];  // array of $name with deferred link object item loader

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
     *     linkobject:<linkname>.<caller_id>.<called_id>:<calledname>.<propname> (for loading propname too)
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
        [$linkpart, $targetpart] = explode(':', substr($value, 11) . ':');
        $this->defername = $linkpart;
        [$linkname, $caller_id, $called_id] = explode('.', $linkpart);
        static::init_deferred($this->defername);
        $this->linkname = $linkname;
        $this->caller_id = $caller_id;
        $this->called_id = $called_id;
        //$this->getDeferredLoader();
        // sorry, you'll have to deal with it directly in the template
        $this->displaylink = null;
        if (!empty($targetpart)) {
            // make sure we always have at least two parts here
            [$object, $field] = explode('.', $targetpart . '.');
            // @checkme support <objectname>.<propname>,<propname2>,<propname3> here too
            $fieldlist = explode(',', $field);
            // add and call resolver for target dataobject once we loaded all links
            if (!empty($fieldlist)) {
                // @todo delay creating target resolver until we know which fields to retrieve (if coming from GraphQL)
                $this->targetname = $targetpart;
                //static::init_deferred($this->targetname);
                $this->getDeferredLoader()->setTarget($object, $fieldlist);
            }
            $this->objectname = $object;
            $this->fieldlist = $fieldlist;
            // see if we can use a fixed template for display links here
            $this->displaylink = xarServer::getObjectURL($object, 'display', ['itemid' => '[itemid]']);
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
        // @checkme not really needed here, since we use itemid + linkobject
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
        // 2. in showForm() set for input preview and update - set cache values
        if (!empty($value) && is_array($value)) {
            $value = array_filter($value);
            $this->log_trace();
            if (!empty($this->_itemid) && !empty($this->linkname)) {
                $this->getDeferredLoader()->set($this->_itemid, $value);
            }
            // @checkme not really needed here, since we use itemid + linkobject
            $value = json_encode($value, JSON_NUMERIC_CHECK);
        }
        parent::setValue($value);
    }

    public function createValue($itemid=0)
    {
        // @checkme $itemid is still unknown at this point, since this is called before datastore->createItem()
        $this->updateValue($itemid);
    }

    public function updateValue($itemid=0)
    {
        if (empty($itemid) || empty($this->value) || empty($this->linkname)) {
            return;
        }
        $value = $this->value;
        if (is_string($value)) {
            // @checkme not really needed here, since we use itemid + linkobject
            $value = @json_decode($value, true);
        }
        if (!is_array($value)) {
            throw new Exception("DeferMany Value: " + var_export($value, true));
        }
        $this->getDeferredLoader()->save($itemid, $value);
    }

    public function deleteValue($itemid=0)
    {
        if (empty($itemid) || empty($this->linkname)) {
            return;
        }
        $this->getDeferredLoader()->save($itemid, []);
    }

    /**
     * Get the value of this property for a particular item (= for object lists)
     *
     * @param int $itemid the item id we want the value for
     * @return mixed
     */
    public function getItemValue($itemid)
    {
        // already setDataToDefer in setItemValue for deferred lookup and showView()
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
        // 1. in getItems() set to value from datastore - setDataToDefer for deferred lookup and showView()
        parent::setItemValue($itemid, $value, $fordisplay);
    }

    /**
     * Get the deferred link object item loader
     */
    public function getDeferredLoader()
    {
        //static::init_deferred($this->defername);
        if (empty(static::$deferred[$this->defername])) {
            static::$deferred[$this->defername] = new LinkObjectItemLoader($this->linkname, $this->caller_id, $this->called_id);
        }
        return static::$deferred[$this->defername];
    }

    /**
     * Set the data to defer here - based on the object itemid here
     */
    public function setDataToDefer($itemid, $value)
    {
        // @checkme we use the itemid as value here
        if (!empty($itemid) && !empty($this->linkname)) {
            $this->getDeferredLoader()->add($itemid);
        }
        //return $value;
        return $itemid;
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
    public function showInput(array $data = [])
    {
        if (!$this->singlevalue && !empty($this->fieldlist) && count($this->fieldlist) == 1) {
            $this->singlevalue = true;
        }
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
    public function showOutput(array $data = [])
    {
        return parent::showOutput($data);
    }

    public function importValue(SimpleXMLElement $element)
    {
        // return $this->castType((string)$element->{$this->name});
        // @checkme the list of called_id is added to LinkName1 in updateValue if necessary
        return parent::importValue($element);
    }

    /**
     * Export the list of called_id from LinkName1 here, but don't return the propname values from Called1
     */
    public function exportValue($itemid, $item)
    {
        // return xarVar::prepForDisplay($item[$this->name]);
        // @checkme set the targetLoader to null to avoid retrieving the propname values first - see export_items
        $data = $this->getDeferredData(['value' => $item[$this->name] ?? null, '_itemid' => $itemid]);
        $item[$this->name] = $data['value'];
        if (isset($item[$this->name]) && is_array($item[$this->name])) {
            $item[$this->name] = json_encode($item[$this->name], JSON_NUMERIC_CHECK);
        }
        return parent::exportValue($itemid, $item);
    }

    /**
     * Get the actual deferred data here - based on the object itemid here
     */
    public function getDeferredData(array $data = [])
    {
        if (empty($this->linkname)) {
            return $data;
        }
        // @checkme we use the itemid as value here
        $itemid = null;
        if (isset($data['_itemid'])) {
            $itemid = $data['_itemid'];
        } elseif (!empty($this->_itemid)) {
            // @checkme for showDisplay(), set data['value'] here
            $itemid = $this->setDataToDefer($this->_itemid, $this->value);
        }
        if (empty($itemid)) {
            $data['value'] = '';
            $this->value = $data['value'];
            return $data;
        }
        // see if we can use a fixed template for display links - replace itemid in template per value in array
        if (!isset($data['link']) && !empty($this->displaylink) && !empty($itemid)) {
            //$data['link'] = str_replace('[itemid]', (string) $data['value'], $this->displaylink);
            $data['link'] = $this->displaylink;
        }
        $data['value'] = $this->getDeferredLoader()->get($itemid);
        if ($this->singlevalue && is_array($data['value']) && !empty($data['value']) && array_key_exists($this->fieldlist[0], reset($data['value']))) {
            $field = $this->fieldlist[0];
            $values = [];
            foreach ($data['value'] as $key => $props) {
                $values[$key] = $props[$field];
            }
            $data['value'] = $values;
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

        $this->options = [];
        if (empty($this->targetname)) {
            return $this->options;
        }
        /** */
        // @checkme (ab)use the resolver to retrieve all items from the target here
        $target = $this->getDeferredLoader()->getTarget();
        if (empty($target)) {
            return $this->options;
        }
        $items = $target->getValues([]);
        $first = reset($items);
        $field = isset($this->fieldlist) ? reset($this->fieldlist) : 'name';
        if ($first !== false && !array_key_exists($field, $first)) {
            // @checkme pick the first field available here?
            $fieldlist = array_keys($first);
            $field = array_shift($fieldlist);
        }
        foreach ($items as $id => $value) {
            $this->options[] = ['id' => $id, 'name' => $value[$field]];
        }
        /** */
        return $this->options;
    }
}
