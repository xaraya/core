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
class Dynamic_ModuleVariables_DataStore extends Dynamic_DataStore
{
    public $modname;

    function __construct($name)
    {
        // invoke the default constructor from our parent class
        parent::__construct($name);

        // keep track of the concerned module for module settings
        // TODO: the concerned module is currently hiding in the third part of the data store name :)
        list($fixed1,$fixed2,$modid) = explode('_',$name);
        if (empty($modid)) {
            $modid = xarModGetIDFromName(xarMod::getName());
        }
        $modinfo = xarModGetInfo($modid);
        if (!empty($modinfo['name'])) {
            $this->modname = $modinfo['name'];
        }
    }

    function getItem($args)
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

        // let's cheat a little bit here, and preload everything :-)
        xarMod_getVarsByModule($this->modname);

        foreach ($fieldlist as $field) {
            // get the value from the module variables
        // TODO: use $field.$itemid for modules with several itemtypes ? [like articles :)]
            $value = xarModGetVar($this->modname,$field);
            // set the value for this property
            $this->fields[$field]->setValue($value);
        }
        return $itemid;
    }

    function createItem($args)
    {
        // There's no difference with updateItem() here, because xarModSetVar() handles that
        return $this->updateItem($args);
    }

    function updateItem($args)
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
            xarModSetVar($this->modname,$field,$value);
        }
        return $itemid;
    }

    function deleteItem($args)
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
            xarModDelVar($this->modname,$field);
        }

        return $itemid;
    }

    function getItems($args = array())
    {
        // TODO: not supported by xarMod*Var
    }

    function countItems($args = array())
    {
        // TODO: not supported by xarMod*Var
        return 0;
    }

}

?>
