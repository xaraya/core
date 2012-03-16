<?php
/**
 * @package core
 * @subpackage variables
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * Class to handle variables linked to an item of a module.
 *
 * Bit different than the others, so lets just start and see
 * where we end up
 */

sys::import('xaraya.variables');

interface IxarModItemVars
{
    static function get   ($scope, $name, $itemid = null);
    static function set   ($scope, $name, $value, $itemid = null);
    static function delete($scope, $name, $itemid = null);
}

class xarModItemVars extends xarVars implements IxarModItemVars
{
    static function get($scope, $name, $itemid = null)
    {
        if(empty($name))
            throw new EmptyParameterException('name');

        // Initialize
        $value = null;

        // Try to get it from the cache
        $cacheCollection = 'ModItem.Variables.' . $scope;
        $cacheName = $itemid . $name;

        if (xarCoreCache::isCached($cacheCollection, $cacheName)) {
            $value = xarCoreCache::getCached($cacheCollection, $cacheName);
            return $value;
        }

        // Not in cache, need to retrieve it
        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();

        $module_itemvarstable = $tables['module_itemvars'];
        unset($modvarid);
        $modvarid = xarModVars::getId($scope, $name);
        if(!$modvarid)
            return;

        $query = "SELECT value FROM $module_itemvarstable WHERE module_var_id = ? AND item_id = ?";
        $bindvars = array((int)$modvarid, (int)$itemid);

        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars,ResultSet::FETCHMODE_NUM);

        if(!$result->next()) {
            // No value, return the modvar default
            $value = xarModVars::get($scope, $name);
        } else {
            // We finally found it, update the appropriate cache
            list($value) = $result->getRow();
            xarCoreCache::setCached($cacheCollection, $cacheName, $value);
        }
        $result->close();
        return $value;
    }

    static function set($scope, $name, $value, $itemid = null)
    {
        assert('!is_null($value); /* Not allowed to set a variable to NULL value */');
        if (empty($name)) throw new EmptyParameterException('name');

        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();

        $module_itemvarstable = $tables['module_itemvars'];

        // Get the default setting to compare the value against.
        $modsetting = xarModVars::get($scope, $name);

        // We need the variable id
        unset($modvarid);
        $modvarid = xarModVars::getId($scope, $name);
        if(!$modvarid) throw new VariableNotFoundException($name);

        // First delete it.
        // FIXME: do we really want this ?
        self::delete($scope,$name,$itemid);

        if($value === false) $value = 0;
        if($value === true) $value = 1;
        
        // Only store setting if different from global setting
        if ($value != $modsetting)
        {
            $query = "INSERT INTO $module_itemvarstable
                        (module_var_id, item_id, value)
                      VALUES (?,?,?)";
            $bindvars = array($modvarid, $itemid, (string)$value);
            $stmt = $dbconn->prepareStatement($query);
            $stmt->executeUpdate($bindvars);
        }

        $cachename = $itemid . $name;
        xarCoreCache::setCached('ModItem.Variables.' . $scope, $cachename, $value);

        return true;
    }

    static function delete($scope, $name, $itemid = null)
    {
        if (empty($name)) throw new EmptyParameterException('name');

        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();

        $module_itemvarstable = $tables['module_itemvars'];
        // We need the variable id
        $modvarid = xarModVars::getId($scope, $name);
        if(!$modvarid) return;
        $query = "DELETE FROM $module_itemvarstable WHERE module_var_id = ? AND item_id = ?";
        $bindvars = array((int)$modvarid, (int)$itemid);
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate($bindvars);
        $cachename = $itemid . $name;
        xarCoreCache::delCached('ModItem.Variables.' . $scope, $cachename);
        return true;
    }
}
?>