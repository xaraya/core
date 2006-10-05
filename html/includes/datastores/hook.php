<?php
/**
 * Data Store is managed by a hook/utility module
 *
 * @package dynamicdata
 * @subpackage datastores
**/

/**
 * Class to handle hook datastore
 *
 * @package dynamicdata
**/
class Dynamic_Hook_DataStore extends BasicDataStore
{
    /**
     * Get the field name used to identify this property (we use the hook name here)
     */
    function getFieldName(DataProperty &$property)
    {
        // check if this is a known module, based on the name of the property type
        $proptypes = DataPropertyMaster::getPropertyTypes();
        $curtype = $property->type;
        if (!empty($proptypes[$curtype]['name'])) {
            return $proptypes[$curtype]['name'];
        }
    }

    function setPrimary(DataProperty &$property)
    {
        // not applicable !?
    }

    function getItem(array $args = array())
    {
        $modid = $args['modid'];
        $itemtype = $args['itemtype'];
        $itemid = $args['itemid'];
        $modname = $args['modname'];

        foreach (array_keys($this->fields) as $hook) {
            if (xarMod::isAvailable($hook)) {
            // TODO: find some more consistent way to do this !
                $value = xarModAPIFunc($hook,'user','get',
                                       array('modname' => $modname,
                                             'modid' => $modid,
                                             'itemtype' => $itemtype,
                                             'itemid' => $itemid,
                                             'objectid' => $itemid));
                // see if we got something interesting in return
                if (isset($value)) {
                    $this->fields[$hook]->setValue($value);
                }
            }
        }
        return $itemid;
    }

}

?>
