<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
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
    public $initialization_refobject    = 'objects';    // ID of the object we want to reference
    public $initialization_store_prop   = 'name';       // Name of the property we want to use for storage
    public $initialization_display_prop = 'name';       // Name of the property we want to use for displaying.

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
    }

    // Return a list of array(id => value) for the possible options
    function getOptions()
    {
        // The object we need to query is in $this->initialization_refobject, we display the value of
        // the property in $this->display_prop and the id comes from $this->store_prop
        $object  = DataObjectMaster::getObjectList(array('name' => $this->initialization_refobject));

        // TODO: do we need to check whether the properties are actually in the object?
        $items =  $object->getItems(array (
                                    'sort'     => $this->initialization_display_prop,
                                    'fieldlist'=> array($this->initialization_display_prop,$this->initialization_store_prop))
                             );
        $options = array();
        foreach($items as $item) {
            $options[] = array('id' => $item[$this->initialization_store_prop], 'name' => $item[$this->initialization_display_prop]);
        }
        return $options;
    }

    public function showValidation(Array $data = array())
    {
        if (!isset($data['validation'])) $data['validation'] = $this->configuration;
        $this->parseValidation($data['validation']);
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
        return parent::showValidation($data);
    }

}
?>
