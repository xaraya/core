<?php
/**
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

        if (xarCore::isCached($cacheCollection, $cacheName)) {
            $value = xarCore::getCached($cacheCollection, $cacheName);
            return $value;
        }

        // Not in cache, need to retrieve it
        $dbconn =& xarDBGetConn();
        $tables =& xarDBGetTables();

        $module_itemvarstable = $tables['module_itemvars'];
        unset($modvarid);
        $modvarid = xarModVars::getId($scope, $name);
        if(!$modvarid)
            return;

        $query = "SELECT value FROM $module_itemvarstable WHERE modvar_id = ? AND item_id = ?";
        $bindvars = array((int)$modvarid, (int)$itemid);

        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars,ResultSet::FETCHMODE_NUM);

        if(!$result->next()) {
            // No value, return the modvar default
            $value = xarModVars::get($scope, $name);
        } else {
            // We finally found it, update the appropriate cache
            list($value) = $result->getRow();
            xarCore::setCached($cacheCollection, $cacheName, $value);
        }
        $result->close();
        return $value;
    }

    static function set($scope, $name, $value, $itemid = null)
    {
        assert('!is_null($value); /* Not allowed to set a variable to NULL value */');
        if (empty($name)) throw new EmptyParameterException('name');

        $dbconn =& xarDBGetConn();
        $tables =& xarDBGetTables();

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

        // Only store setting if different from global setting
        if ($value != $modsetting)
        {
            $query = "INSERT INTO $module_itemvarstable
                        (modvar_id, item_id, value)
                      VALUES (?,?,?)";
            $bindvars = array($modvarid, $itemid, (string)$value);
            $stmt = $dbconn->prepareStatement($query);
            $stmt->executeUpdate($bindvars);
        }

        $cachename = $itemid . $name;
        xarCore::setCached('ModItem.Variables.' . $scope, $cachename, $value);

        return true;
    }

    static function delete($scope, $name, $itemid = null)
    {
        if (empty($name)) throw new EmptyParameterException('name');

        $dbconn =& xarDBGetConn();
        $tables =& xarDBGetTables();

        $module_itemvarstable = $tables['module_itemvars'];
        // We need the variable id
        $modvarid = xarModVars::getId($scope, $name);
        if(!$modvarid) return;
        $query = "DELETE FROM $module_itemvarstable WHERE modvar_id = ? AND item_id = ?";
        $bindvars = array((int)$modvarid, (int)$itemid);
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate($bindvars);
        $cachename = $itemid . $name;
        xarCore::delCached('ModItem.Variables.' . $scope, $cachename);
        return true;
    }
}
?>
