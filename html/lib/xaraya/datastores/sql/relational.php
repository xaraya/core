<?php
/**
 * Data Store is a series of flat SQL tables (= typical module tables)
 *
 * @package core\datastores
 * @subpackage datastores
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
**/

namespace Xaraya\DataObject\DataStores;

use DataObject;
use DataObjectList;
use DataObjectFactory;
use DataProperty;
use DataPropertyMaster;
use Query;
use BadParameterException;
use Exception;
use sys;

sys::import('xaraya.datastores.sql');

/**
 * Class for relational datastore
 *
 */
class RelationalDataStore extends SQLDataStore
{
    /** @var array<int> */
    private static $_subitems_types = [30069, 30120];
    /** @var string */
    private static $_deferred_property = 'DeferredItemProperty';
    ///** @var mixed */
    //private $encryptor;

    /**
     * Summary of __construct
     * @param mixed $name
     * @param int|string $dbConnIndex connection index of the database if different from Xaraya DB (optional)
     */
    public function __construct($name = null, $dbConnIndex = 0)
    {
        parent::__construct($name, $dbConnIndex);

        /**
         * @deprecated in PHP 7.1.0 and removed in PHP 7.2.0 - see https://www.php.net/manual/en/intro.mcrypt.php
        if (extension_loaded('mcrypt')) {
            // Load the encryption class in case we have encrypted fields
            sys::import('xaraya.encryptor');
            $this->encryptor = xarEncryptor::instance();
        }
         */
    }

    /**
     * Summary of __toString
     * @return string
     */
    public function __toString()
    {
        return "relational";
    }

    /**
     * Get the field name used to identify this property (we use the name of the table field here)
     */
    public function getFieldName(DataProperty &$property)
    {
        if (!is_object($property)) {
            debug($property);
        } // <-- this throws an exception
        // support [database.]table.field syntax
        if (preg_match('/^(.+)\.(\w+)$/', $property->source, $matches)) {
            $table = $matches[1];
            $field = $matches[2];
            return $field;
        }
        return null;
    }

    /**
     * Summary of itemExists
     * @param array<string, mixed> $args
     * @throws \Exception
     * @return bool
     */
    public function itemExists(array $args = [])
    {
        // Get the itemid from the params or from the object definition
        $itemid = $args['itemid'] ?? $this->object->itemid;

        // Make sure we have a primary field
        if (empty($this->object->primary)) {
            throw new Exception(xarML('The object #(1) has no primary key', $this->object->name));
        }

        $q = $this->object->dataquery;
        $primary = $this->object->properties[$this->object->primary]->source;
        $q->eq($primary, (int)$itemid);

        // Run it
        if (!$q->run()) {
            throw new Exception(xarML('Query failed'));
        }
        $result = $q->output();
        return !empty($result);

    }

    /**
     * Summary of getItem
     * @param array<string, mixed> $args
     * @throws \Exception
     * @return mixed
     */
    public function getItem(array $args = [])
    {
        // Get the itemid from the params or from the object definition
        $itemid = $args['itemid'] ?? $this->object->itemid;

        //Make sure we have a primary field
        if (empty($this->object->primary)) {
            throw new Exception(xarML('The object #(1) has no primary key', $this->object->name));
        }

        // Bail if the object has no properties
        if (count($this->object->properties) < 1) {
            return;
        }

        // Complete the dataquery
        $q = $this->object->dataquery;
        $fieldlist = $this->object->getFieldList();
        foreach ($fieldlist as $fieldname) {
            $field = $this->object->properties[$fieldname];

            if (empty($field->source)) {
                if (empty($field->initialization_refobject)) {
                    continue;
                }
                $this->addqueryfields($q, $field->initialization_refobject);
            } else {
                $q->addfield($field->source . ' AS ' . $field->name);
            }
        }
        $primary = $this->object->properties[$this->object->primary]->source;
        $q->eq($primary, (int)$itemid);

        // Run it
        if (!$q->run()) {
            throw new Exception(xarML('Query failed'));
        }
        $result = $q->output();
        if (empty($result)) {
            return;
        }

        // Set the values of the valid properties
        $index = 0;
        foreach ($result as $row) {
            foreach ($fieldlist as $fieldname) {
                // Decrypt if required
                //if (!empty($this->object->properties[$fieldname]->initialization_encrypt)) {
                //    $row[$fieldname] = $this->encryptor->decrypt($row[$fieldname]);
                //}

                // Subitem properties get special treatment
                if (in_array($this->object->properties[$fieldname]->type, self::$_subitems_types)) {
                    $this->setItemValue($itemid, $row, $fieldname, $this->object);
                } elseif ($index < 1) {
                    $this->setValue($row, $fieldname);
                }
            }
            $index++;
        }
        return $itemid;
    }

