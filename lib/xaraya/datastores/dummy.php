<?php
/**
 * Data Store is a dummy (for in-memory data storage, perhaps)
 *
 * @package dynamicdata
 * @subpackage datastores
 * @todo louzy name
**/

/**
 * Dummy data store class
 *
 * @package dynamicdata
 */
class DummyDataStore extends BasicDataStore
{
    function getItem(Array $args = array())
    {
        $itemid = $args['itemid'];
        foreach (array_keys($this->fields) as $field) {
            $this->fields[$field]->value = $itemid;
        }
    }

    function getItems(Array $args = array())
    {
        if (!empty($args['itemids'])) {
            $itemids = $args['itemids'];
        } elseif (isset($this->_itemids)) {
            $itemids = $this->_itemids;
        } else {
            $itemids = array();
        }
        foreach ($itemids as $itemid) {
            foreach (array_keys($this->fields) as $field) {
                $this->fields[$field]->setItemValue($itemid, $itemid);
            }
        }
    }

    function createItem(Array $args = array())
    {
        $itemid = $args['itemid'];
        foreach (array_keys($this->fields) as $field) {
            if (method_exists($this->fields[$field],'createvalue')) {
                $this->fields[$field]->createValue($itemid);
            }
        }
        return $itemid;
    }

    function updateItem(Array $args = array())
    {
        $itemid = $args['itemid'];
        foreach (array_keys($this->fields) as $field) {
            if (method_exists($this->fields[$field],'updatevalue')) {
                $this->fields[$field]->updateValue($itemid);
            }
        }
        return $itemid;
    }

    function deleteItem(Array $args = array())
    {
        $itemid = $args['itemid'];
        foreach (array_keys($this->fields) as $field) {
            if (method_exists($this->fields[$field],'deletevalue')) {
                $this->fields[$field]->deleteValue($itemid);
            }
        }
        return $itemid;
    }
}

?>
