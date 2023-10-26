<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @todo match the type of the local field to the store property type (must be the same)
 * @todo extra option to limit displaying
 * @todo rules for when the referenced object prop value gets deleted etc.
 * @todo foreign keys which consist of multiple attributes (bad design, but in practice it might come in handy)
 * @todo make the different loops a bit more efficient.
 */

sys::import('modules.base.xarproperties.dropdown');

/**
 * This property displays a dropdown of items of a dataproperty
 *
 * DataObject Reference Property (foreign key like dropdown)
 * You can specify the to be referenced object and what property values
 * to use for displayinig and to store in the (foreign key) field
 */
class ObjectRefProperty extends SelectProperty
{
    public $id         = 507;
    public $name       = 'objectref';
    public $desc       = 'Object Dropdown';
    public $reqmodules = ['dynamicdata'];

    // We explicitly use names here instead of id's, so we are independent of
    // how dd assigns them at a given time. Otherwise the configuration is not
    // exportable to other sites.
    public $initialization_refobject    = 'objects';    // Name of the object we want to reference
    public $initialization_store_prop   = 'name';       // Name of the property we want to use for storage
    public $initialization_display_prop = 'name';       // Name of the property we want to use for displaying.

    public $store_prop_is_itemid        = true;        // Check if the store_prop is the itemid - assume true for now

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
    }

    /**
     * Display a dropdown of items for input
     *
     * @param array<string, mixed> $data An array of input parameters
     * @return string     HTML markup to display the property for input on a web page
     */
    public function showInput(array $data = [])
    {
        // Allow overriding by specific parameters
        if (isset($data['refobject'])) {
            $this->initialization_refobject = $data['refobject'];
        }
        if (isset($data['store_prop'])) {
            $this->initialization_store_prop = $data['store_prop'];
        }
        if (isset($data['display_prop'])) {
            $this->initialization_display_prop = $data['display_prop'];
        }
        if (isset($data['firstline'])) {
            $this->initialization_firstline = $data['firstline'];
        }
        return parent::showInput($data);
    }

    /**
     * Display a dropdown of items for output
     *
     * @param array<string, mixed> $data An array of input parameters
     * @return string     HTML markup to display the property for output on a web page
     */
    public function showOutput(array $data = [])
    {
        // Allow overriding by specific parameters
        if (isset($data['refobject'])) {
            $this->initialization_refobject = $data['refobject'];
        }
        if (isset($data['store_prop'])) {
            $this->initialization_store_prop = $data['store_prop'];
        }
        if (isset($data['display_prop'])) {
            $this->initialization_display_prop = $data['display_prop'];
        }
        if (isset($data['firstline'])) {
            $this->initialization_firstline = $data['firstline'];
        }

        if (isset($data['value'])) {
            $this->value = $data['value'];
        }
        if (!empty($this->value) && !isset($data['link'])) {
            // CHECKME: store_prop_is_itemid only gets checked once getOptions() is called later on !
            if (is_numeric($this->value) && $this->store_prop_is_itemid) {
                $data['link'] = xarServer::getObjectURL($this->initialization_refobject, 'display', ['itemid' => $this->value]);
            } elseif (is_string($this->value)) {
                $data['link'] = xarServer::getObjectURL($this->initialization_refobject, 'view', ['where' => $this->initialization_display_prop . " = '" . $this->value . "'"]);
            } else {
                echo xarML('Array values for links are currently not supported in the objectref property');
                exit;
            }
        }
        return parent::showOutput($data);
    }

    // Return a list of array(id => value) for the possible options
    public function getOptions()
    {
        if (!empty($this->options)) {
            return $this->options;
        }

        $options = [];

        // The object we need to query is in $this->initialization_refobject, we display the value of
        // the property in $this->display_prop and the id comes from $this->store_prop

        sys::import('modules.dynamicdata.class.objects.master');
        if ($this->initialization_refobject == 'objects') {
            // In this case need to go directly (rather than get a DD object) to avoid recursion
            if ($this->initialization_display_prop == 'id') {
                $sortprop = "objectid";
            } else {
                $sortprop = $this->initialization_display_prop;
            }
            $dbconn = xarDB::getConn();
            $xartable = & xarDB::getTables();
            $q = "SELECT id, name, label, module_id, itemtype, class, filepath,
                urlparam, maxid, config, isalias FROM " . $xartable['dynamic_objects'] . " ORDER BY " . $sortprop;
            $result = $dbconn->executeQuery($q);
            $items = [];
            while ($result->next()) {
                [$objectid, $name, $label, $module_id, $itemtype, $class,
                    $filepath, $urlparam, $maxid, $config, $isalias] = $result->fields;

                $items[] = ['objectid' => $objectid,
                                 'name'    => $name,
                                 'label'   => $label,
                                 'moduleid' => $module_id,
                                 'itemtype' => $itemtype,
                                 'class'   => $class,
                                 'filepath'   => $filepath,
                                 'urlparam'   => $urlparam,
                                 'maxid'   => $maxid,
                                 'config'   => $config,
                                 'isalias'   => $isalias];
            }
            $object = DataObjectMaster::getObject(['name' => 'objects']);
        } else {
            $object = DataObjectMaster::getObjectList(['name' => $this->initialization_refobject]);

            $items =  $object->getItems(
                [
                                        'sort'     => $this->initialization_display_prop,
                                        'fieldlist' => [$this->initialization_display_prop,$this->initialization_store_prop],
                                        'fordisplay' => 1]
            );
            $object = DataObjectMaster::getObject(['name' => $this->initialization_refobject]);
        }

        // Make sure the display and store fields are valid properties of this object
        $fields = $object->getFieldList();
        if (!in_array($this->initialization_display_prop, $fields)) {
            throw new EmptyParameterException('display_prop: ' . $object->name . '.' .$this->initialization_display_prop);
        }
        if (!in_array($this->initialization_store_prop, $fields)) {
            throw new EmptyParameterException('store_prop: ' . $object->name . '.' .$this->initialization_store_prop);
        }

        // Check if the store_prop is the itemid
        if (DataPropertyMaster::isPrimaryType($object->properties[$this->initialization_store_prop]->type)) { // itemid
            $this->store_prop_is_itemid = true;
        } else {
            $this->store_prop_is_itemid = false;
        }

        foreach($items as $item) {
            $options[] = ['id' => $item[$this->initialization_store_prop], 'name' => $item[$this->initialization_display_prop]];
        }

        // Save options only when we're dealing with an object list
        if (!empty($this->_items)) {
            $this->options = $options;
        }
        return $options;
    }

    /**
     * Show the current configuration rules for this property type
     *
     * @param array<string, mixed> $data An array of input parameters
     * @return string containing the HTML (or other) text to output on a web page
     */
    public function showConfiguration(array $data = [])
    {
        if (!isset($data['configuration'])) {
            $data['configuration'] = $this->configuration;
        }
        $this->parseConfiguration($data['configuration']);
        if (!isset($data['initialization'])) {
            $data['initialization'] = $this->getConfigProperties('initialization', 1);
        }

        if (!empty($data['initialization']['initialization_store_prop']['configuration'])) {
            $temp = unserialize($data['initialization']['initialization_store_prop']['configuration']);
            $temp = str_replace('#(1)', "'" . $this->initialization_refobject  . "'", $temp);
            $data['initialization']['initialization_store_prop']['configuration'] = serialize($temp);
        }
        if (!empty($data['initialization']['initialization_display_prop']['configuration'])) {
            $temp = unserialize($data['initialization']['initialization_display_prop']['configuration']);
            $temp = str_replace('#(1)', "'" . $this->initialization_refobject  . "'", $temp);
            $data['initialization']['initialization_display_prop']['configuration'] = serialize($temp);
        }
        return parent::showConfiguration($data);
    }

    public function preList()
    {
        // Bail if there is no parent object
        if (empty($this->objectref)) {
            return true;
        }

        // Get the object associated with this property
        if ($this->objectref->name == $this->initialization_refobject) {
            // Case of the same table in the property and its parent object
            $object = $this->objectref;
        } else {
            // Property table is different from the object table
            $object = DataObjectMaster::getObject(['name' => $this->initialization_refobject]);
        }

        // We only support relational storage
        if (!$object || !$object->datastore) {
            return true;
        }
        $store = $object->datastore->name;
        if ($object->datastore->name != "relational") {
            return true;
        }

        // Assemble the links to the object's table - @todo see DataObjectMaster assembleQuery instead
        $descriptor  = $object->descriptor;
        $sources     = $descriptor->exists("sources") ? unserialize($descriptor->get("sources") ?? 'a:0:{}') : [];
        $relations   = $descriptor->exists("relations") ? unserialize($descriptor->get("relations") ?? 'a:0:{}') : [];

        // Debug display
        if (xarModVars::get('dynamicdata', 'debugmode') &&
        in_array(xarUser::getVar('id'), xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
            echo "Ref Object: " . $this->objectref->name . "<br/>";
            echo "Property: " . $this->name . "<br/>";
            echo "Prop Object: " . $object->name . "<br/>";
            echo "Sources: ";
            var_dump($sources);
            echo "<br/>";
            echo "Relations: ";
            var_dump($relations);
            echo "<br/>";
            echo "<br/>";
        }

        // Get the parent object's query;
        $q = $this->objectref->dataquery;

        // The tables of this property will be added with a special prefix
        // to make sure all tables are unique
        $tableprefix = $this->id . "_";

        // Run through each of the sources and create a table entry
        // The first table is linked with a join to the current object's source table(s)
        // By definition this is an outer join
        // The other relations are added as given in the configurations
        $storeprop   = $tableprefix . $object->properties[$this->initialization_store_prop]->source;
        $displayprop = $tableprefix . $object->properties[$this->initialization_display_prop]->source;
        $i = 0;
        foreach($sources as $key => $value) {
            $q->addTable($value[0], $tableprefix . $key);
            if ($i == 0) {
                $q->leftjoin($this->source, $storeprop);
            } else {
                if ($value[1] == 'internal') {
                    $q->join($tableprefix . $relations[$i - 1][0], $tableprefix . $relations[$i - 1][1]);
                } else {
                    $q->leftjoin($tableprefix . $relations[$i - 1][0], $tableprefix . $relations[$i - 1][1]);
                }
            }
            $i++;
        }

        // Set the source of this property
        $this->source = $displayprop;
        // Do not transform the raw value
        $this->transform = false;
        return true;
    }
}