    /**
     * Create an item in the flat table
     *
     * @param array<string, mixed> $args
     * @return bool true on success, false on failure
     * @throws BadParameterException
     **/
    public function createItem(array $args = [])
    {
        // Get the itemid from the params or from the object definition
        $itemid = $args['itemid'] ?? $this->object->itemid;

        // If no itemid was passed or found on the object, get the next id (or dummy)
        $checkid = false;
        if (empty($itemid)) {
            $itemid = null;
            $checkid = true;
        }

        //Make sure we have a primary field
        if (empty($this->object->primary)) {
            throw new Exception(xarML('The object #(1) has no primary key', $this->object->name));
        }

        // Bail if the object has no properties
        if (count($this->object->properties) < 1) {
            return true;
        }

        $q = clone $this->object->dataquery;
        $q->setType('INSERT');

        // Remove any tables that are foreign
        $q = $this->removeForeignTables($q);

        $q->clearfields();
        foreach ($this->object->fieldlist as $fieldname) {
            $field = $this->object->properties[$fieldname];
            $fieldtablealias = explode('.', $field->source);
            if (empty($field->source)) {
                // Ignore fields with no source
                continue;
            } elseif (isset($args[$field->name])) {
                // We have an override through the method's parameters
                $q->addfield($field->source, $args[$field->name]);
            } elseif ($field->getInputStatus() == DataPropertyMaster::DD_INPUTSTATE_IGNORED) {
                // Ignore the fields with IGNORE status
                continue;
            } elseif (!in_array($fieldtablealias[0], array_keys($q->tables))) {
                // Ignore the fields from tables that are foreign
                continue;
            } elseif ($field->name == $this->object->primary) {
                // Ignore the primary value if not set
                if (!isset($itemid)) {
                    continue;
                }
                $q->addfield($field->source, $itemid);
            } else {
                // No override, just take the value the property already has
                // Encrypt if required
                //if (!empty($field->initialization_encrypt)) {
                //    $fieldvalue = $this->encryptor->encrypt($field->value);
                //} else {
                $fieldvalue = $field->value;
                //}
                $q->addfield($field->source, $fieldvalue);
            }
        }

        // Optimze the query to see if we still have more than 1 table
        $q->optimize();
        // Complete the dataquery
        if (count($q->tables) > 1) {
            // Find the primary and pass it to the query so we know which insert to start with
            $q->primary = $this->object->properties[$this->object->primary]->source;
        }

        // Run it
        $q->clearconditions();
        try {
            $q->run();
        } catch (Exception $e) {
            $message = xarML('The following notional query failed:<br/>');
            $message .= $q->tostring();
            $message .= xarML('<br/>The specific message was:<br/>');
            $message .= $e->getMessage();
            throw new Exception($message);
        }

        // get the last inserted id
        if ($checkid) {
            $parts = explode('.', $this->object->properties[$this->object->primary]->source);
            $itemid = $q->lastid($q->tables[$parts[0]]['name'], $parts[1]);
        }
        unset($q);

        $this->object->properties[$this->object->primary]->value = $itemid;
        return $itemid;
    }

