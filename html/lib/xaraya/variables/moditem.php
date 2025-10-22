<?php

/**
 * @package core\variables
 * @subpackage variables
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * Class to handle variables linked to an item of a module.
 *
 * Bit different than the others, so lets just start and see
 * where we end up
 */

sys::import('xaraya.variables');
sys::import('xaraya.services.xar');
use Xaraya\Services\xar;

interface IxarModItemVars
{
    public static function get($scope, $name, $itemid = null);
    public static function set($scope, $name, $value, $itemid = null);
    public static function delete($scope, $name, $itemid = null);
}

/**
 * @package core\variables
 * @subpackage variables
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */
class xarModItemVars extends xarVars implements IxarModItemVars
{
    public static function get($scope, $name, $itemid = null)
    {
        if (empty($name)) {
            throw new EmptyParameterException('name');
        }

        // Initialize
        $value = null;

        // Try to get it from the cache
        $cacheCollection = 'ModItem.Variables.' . $scope;
        $cacheName = $itemid . $name;

        if (xar::var()->isCached($cacheCollection, $cacheName)) {
            $value = xar::var()->getCached($cacheCollection, $cacheName);
            return $value;
        }

        // Not in cache, need to retrieve it
        $dbconn = xar::db()->getConn();
        $tables = xar::db()->getTables();

        $module_itemvarstable = $tables['module_itemvars'];
        //unset($modvarid);
        $modvarid = xarModVars::getID($scope, $name);
        if (!$modvarid) {
            return;
        }

        $query = "SELECT value FROM $module_itemvarstable WHERE module_var_id = ? AND item_id = ?";
        $bindvars = [(int) $modvarid, (int) $itemid];

        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars, xar::db()->getFetchNum());

        if (!$result->next()) {
            // No value, return the modvar default
            $value = xarModVars::get($scope, $name);
        } else {
            // We finally found it, update the appropriate cache
            [$value] = $result->getRow();
            xar::var()->setCached($cacheCollection, $cacheName, $value);
        }
        $result->close();
        return $value;
    }

    public static function set($scope, $name, $value, $itemid = null)
    {
        assert(!is_null($value)); /* Not allowed to set a variable to NULL value */
        if (empty($name)) {
            throw new EmptyParameterException('name');
        }

        $dbconn = xar::db()->getConn();
        $tables = xar::db()->getTables();

        $module_itemvarstable = $tables['module_itemvars'];

        // Get the default setting to compare the value against.
        $modsetting = xarModVars::get($scope, $name);

        // We need the variable id
        //unset($modvarid);
        $modvarid = xarModVars::getID($scope, $name);
        if (!$modvarid) {
            throw new VariableNotFoundException($name);
        }

        // First delete it.
        // FIXME: do we really want this ?
        self::delete($scope, $name, $itemid);

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

        $cachename = $itemid . $name;
        xar::var()->setCached('ModItem.Variables.' . $scope, $cachename, $value);

        return true;
    }

    public static function delete($scope, $name, $itemid = null)
    {
        if (empty($name)) {
            throw new EmptyParameterException('name');
        }

        $dbconn = xar::db()->getConn();
        $tables = xar::db()->getTables();

        $module_itemvarstable = $tables['module_itemvars'];
        // We need the variable id
        $modvarid = xarModVars::getID($scope, $name);
        if (!$modvarid) {
            return;
        }
        $query = "DELETE FROM $module_itemvarstable WHERE module_var_id = ? AND item_id = ?";
        $bindvars = [(int) $modvarid, (int) $itemid];
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate($bindvars);
        $cachename = $itemid . $name;
        xar::var()->delCached('ModItem.Variables.' . $scope, $cachename);
        return true;
    }
}
