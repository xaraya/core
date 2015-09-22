<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
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
 * Handle the objectreference property
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
    public $reqmodules = array('dynamicdata');

    // We explicitly use names here instead of id's, so we are independent of
    // how dd assigns them at a given time. Otherwise the configuration is not
    // exportable to other sites.
    public $initialization_refobject    = 'objects';    // Name of the object we want to reference
    public $initialization_store_prop   = 'name';       // Name of the property we want to use for storage
    public $initialization_display_prop = 'name';       // Name of the property we want to use for displaying.

    public $store_prop_is_itemid        = true;        // Check if the store_prop is the itemid - assume true for now

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
    }

    public function showInput(Array $data = array())
    {
        // Allow overriding by specific parameters
        if (isset($data['refobject']))    $this->initialization_refobject = $data['refobject'];
        if (isset($data['store_prop']))   $this->initialization_store_prop = $data['store_prop'];
        if (isset($data['display_prop'])) $this->initialization_display_prop = $data['display_prop'];
        if (isset($data['firstline']))    $this->initialization_firstline = $data['firstline'];
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        // Allow overriding by specific parameters
        if (isset($data['refobject']))    $this->initialization_refobject = $data['refobject'];
        if (isset($data['store_prop']))   $this->initialization_store_prop = $data['store_prop'];
        if (isset($data['display_prop'])) $this->initialization_display_prop = $data['display_prop'];
        if (isset($data['firstline']))    $this->initialization_firstline = $data['firstline'];

        if (isset($data['value'])) $this->value = $data['value'];
        if (!empty($this->value) && !isset($data['link'])) {
            // CHECKME: store_prop_is_itemid only gets checked once getOptions() is called later on !
            if (is_numeric($this->value) && $this->store_prop_is_itemid) {
                $data['link'] = xarServer::getObjectURL($this->initialization_refobject, 'display', array('itemid' => $this->value));
            } elseif (is_string($this->value)) {
                $data['link'] = xarServer::getObjectURL($this->initialization_refobject, 'view', array('where' => array($this->initialization_store_prop => $this->value)));
            }
            // TODO: support array values too ?
        }
        return parent::showOutput($data);
    }

    // Return a list of array(id => value) for the possible options
    function getOptions()
    {
        if (!empty($this->options)) return $this->options;

        $options = array();

        // The object we need to query is in $this->initialization_refobject, we display the value of
        // the property in $this->display_prop and the id comes from $this->store_prop

        sys::import('modules.dynamicdata.class.objects.master');
        if ($this->initialization_refobject == 'objects') {
            // In this case need to go directly (rather than get a DD object) to avoid recursion
            if ($this->initialization_display_prop == 'id') $sortprop = "objectid";
            else $sortprop = $this->initialization_display_prop;
            $dbconn = xarDB::getConn();
            $xartable =& xarDB::getTables();
            $q = "SELECT id, name, label, module_id, itemtype, class, filepath,
                urlparam, maxid, config, isalias FROM " . $xartable['dynamic_objects'] . " ORDER BY " . $sortprop;
            $result = $dbconn->executeQuery($q);
            $items = array();
            while ($result->next()) {
            list($objectid, $name, $label, $module_id, $itemtype, $class,
                $filepath, $urlparam, $maxid, $config, $isalias) = $result->fields;

            $items[] = array('objectid' => $objectid,
                             'name'    => $name,
                             'label'   => $label,
                             'moduleid' => $module_id,
                             'itemtype' => $itemtype,
                             'class'   => $class,
                             'filepath'   => $filepath,
                             'urlparam'   => $urlparam,
                             'maxid'   => $maxid,
                             'config'   => $config,
                             'isalias'   => $isalias);
            }
            $object = DataObjectMaster::getObject(array('name' => 'objects'));
        } else {
            $object = DataObjectMaster::getObjectList(array('name' => $this->initialization_refobject));

            $items =  $object->getItems(array (
                                        'sort'     => $this->initialization_display_prop,
                                        'fieldlist'=> array($this->initialization_display_prop,$this->initialization_store_prop),
                                        'fordisplay' => 1)
                                 );
            $object = DataObjectMaster::getObject(array('name' => $this->initialization_refobject));
        }
        
        // Make sure the display and store fields are valid properties of this object
        $fields = $object->getFieldList();
        if (!in_array($this->initialization_display_prop,$fields))
            throw new EmptyParameterException('display_prop: ' . $object->name . '.' .$this->initialization_display_prop);
        if (!in_array($this->initialization_store_prop,$fields))
            throw new EmptyParameterException('store_prop: ' . $object->name . '.' .$this->initialization_store_prop);

        // Check if the store_prop is the itemid
        if ($object->properties[$this->initialization_store_prop]->type == 21) { // itemid
            $this->store_prop_is_itemid = true;
        } else {
            $this->store_prop_is_itemid = false;
        }

        foreach($items as $item) {
            $options[] = array('id' => $item[$this->initialization_store_prop], 'name' => $item[$this->initialization_display_prop]);
        }

        // Save options only when we're dealing with an object list
        if (!empty($this->_items)) {
            $this->options = $options;
        }
        return $options;
    }

    public function showConfiguration(Array $data = array())
    {
        if (!isset($data['configuration'])) $data['configuration'] = $this->configuration;
        $this->parseConfiguration($data['configuration']);
        if (!isset($data['initialization'])) $data['initialization'] = $this->getConfigProperties('initialization',1);

        if (!empty($data['initialization']['initialization_store_prop']['configuration'])) {
            $temp = unserialize($data['initialization']['initialization_store_prop']['configuration']);
            $temp = str_replace  ('#(1)', "'" . $this->initialization_refobject  . "'", $temp);
            $data['initialization']['initialization_store_prop']['configuration'] = serialize($temp);
        }
        if (!empty($data['initialization']['initialization_display_prop']['configuration'])) {
            $temp = unserialize($data['initialization']['initialization_display_prop']['configuration']);
            $temp = str_replace  ('#(1)', "'" . $this->initialization_refobject  . "'", $temp);
            $data['initialization']['initialization_display_prop']['configuration'] = serialize($temp);
        }
        return parent::showConfiguration($data);
    }

}
?>