    /**
     * Summary of updateItem
     * @param array<string, mixed> $args
     * @throws \Exception
     * @return mixed
     */
    public function updateItem(array $args = [])
    {
        // Get the itemid from the params or from the object definition
        $itemid = $args['itemid'] ?? $this->object->itemid;

        //Make sure we have a primary field
        if (empty($this->object->primary)) {
            throw new Exception(xarML('The object #(1) has no primary key', $this->object->name));
        }

        // Bail if the object has no properties
        if (count($this->object->properties) < 1) {
            return;
        }

        // Complete the dataquery
        $q = clone $this->object->dataquery;
        $q->setType('UPDATE');

        // Remove any tables that are foreign
        $q = $this->removeForeignTables($q);

        $q->clearfields();
        foreach ($this->object->fieldlist as $fieldname) {
            $field = $this->object->properties[$fieldname];
            $fieldtablealias = explode('.', $field->source);
            if (empty($field->source)) {
                // Ignore fields with no source
                continue;
            } elseif ($field->name == $this->object->primary) {
                // Ignore the primary value
                continue;
            } elseif ($field->getInputStatus() == DataPropertyMaster::DD_INPUTSTATE_IGNORED) {
                // Ignore the fields with IGNORE status
                continue;
            } elseif (!in_array($fieldtablealias[0], array_keys($q->tables))) {
                // Ignore the fields from tables that are foreign
                continue;
            } elseif (isset($args[$field->name])) {
                // We have an override through the methods parameters
                // Encrypt if required
                //if (!empty($field->initialization_encrypt)) {
                //    $args[$field->name] = $this->encryptor->encrypt($args[$field->name]);
                //}
                $q->addfield($field->source, $args[$field->name]);
            } else {
                // No override, just take the value the property already has
                // Encrypt if required
                //if (!empty($field->initialization_encrypt)) {
                //    $fieldvalue = $this->encryptor->encrypt($field->value);
                //} else {
                $fieldvalue = $field->value;
                //}
                $q->addfield($field->source, $fieldvalue);
            }
        }

        // Are we overriding the primary?
        if (isset($itemid)) {
            $q->clearconditions();
            $q->eq($this->object->properties[$this->object->primary]->source, $itemid);
        }

        try {
            $q->run();
        } catch (Exception $e) {
            $message = xarML('The following notional query failed:<br/>');
            $message .= $q->tostring();
            $message .= xarML('<br/>The specific message was:<br/>');
            $message .= $e->getMessage();
            throw new Exception($message);
        }
        unset($q);

        return $itemid;
    }

    /**
     * Summary of deleteItem
     * @param array<string, mixed> $args
     * @throws \Exception
     * @return mixed
     */
    public function deleteItem(array $args = [])
    {
        // Get the itemid from the params or from the object definition
        $itemid = $args['itemid'] ?? $this->object->itemid;

        //Make sure we have a primary field
        if (empty($this->object->primary)) {
            throw new Exception(xarML('The object #(1) has no primary key', $this->object->name));
        }

        // Complete the dataquery
        $q = $this->object->dataquery;
        $q->setType('DELETE');

        // Remove any tables that are foreign
        $q = $this->removeForeignTables($q);

        // CHECKME: should an itemid = 0 indicate deleting all items?
        $q->clearconditions();
        $q->eq($this->object->properties[$this->object->primary]->source, $itemid);
        // Run it
        if (!$q->run()) {
            throw new Exception(xarML('Query failed'));
        }

        return $itemid;
    }

