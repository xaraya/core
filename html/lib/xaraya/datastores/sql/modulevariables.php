<?php
/**
 * Data Store is the module variables // TODO: integrate module variable handling with DD
 *
 * @package core\datastores
 * @subpackage datastores
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

/**
 * Class to handle module variables datastores
 *
 */
sys::import('xaraya.datastores.sql.relational');

class ModuleVariablesDataStore extends RelationalDataStore
{
    /** @var string */
    public $modulename;
    /** @var string */
    public $variablename;

    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->setModvarName($name);
    }

    public function __toString()
    {
        return "module_variables";
    }

    /**
     * Summary of setModvarName
     * @param ?string $name
     * @throws \Exception
     * @return void
     */
    private function setModvarName($name = "")
    {
        if (empty($name)) {
            throw new Exception('Bad modvar name');
        }
        $this->modulename = $name;
    }

    public function getFieldName(DataProperty &$property)
    {
        return $property->name;
    }

    public function getItem(array $args = [])
    {
        $this->setModvarName($this->name);
        $itemid = !empty($args['itemid']) ? $args['itemid'] : 0;
        $fieldlist = $this->object->getFieldList();
        if (count($fieldlist) < 1) {
            return;
        }
        foreach ($fieldlist as $field) {
            $value = xarModItemVars::get($this->modulename, $field, $itemid);
            // set the value for this property
            $this->object->properties[$field]->value = $value;
        }
        return $itemid;
    }

    public function createItem(array $args = [])
    {
        return $this->updateItem($args);
    }

    public function updateItem(array $args = [])
    {
        $itemid = !empty($args['itemid']) ? $args['itemid'] : 0;
        $fieldlist = $this->object->getFieldList();
        if (count($fieldlist) < 1) {
            return 0;
        }

        foreach ($fieldlist as $field) {
            // get the value from the corresponding property
            $value = $this->object->properties[$field]->value;
            // skip fields where values aren't set
            if (!isset($value)) {
                continue;
            }
            if (empty($itemid)) {
                xarModVars::set($this->modulename, $field, $value);
            } else {
                xarModItemVars::set($this->modulename, $field, $value, $itemid);
            }
        }
        return $itemid;
    }

    public function deleteItem(array $args = [])
    {
        $itemid = !empty($args['itemid']) ? $args['itemid'] : 0;
        $fieldlist = $this->object->getFieldList();
        if (count($fieldlist) < 1) {
            return 0;
        }

        foreach ($fieldlist as $field) {
            xarModItemVars::delete($this->modulename, $field, $itemid);
        }

        return $itemid;
    }

    public function getItems(array $args = [])
    {
        // FIXME: only the last clause has been done!!

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
        // @deprecated not actually used in datastores
        // check if it's set here - could be 0 (= empty) too
        if (isset($args['cache'])) {
            $this->cache = $args['cache'];
        }

        $properties = $this->object->getProperties();
        if (count($properties) < 1) {
            return;
        }

        $modvars = $this->getTable('module_vars');
        $moditemvars = $this->getTable('module_itemvars');

        $modulefields = [];
        // split the fields to be gotten up by module
        foreach ($properties as $field) {
            if (empty($field->source)) {
                continue;
            }
            $this->setModvarName($field->source);
            $modulefields[$this->modulename] ??= [];
            $modulefields[$this->modulename][] = $field->name;
        }
        foreach ($modulefields as $key => $values) {
            if (count($values) < 1) {
                continue;
            }
            $modid = xarMod::getID(substr(trim($key), 17));
            $bindmarkers = '?' . str_repeat(',?', count($values) - 1);
            // include module variable as default
            $query = "SELECT DISTINCT m.name,
                             m.value,
                             mi.item_id,
                             mi.value
                        FROM $modvars m LEFT JOIN $moditemvars mi ON m.id = mi.module_var_id
                       WHERE m.name IN ($bindmarkers) AND m.module_id = $modid";
            $stmt = $this->prepareStatement($query);
            $result = $stmt->executeQuery($values);

            $itemidlist = [];
            while ($result->next()) {
                [$field, $default, $itemid, $value] = $result->getRow();
                if (empty($itemid)) {
                    $itemid = 0;
                    $value = $default;
                }
                // if ($key != 'dynamic_data') $field .= '_' . $key;
                $itemidlist[$itemid] = 1;
                if (isset($value)) {
                    // add the item to the value list for this property
                    $properties[$field]->setItemValue($itemid, $value);
                }
            }
            // add the itemids to the list
            $this->_itemids = array_keys($itemidlist);
            $result->close();
        }
    }

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

        $modvars = $this->getTable('module_vars');
        $moditemvars = $this->getTable('module_itemvars');

        $properties = $this->object->getProperties();

        $modulefields = [];
        // split the fields to be gotten up by module
        foreach ($properties as $field) {
            if (empty($field->source)) {
                continue;
            }
            $this->setModvarName($field->source);
            $modulefields[$this->modulename] ??= [];
            $modulefields[$this->modulename][] = $field->name;
        }
        // include module variable as default
        $numitems = 1;
        foreach ($modulefields as $key => $values) {
            if (count($values) < 1) {
                continue;
            }
            $modid = xarMod::getID(substr(trim($key), 17));
            $bindmarkers = '?' . str_repeat(',?', count($values) - 1);
            if($this->getType() == 'sqlite') {
                $query = "SELECT COUNT(*)
                          FROM (SELECT DISTINCT mi.item_id FROM $modvars m INNER JOIN $moditemvars mi ON m.id = mi.module_var_id
                          WHERE m.name IN ($bindmarkers)) AND m.module_id = $modid";
            } else {
                $query = "SELECT COUNT(DISTINCT mi.item_id)
                          FROM $modvars m INNER JOIN $moditemvars mi ON m.id = mi.module_var_id
                          WHERE m.name IN ($bindmarkers) AND m.module_id = $modid";
            }

            $stmt = $this->prepareStatement($query);
            $result = $stmt->executeQuery($values);
            if (!$result->first()) {
                return null;
            }

            $numitems += $result->getInt(1);
            $result->close();
        }
        return $numitems;
    }

}
