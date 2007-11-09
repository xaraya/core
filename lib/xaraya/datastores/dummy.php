<?php
/**
 * Data Store is a dummy (for in-memory data storage, perhaps)
 *
 * @package dynamicdata
 * @subpackage datastores
**/

/**
 * This datastore can be used for all sorts non-standard dataproperties.
 * whose storage is particular to them and cannot be
 * easily handled by a common datastore.
 * Categories and Subforms are examples, each of which can store data in their own tables.
 * When creating/updating/deleting items this datastore will check the dataproperty class that called it
 * for a createValue/updateValue/deleteValue method and, if found, execute it.
 *
 * Going this route also allows us to run such operations in the correct sequence, rather than, as currently
 * is the case with subforms, stick them into the checkInput method.

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