    /**
     * Summary of removeForeignTables
     * @param mixed $q
     * @return mixed
     */
    private function removeForeignTables($q = null)
    {
        // Foreign tables are any tables not among the object's datasources, like subitems
        if (empty($q)) {
            return $q;
        }

        $updatabletables = [];
        foreach ($q->tables as $table) {
            // We cater to simple and composite aliases (object name _ simple alias)
            $fullalias = $this->object->name . "_" . $table['alias'];

            // If this table is not among the data sources igonore it
            if (!isset($this->object->datasources[$table['alias']]) && !isset($this->object->datasources[$fullalias])) {
                continue;
            }

            // Make sure this table is not tagged "foreign" before adding it
            if ((!is_array($this->object->datasources[$table['alias']]) ||
                 $this->object->datasources[$table['alias']][1] != 'foreign')) {
                $updatabletables[$table['alias']] = $table;
            }
        }
        $q->tables = $updatabletables;

        // Remove any links on tables that are foreign
        $updatablelinks = [];
        foreach ($q->tablelinks as $link) {
            $link1 = $q->deconstructfield($link['field1']);
            $link2 = $q->deconstructfield($link['field2']);
            if (isset($this->object->datasources[$link1['table']]) && isset($this->object->datasources[$link2['table']])) {
                if (
                    (is_array($this->object->datasources[$link1['table']]) && $this->object->datasources[$link1['table']][1] == 'foreign') &&
                    (is_array($this->object->datasources[$link2['table']]) && $this->object->datasources[$link2['table']][1] == 'foreign')
                ) {
                    continue;
                }
                $updatablelinks[] = $link;
            }
        }
        $q->tablelinks = $updatablelinks;
        return $q;
    }

    /**
     * Summary of getItems
     * @param array<string, mixed> $args
     * @throws \Exception
     * @return void
     */
    public function getItems(array $args = [])
    {
        if (!empty($args['numitems'])) {
            $numitems = (int)$args['numitems'];
        } else {
            $numitems = 0;
        }
        if (!empty($args['startnum'])) {
            $startnum = (int)$args['startnum'];
        } else {
            $startnum = 1;
        }
        if (!empty($args['itemids'])) {
            $itemids = $args['itemids'];
            // random: removing this as it injects prior results (?) into the current query (see addDataStore method in master.php)
            //		   itemids should be passed via $args['itemids']
            //        } elseif (isset($this->_itemids)) {
            //            $itemids = $this->_itemids;
        } else {
            $itemids = [];
        }
        // @deprecated not actually used in datastores
        // check if it's set here - could be 0 (= empty) too
        if (isset($args['cache'])) {
            $this->cache = $args['cache'];
        }

        $isgrouped = 0;
        if (count($this->groupby) > 0) {
            $isgrouped = 1;
        }
        if (count($itemids) == 0 && !$isgrouped) {
            $saveids = 1;
        } else {
            $saveids = 0;
        }

        // Bail if the object has no properties
        if (count($this->object->properties) < 1) {
            return;
        }

        // Make sure we have a primary field
        //        if (empty($this->object->primary)) throw new Exception(xarML('The object #(1) has no primary key', $this->object->name));

        // Complete the dataquery
        $q = $this->object->dataquery;
        $fieldlist = $this->object->getFieldList();
        foreach ($fieldlist as $fieldname) {
            $field = $this->object->properties[$fieldname];

            // Check if we have a subitems property
            // CHECKME: should we check the property type instead?
            if (empty($field->source)) {
                if (empty($field->initialization_refobject)) {
                    continue;
                }
                $this->addqueryfields($q, $field->initialization_refobject);
            } else {
                $q->addfield($field->source . ' AS ' . $field->name);
            }
        }

        // Make sure we include the primary key, even if it won't be displayed
        if (!empty($this->object->primary) && !in_array($this->object->primary, $this->object->fieldlist)) {
            $q->addfield($this->object->properties[$this->object->primary]->source . ' AS ' . $this->object->primary);
        }
        // CHECKME: the following line makes sure we order the items at least according to ID
        // Is this a good idea?
        if (!empty($this->object->primary)) {
            $q->addorder($this->object->properties[$this->object->primary]->source);
        }

        // add selecting on itemids again for relational tables - moved from objects/list.php
        $select_from_ids = (!empty($this->object->primary) && !empty($itemids) && count($itemids)) > 0;
        if ($select_from_ids) {
            $primarysource = $this->object->properties[$this->object->primary]->source;
            $q->in($primarysource, $itemids);
        }

        if (!empty($numitems)) {
            // Add limits if called for
            $q->setrowstodo($numitems);
            $q->setstartat($startnum);
        }

        // Set the kind of display we want/get
        // Associative: if there are subitems then show a nested array, key is primary field, associative array
        // Raw: no associative array
        if (!isset($args['row_output'])) {
            $args['row_output'] = 'associative';
        }

        // Run the query
        if (!$q->run()) {
            throw new Exception(xarML('Query failed'));
        }

        // Restore the query back to its state before selecting on itemids
        if ($select_from_ids) {
            $q->remove_last_condition();
        }

        // Get the result
        $result = $q->output();
        if (empty($result)) {
            return;
        }
        // Distribute the result to the appropriate properties
        $fordisplay = (isset($args['fordisplay'])) ? $args['fordisplay'] : 0;

        foreach ($result as $key => $row) {
            if ($args['row_output'] == 'associative') {
                // If we want to display the results as a nested set, try using the primary field as a key
                if (!empty($this->object->primary) && isset($row[$this->object->primary])) {
                    // Get the value of the primary key
                    $itemid = $row[$this->object->primary];
                } else {
                    // No primary field: use the row key
                    $itemid = $key;
                }
            } else {
                // If we want to display the results as flat rows (raw output), we cannot use the primary field as a key
                $itemid = $key;
            }

            // Add this itemid to the list
            if ($saveids) {
                $this->_itemids[] = $itemid;
            }

            // Set the values of the valid properties
            foreach ($this->object->fieldlist as $fieldname) {
                // Decrypt if required
                //if (!empty($this->object->properties[$fieldname]->initialization_encrypt)) {
                //    $row[$fieldname] = $this->encryptor->decrypt($row[$fieldname]);
                //}

                $this->setItemValue($itemid, $row, $fieldname, $this->object, $fordisplay, $args['row_output']);
            }
        }
    }

