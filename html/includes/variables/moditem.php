<?php
/*
    Class to handle variables linked to an item of a module.
    Bit different than the others, so lets just start and see
    where we end up
*/

sys::import('variables');

interface IxarModItemVars
{
    static function get   ($scope, $name, $itemid = null);
    static function set   ($scope, $name, $value, $itemid = null);
    static function delete($scope, $name, $itemid = null);
}

class xarModItemVars implements IxarModItemVars
{
    static function get($scope, $name, $itemid = null)
    {
        if (empty($name)) throw new EmptyParameterException('name');
        if (empty($prep)) $prep = XARVAR_PREP_FOR_NOTHING;

        // Lets first check to see if any of our type vars are alread set in the cache.
        $cacheName = $name;
        $cacheCollection = 'ModItem.Variables.' . $scope;
        $cacheName = $itemid . $name;

        if (xarCore::isCached($cacheCollection, $cacheName)) {
            $value = xarCore::getCached($cacheCollection, $cacheName);
            if (!isset($value)) {
                return;
            } else {
                if ($prep == XARVAR_PREP_FOR_DISPLAY){
                    $value = xarVarPrepForDisplay($value);
                } elseif ($prep == XARVAR_PREP_FOR_HTML){
                    $value = xarVarPrepHTMLDisplay($value);
                }
                return $value;
            }
        } elseif (xarCore::isCached($cacheCollection, 0)) {
            //variable missing.
            return;
        }

        // Still no luck, let's do the hard work then
        $baseinfotype = 'module';

        $modBaseInfo = xarMod::getBaseInfo($scope, $baseinfotype);
        if (!isset($modBaseInfo)) return; // throw back

        $dbconn =& xarDBGetConn();
        $tables =& xarDBGetTables();
        $bindvars = array();

         $module_itemvarstable = $tables['module_itemvars'];
         unset($modvarid);
         $modvarid = xarModVars::getId($scope, $name);
         if (!$modvarid) return;

         $query = "SELECT xar_value FROM $module_itemvarstable WHERE xar_mvid = ? AND xar_itemid = ?";
         $bindvars = array((int)$modvarid, (int)$itemid);

        // TODO : Here used to be a resultset cache option, reconsider it
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars,ResultSet::FETCHMODE_NUM);

        if ($result->getRecordCount() == 0) {
            $result->close(); unset($result);

            // If there is no such thing, return the global setting for moditemvars
            return xarModVars::get($scope, $name);
        }

        // We finally found it, update the appropriate cache
        $result->next();
        list($value) = $result->getRow();
        xarCore::setCached($cacheCollection, $cacheName, $value);
        $result->Close();

        // Optionally prepare it
        // FIXME: This may sound convenient now, feels wrong though, prepping introduces
        //        an unnecessary dependency here.
        if ($prep == XARVAR_PREP_FOR_DISPLAY){
            $value = xarVarPrepForDisplay($value);
        } elseif ($prep == XARVAR_PREP_FOR_HTML){
            $value = xarVarPrepHTMLDisplay($value);
        }

        return $value;
    }
    
    static function set($scope, $name, $value, $itemid=NULL)
    {
        assert('!is_null($value); /* Not allowed to set a variable to NULL value */');
        if (empty($name)) throw new EmptyParameterException('name');

        $modBaseInfo = xarMod::getBaseInfo($scope);
        if(!isset($modBaseInfo)) throw new ModuleNotFoundException($scope);

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
        if ($value != $modsetting) {
            $query = "INSERT INTO $module_itemvarstable
                        (xar_mvid, xar_itemid, xar_value)
                    VALUES (?,?,?)";
            $bindvars = array($modvarid, $itemid, (string)$value);
        }

        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate($bindvars);

        $cachename = $itemid . $name;
        xarCore::setCached('ModItem.Variables.' . $scope, $cachename, $value);

        return true;
    }
    
    static function delete($scope, $name, $itemid = null)
    {
        if (empty($name)) throw new EmptyParameterException('name');

        $dbconn =& xarDBGetConn();
        $tables =& xarDBGetTables();

        $modBaseInfo = xarMod::getBaseInfo($scope);
        if (!isset($modBaseInfo)) return; // throw back
        $module_itemvarstable = $tables['module_itemvars'];
        // We need the variable id
        $modvarid = xarModVars::getId($scope, $name);
        if(!$modvarid) return;
        $query = "DELETE FROM $module_itemvarstable WHERE xar_mvid = ? AND xar_itemid = ?";
        $bindvars = array((int)$modvarid, (int)$itemid);
        $dbconn->execute($query,$bindvars);
        $cachename = $itemid . $name;
        xarCore::delCached('ModItem.Variables.' . $scope, $cachename);
        return true;
    }
}
?>