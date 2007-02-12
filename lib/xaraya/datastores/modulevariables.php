<?php
/**
 * Data Store is the module variables // TODO: integrate module variable handling with DD
 *
 * @package dynamicdata
 * @subpackage datastores
 */

/**
 * Class to handle module variables datastores
 *
 * @package dynamicdata
 */
class ModuleVariablesDataStore extends BasicDataStore
{
    public $modname;

    function __construct($name=null)
    {
        // invoke the default constructor from our parent class
        parent::__construct($name);

        // keep track of the concerned module for module settings
        // TODO: the concerned module is currently hiding in the third part of the data store name :)
        list($fixed1,$fixed2,$modid) = explode('_',$name);
        if (empty($modid)) {
            $modid = xarMod::getRegID(xarMod::getName());
        }
        $modinfo = xarMod::getInfo($modid);
        if (!empty($modinfo['name'])) {
            $this->modname = $modinfo['name'];
        }
    }

    function getItem(Array $args = array())
    {
        if (empty($args['itemid'])) {
            // by default, there's only 1 item here, except if your module has several
            // itemtypes with different values for the same bunch of settings [like articles :)]
            $itemid = 0;
        } else {
            $itemid = $args['itemid'];
        }

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        foreach ($fieldlist as $field) {
            // get the value from the module variables
            // TODO: use $field.$itemid for modules with several itemtypes ? [like articles :)]
            $value = xarModVars::get($this->modname,$field);
            // set the value for this property
            $this->fields[$field]->setValue($value);
        }
        return $itemid;
    }

    function createItem(Array $args = array())
    {
        // There's no difference with updateItem() here, because xarModVars:set() handles that
        return $this->updateItem($args);
    }

    function updateItem(Array $args = array())
    {
        if (empty($args['itemid'])) {
            // by default, there's only 1 item here, except if your module has several
            // itemtypes with different values for the same bunch of settings [like articles :)]
            $itemid = 0;
        } else {
            $itemid = $args['itemid'];
        }

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        foreach ($fieldlist as $field) {
            // get the value from the corresponding property
            $value = $this->fields[$field]->getValue();
            // skip fields where values aren't set
            if (!isset($value)) {
                continue;
            }
            xarModVars::set($this->modname,$field,$value);
        }
        return $itemid;
    }

    function deleteItem(Array $args = array())
    {
        if (empty($args['itemid'])) {
            // by default, there's only 1 item here, except if your module has several
            // itemtypes with different values for the same bunch of settings [like articles :)]
            $itemid = 0;
        } else {
            $itemid = $args['itemid'];
        }

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        foreach ($fieldlist as $field) {
            xarModVars::delete($this->modname,$field);
        }

        return $itemid;
    }

    function getItems(Array $args = array())
    {
        // TODO: not supported by xarMod*Var
    }

    function countItems(Array $args = array())
    {
        // TODO: not supported by xarMod*Var
        return 0;
    }

}

?>