    /**
     * Assign a query result value to its property in the proper object
     * @param mixed $value
     * @param mixed $field
     * @throws \Exception
     * @return void
     */
    private function setValue($value, $field)
    {
        // Is this a subitems property?
        if (in_array($this->object->properties[$field]->type, self::$_subitems_types)) {

            // Ignore if we don't have an object
            $subitemsobjectname = $this->object->properties[$field]->initialization_refobject;
            if (empty($subitemsobjectname)) {
                return;
            }

            // Ignore if the record is a null (by way of the primary index)
            $subitemsobject = $this->object->properties[$field]->subitemsobject;
            if ($value[$subitemsobjectname . "_" . $subitemsobject->primary] == null) {
                return;
            }

            // Assign the appropriate value to each of the subitemsobjct's properties
            $subfieldlist = $subitemsobject->getFieldList();
            foreach ($subfieldlist as $subfield) {
                // If the property is again a subitems property, recall the function
                if (in_array($subitemsobject->properties[$subfield]->type, self::$_subitems_types)) {
                    $this->setValue($value, $field);
                } else {
                    // Convert the source field name to this property's name and assign
                    $sourceparts = explode('.', $subitemsobject->properties[$subfield]->source);
                    $subitemsobject->properties[$subfield]->setValue($value[$subitemsobjectname . "_" . $sourceparts[1]]);
                }
            }
        } elseif (empty($this->object->properties[$field]->source)) {
            // This is some other property with a virtual datasource, ignore it
        } else {
            // This is not a subitems property: assign the value in the usual way
            try {
                $this->object->properties[$field]->value = $value[$this->object->properties[$field]->name];
            } catch(Exception $e) {
                throw new Exception(xarML('Could not assign a value to field #(1). Its source may overlap with another field.', $field));
            }
        }
    }

