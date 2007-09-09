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
sys::import('xaraya.datastores.sql.flattable');

class ModuleVariablesDataStore extends FlatTableDataStore
{
    public $modname;

    function __construct($name=null)
    {
        // invoke the default constructor from our parent class
        parent::__construct($name);

        // keep track of the concerned module for module settings
        // TODO: the concerned module is currently hiding in the third part of the data store name :)
        $namepart = explode('_',$name);
		if (empty($namepart[2])) $namepart[2] = 'dynamicdata';
		$this->modname = $namepart[2];
    }

    function getItem(Array $args = array())
    {
		$itemid = !empty($args['itemid']) ? $args['itemid'] : 0;

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        foreach ($fieldlist as $field) {
            // get the value from the module variables
            // TODO: use $field.$itemid for modules with several itemtypes ? [like articles :)]
            $namepart = explode('_',$field);
            $value = unserialize(xarModItemVars::get($this->modname,$namepart[0],$itemid));
            // set the value for this property
			$this->fields[$field]->value = $value;
        }
        return $itemid;
    }

    function createItem(Array $args = array())
    {
        // There's no difference with updateItem() here, because xarModItemVars:set() handles that
        return $this->updateItem($args);
    }

    function updateItem(Array $args = array())
    {
		$itemid = !empty($args['itemid']) ? $args['itemid'] : 0;

        $fieldlist = array_keys($this->fields);
        var_dump($args);exit;
        if (count($fieldlist) < 1) {
            return 0;
        }

        foreach ($fieldlist as $field) {
            // get the value from the corresponding property
            $value = $this->fields[$field]->getValue();
            // skip fields where values aren't set
            if (!isset($value)) {
                continue;
            }
            $namepart = explode('_',$field);
            xarModItemVars::set($this->modname,$namepart[0],serialize($value),$itemid);
        }
        return $itemid;
    }

    function deleteItem(Array $args = array())
    {
		$itemid = !empty($args['itemid']) ? $args['itemid'] : 0;

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        foreach ($fieldlist as $field) {
			$namepart = explode('_',$field);
            xarModItemVars::delete($this->modname,$namepart[0],$itemid);
        }

        return $itemid;
    }

    function getItems(Array $args = array())
    {
    }

    function countItems(Array $args = array())
    {
        // TODO: not supported by xarMod*Var
        return 0;
    }

}

?>
