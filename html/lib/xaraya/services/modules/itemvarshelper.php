<?php

/**
 * Modules Service Helper for Module Item Variables
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services\Modules;

use EmptyParameterException;
use VariableNotFoundException;

/**
 * Modules Service Helper for Module Item Variables
 */
class ItemVarsHelper extends VarsHelper
{
    public const SLICE = 'modules.item';

    public function get(string $modName, string $varName, mixed $itemid = null): mixed
    {
        // @checkme: this is new behavior for template functions
        if (is_null($itemid)) {
            return parent::get($modName, $varName);
        }
        if (empty($varName)) {
            throw new EmptyParameterException('name');
        }
        $xar = $this->getServicesClass();

        // Initialize
        $value = null;

        $mem = $xar->mem();
        // Try to get it from the cache
        $cacheCollection = 'ModItem.Variables.' . $modName;
        $cacheName = $itemid . $varName;

        if ($mem->has($cacheCollection, $cacheName)) {
            $value = $mem->get($cacheCollection, $cacheName);
            return $value;
        }

        $db = $xar->db();
        // Not in cache, need to retrieve it
        $dbconn = $db->getConn();
        $tables = $db->getTables();

        $module_itemvarstable = $tables['module_itemvars'];

        $modvarid = parent::getID($modName, $varName);
        if (!$modvarid) {
            return null;
        }

        $query = "SELECT value FROM $module_itemvarstable WHERE module_var_id = ? AND item_id = ?";
        $bindvars = [(int) $modvarid, (int) $itemid];

        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars, $db->getFetchNum());

        if (!$result->next()) {
            // No value, return the modvar default
            $value = parent::get($modName, $varName);
        } else {
            // We finally found it, update the appropriate cache
            [$value] = $result->getRow();
            $mem->set($cacheCollection, $cacheName, $value);
        }
        $result->close();
        return $value;
    }

    public function set(string $modName, string $varName, mixed $value, mixed $itemid = null): bool
    {
        // @checkme: this is new behavior for template functions
        if (is_null($itemid)) {
            return false;
        }
        assert(!is_null($value)); /* Not allowed to set a variable to NULL value */
        if (empty($varName)) {
            throw new EmptyParameterException('name');
        }
        $xar = $this->getServicesClass();

        $db = $xar->db();
        $dbconn = $db->getConn();
        $tables = $db->getTables();

        $module_itemvarstable = $tables['module_itemvars'];

        // Get the default setting to compare the value against.
        $modsetting = parent::get($modName, $varName);

        // We need the variable id
        $modvarid = parent::getID($modName, $varName);
        if (!$modvarid) {
            throw new VariableNotFoundException($varName);
        }

        // First delete it.
        // FIXME: do we really want this ?
        $this->delete($modName, $varName, $itemid);

        if ($value === false) {
            $value = 0;
        }
        if ($value === true) {
            $value = 1;
        }

        // Only store setting if different from global setting
        if ($value != $modsetting) {
            $query = "INSERT INTO $module_itemvarstable
                        (module_var_id, item_id, value)
                      VALUES (?,?,?)";
            $bindvars = [$modvarid, $itemid, (string) $value];
            $stmt = $dbconn->prepareStatement($query);
            $stmt->executeUpdate($bindvars);
        }

        $mem = $xar->mem();
        $cachename = $itemid . $varName;
        $mem->set('ModItem.Variables.' . $modName, $cachename, $value);

        return true;
    }

    public function delete(string $modName, string $varName, mixed $itemid = null): bool
    {
        // @checkme: this is new behavior for template functions
        if (is_null($itemid)) {
            return false;
        }
        if (empty($varName)) {
            throw new EmptyParameterException('name');
        }
        $xar = $this->getServicesClass();

        $db = $xar->db();
        $dbconn = $db->getConn();
        $tables = $db->getTables();

        $module_itemvarstable = $tables['module_itemvars'];

        // We need the variable id
        $modvarid = parent::getID($modName, $varName);
        if (!$modvarid) {
            return false;
        }
        $query = "DELETE FROM $module_itemvarstable WHERE module_var_id = ? AND item_id = ?";
        $bindvars = [(int) $modvarid, (int) $itemid];
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate($bindvars);

        $mem = $xar->mem();
        $cachename = $itemid . $varName;
        $mem->del('ModItem.Variables.' . $modName, $cachename);
        return true;
    }
}