    /**
     * Summary of setItemValue
     * @param mixed $itemid
     * @param mixed $row
     * @param mixed $field
     * @param DataObjectList $object
     * @param mixed $fordisplay
     * @param mixed $row_output
     * @throws Exception
     * @return void
     */
    private function setItemValue($itemid, $row, $field, $object, $fordisplay = 0, $row_output = 'associative')
    {
        // Is this a subitems property?
        if (in_array($object->properties[$field]->type, self::$_subitems_types)) {

            // Ignore if we don't have an object
            $subitemsobjectname = $object->properties[$field]->initialization_refobject;
            if (empty($subitemsobjectname)) {
                return;
            }

            // Ignore if the record is a null (by way of the primary index)
            /** @var DataObject|DataObjectList $subitemsobject */
            $subitemsobject = $object->properties[$field]->subitemsobject;
            if (!is_object($subitemsobject)) {
                throw new Exception(xarML('The property #(1) has no valid subitems object. Value is: #(2)', $field, $subitemsobject));
            }
            if ($row[$subitemsobjectname . "_" . $subitemsobject->primary] == null) {
                return;
            }

            // Assign the appropriate value to each of the subitemsobejct's properties
            $subfieldlist = $subitemsobject->getFieldList();
            foreach ($subfieldlist as $subfield) {
                // If the property is again a subitems property, call the function again for the subitemsobject
                if (in_array($subitemsobject->properties[$subfield]->type, self::$_subitems_types)) {
                    // First get the value of the primary index, make sure it has been assigned
                    $primary = $subitemsobject->primary;
                    $subitemsobject->properties[$primary]->setValue($row[$subitemsobject->name . "_" . $subitemsobject->properties[$primary]->name]);
                    $subitemid = $subitemsobject->properties[$primary]->value;
                    $this->setItemValue($subitemid, $row, $subfield, $subitemsobject, $fordisplay);
                    // Ignore any other property without a source (for now)
                } elseif (empty($subitemsobject->properties[$subfield]->source)) {
                    continue;
                } else {
                    // Convert the source field name to this property's name and assign
                    // Note we add the subitems to the rows of the parent object list items array
                    // We do this for convenience of calling the items, and because the subobject is defined as an object, not an objectlist
                    $sourceparts = explode('.', $subitemsobject->properties[$subfield]->source);
                    $subobjectid = $row[$subitemsobjectname . "_" . $subitemsobject->primary];
                    if ($row_output == 'associative') {
                        $object->items[$itemid][$subitemsobjectname . "_" . $subfield][$subobjectid] = $row[$subitemsobjectname . "_" . $sourceparts[1]];
                    } else {
                        $object->items[$itemid][$subitemsobjectname . "_" . $subfield] = $row[$subitemsobjectname . "_" . $sourceparts[1]];
                    }
                }
            }
        } elseif ($object->properties[$field] instanceof self::$_deferred_property) {
            // Is this a deferred item property or one of its subclasses?
            $object->properties[$field]->setItemValue($itemid, $row[$object->properties[$field]->name] ?? null, $fordisplay);
        } elseif (empty($object->properties[$field]->source)) {
            // This is some other property with a virtual datasource, ignore it
        } else {
            // This is a  property with a normal datasource: assign the value in the usual way,
            // that is using the property's setItemValue method
            $object->properties[$field]->setItemValue($itemid, $row[$object->properties[$field]->name], $fordisplay);
        }
    }

    /**
     * Add the properties of a subitems object to the getItems query
     * @param Query $query
     * @param mixed $objectname
     * @return void
     */
    private function addqueryfields(Query $query, $objectname)
    {
        $object = DataObjectFactory::getObject(['name' => $objectname]);
        foreach ($object->properties as $property) {
            // Ignore fields that are disabled
            if ($property->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_DISABLED) {
                continue;
            }

            if (empty($property->source)) {
                if (empty($property->initialization_refobject)) {
                    continue;
                }
                $this->addqueryfields($query, $property->initialization_refobject);
            } else {
                $parts = explode('.', $property->source);
                $query->addfield($object->name . "_" . $property->source . ' AS ' . $object->name . "_" . $parts[1]);
            }
        }
    }

    /**
     * Summary of countItems
     * @param array<string, mixed> $args
     * @throws \Exception
     * @return int|null
     */
    public function countItems(array $args = [])
    {
        if (!empty($args['itemids'])) {
            $itemids = $args['itemids'];
        } elseif (isset($this->_itemids)) {
            $itemids = $this->_itemids;
        } else {
            $itemids = [];
        }
        // @deprecated not actually used in datastores
        // check if it's set here - could be 0 (= empty) too
        if (isset($args['cache'])) {
            $this->cache = $args['cache'];
        }

        //Make sure we have a primary field
        //        if (empty($this->object->primary)) throw new Exception(xarML('The object #(1) has no primary key', $this->object->name));

        // Create the query
        $q = clone $this->object->dataquery;
        $q->clearfields();
        //        $q->addfield('COUNT(DISTINCT ' . $this->object->properties[$this->object->primary]->source . ')');
        $q->addfield('COUNT(*)');

        // Run the query
        if (!$q->run()) {
            throw new Exception(xarML('Query failed'));
        }
        $result = $q->row();
        if (empty($result)) {
            return null;
        }

        return (int)current($result);
    }

    /**
     * Summary of getNext
     * @param array<string, mixed> $args
     * @return mixed
     * @deprecated 2.2.0 relies on old datastore fields instead of object properties
     */
    public function getNext(array $args = [])
    {
        static $temp = [];

        $table = $this->name;
        $itemidfield = $this->primary;

        // can't really do much without the item id field at the moment
        if (empty($itemidfield)) {
            return;
        }

        $fieldlist = $this->object->getFieldList();
        // Something to do for us?
        if (count($fieldlist) < 1) {
            return;
        }

        if (!isset($temp['result'])) {
            if (!empty($args['numitems'])) {
                $numitems = $args['numitems'];
            } else {
                $numitems = 0;
            }
            if (!empty($args['startnum'])) {
                $startnum = $args['startnum'];
            } else {
                $startnum = 1;
            }
            if (!empty($args['itemids'])) {
                $itemids = $args['itemids'];
            } elseif (isset($this->_itemids)) {
                $itemids = $this->_itemids;
            } else {
                $itemids = [];
            }

            $query = "SELECT $itemidfield, " . join(', ', $fieldlist) . "
                      FROM $table ";

            $bindvars = [];
            if (count($itemids) > 1) {
                $bindmarkers = '?' . str_repeat(',?', count($itemids) - 1);
                $query .= " WHERE $itemidfield IN ($bindmarkers) ";
                foreach ($itemids as $itemid) {
                    $bindvars[] = (int) $itemid;
                }
            } elseif (count($itemids) == 1) {
                $query .= " WHERE $itemidfield = ? ";
                $bindvars[] = (int)$itemids[0];
            } elseif (count($this->where) > 0) {
                $query .= " WHERE ";
                foreach ($this->where as $whereitem) {
                    $query .= $whereitem['join'] . ' ' . $whereitem['pre'] . $whereitem['field'] . ' ' . $whereitem['clause'] . $whereitem['post'] . ' ';
                }
            }

            // TODO: GROUP BY, LEFT JOIN, ... ? -> cfr. relationships

            if (count($this->sort) > 0) {
                $query .= " ORDER BY ";
                $join = '';
                foreach ($this->sort as $sortitem) {
                    $query .= $join . $sortitem['field'] . ' ' . $sortitem['sortorder'];
                    $join = ', ';
                }
            } else {
                $query .= " ORDER BY $itemidfield";
            }
            // We got the query, prepare it
            $stmt = $this->prepareStatement($query);

            // Now set additional parameters if we need to
            if ($numitems > 0) {
                $stmt->setLimit($numitems);
                $stmt->setOffset($startnum - 1);
            }
            // Execute it
            $result = $stmt->executeQuery($bindvars);
            $temp['result'] = & $result;
        }

        $result = & $temp['result'];

        // Try to fetch the next row
        if (!$result->next()) {
            $result->close();

            $temp['result'] = null;
            return;
        }

        $values = $result->getRow();
        $itemid = array_shift($values);
        // oops, something went seriously wrong here...
        if (empty($itemid) || count($values) != count($this->fields)) {
            $result->close();

            $temp['result'] = null;
            return;
        }

        $this->fields[$itemidfield]->value = $itemid;
        foreach ($fieldlist as $field) {
            // set the value for this property
            $this->fields[$field]->value = array_shift($values);
        }
        return $itemid;
    }
}
