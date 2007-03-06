<?php
/**
 * Build upon IxarVars to define interface for ModVars
 *
 */
sys::import('xaraya.variables');
interface IxarModVars extends IxarVars
{
    static function getID     ($scope, $name);
    static function delete_all($scope);
}

/**
 * Class to model interface to module variables
 *
 */
class xarModVars extends xarVars implements IxarModVars
{
    private static $preloaded = array(); // Keep track of what module vars (per module) we already had

    /**
     * Get a module variable
     *
     * @access public
     * @param  string $scope The name of the module
     * @param  string $name  The name of the variable
     * @return mixed The value of the variable or void if variable doesn't exist
     * @throws EmptyParameterException
     */
    static function get($scope, $name)
    {
        if (empty($scope)) throw new EmptyParameterException('modName');
        if (empty($name)) throw new EmptyParameterException('name');

        // Initialize
        $value = null;

        // Preload per module, once
        if(!isset(self::$preloaded[$scope]))
            self::preload($scope);

        // Lets first check to see if any of our type vars are already set in the cache.
        $cacheCollection = 'Mod.Variables.' . $scope;

        // Try to get it from the cache
        if (xarCore::isCached($cacheCollection, $name)) {
            $value = xarCore::getCached($cacheCollection, $name);
            return $value;
        }

        // Still no luck, let's do the hard work then
        $modBaseInfo = xarMod::getBaseInfo($scope);
        if (!isset($modBaseInfo)) return; // throw back

        $dbconn =& xarDBGetConn();
        $tables =& xarDBGetTables();

        // Retrieve all the variables for this module at once
        $module_varstable = $tables['module_vars'];
        $query = "SELECT name, value FROM $module_varstable WHERE module_id = ? AND name = ?";
        $bindvars = array((int)$modBaseInfo['systemid'],$name);

        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars,ResultSet::FETCHMODE_NUM);

        if($result->next())
        {
            // Found
            $value = $result->get(2);
            xarCore::setCached($cacheCollection, $result->getString(1), $value);
        }
        $result->close();
        return $value;
    }

    /**
     * PreLoad all module variables for a particular module
     *
     * @author Michel Dalle
     * @access private
     * @param  string $scope Module name
     * @return boolean true on success
     * @throws EmptyParameterException
     * @todo  This has some duplication with xarVar.php
     */
    private static function preload($scope)
    {
        if (empty($scope)) throw new EmptyParameterException('modName');

        $modBaseInfo = xarMod::getBaseInfo($scope);
        if (!isset($modBaseInfo)) return;

        $dbconn =& xarDBGetConn();
        $tables =& xarDBGetTables();

        // Takes the right table basing on module mode
        $module_varstable = $tables['module_vars'];

        $query = "SELECT name, value FROM $module_varstable WHERE module_id = ?";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($modBaseInfo['systemid']),ResultSet::FETCHMODE_ASSOC);

        while ($result->next()) {
            xarCore::setCached('Mod.Variables.' . $scope, $result->getString('name'), $result->get('value'));
        }
        $result->close();

        self::$preloaded[$scope] = true;
        return true;
    }

    /**
     * Set a module variable
     *
     * @access public
     * @param  string $scope The name of the module
     * @param  string $name  The name of the variable
     * @param  mixed  $value The value of the variable
     * @return bool true on success
     * @throws EmptyParameterException
     * @todo  We could delete the item vars for the module with the new value to save space?
     */
    static function set($scope, $name, $value)
    {
        if (empty($scope)) throw new EmptyParameterException('modName');
        if (empty($name)) throw new EmptyParameterException('name');
        assert('!is_null($value); /* Not allowed to set a variable to NULL value */');

        $dbconn =& xarDBGetConn();
        $tables =& xarDBGetTables();
        $modBaseInfo = xarMod::getBaseInfo($scope);
        $module_varstable = $tables['module_vars'];
        // We need the variable id
        unset($modvarid);
        $modvarid = self::getId($scope, $name);

        if($value === false) $value = 0;
        if($value === true)  $value = 1;

        if(!$modvarid) {
            // Not there yet
            $query = "INSERT INTO $module_varstable
                         (module_id, name, value)
                      VALUES (?,?,?)";
            $bindvars = array($modBaseInfo['systemid'],$name,(string)$value);
        } else {
            // Existing one
            $query = "UPDATE $module_varstable SET value = ? WHERE id = ?";
            $bindvars = array((string)$value,$modvarid);
        }
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate($bindvars);

        // Update cache for the variable
        xarCore::setCached('Mod.Variables.' . $scope, $name, $value);
        return true;
    }

    /**
     * Delete a module variable
     *
     * @access public
     * @param  string $scope The name of the module
     * @param  string $name  The name of the variable
     * @return bool true on success
     * @throws EmptyParameterException
     * @todo Add caching for item variables?
     */
    static function delete($scope, $name)
    {
        if (empty($scope)) throw new EmptyParameterException('modName');

        $dbconn =& xarDBGetConn();
        $tables =& xarDBGetTables();
        $modBaseInfo = xarMod::getBaseInfo($scope);

        // Delete all the itemvars derived from this var first
        $modvarid = self::getId($scope, $name);
        // TODO: we should delegate this to moditemvars class somehow
        if($modvarid) {
            $module_itemvarstable = $tables['module_itemvars'];
            $query = "DELETE FROM $module_itemvarstable WHERE xar_mvid = ?";
            $stmt = $dbconn->prepareStatement($query);
            $stmt->executeUpdate(array((int)$modvarid));
        }

        // Now delete the modvar itself
        $module_varstable = $tables['module_vars'];
        // Now delete the module var itself
        $query = "DELETE FROM $module_varstable WHERE module_id = ? AND name = ?";
        $bindvars = array($modBaseInfo['systemid'], $name);
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate($bindvars);

        // Removed it from the cache
        xarCore::delCached('Mod.Variables.' . $scope, $name);
        return true;
    }

    /**
     * Delete all module variables
     *
     * @access public
     * @param  string $scope The name of the module
     * @return bool true on success
     * @throws EmptyParameterException, SQLException
     * @todo Add caching for item variables?
     */
    static function delete_all($scope)
    {
        if(empty($scope)) throw new EmptyParameterException('modName');

        $modBaseInfo = xarMod::getBaseInfo($scope);

        $dbconn =& xarDBGetConn();
        $tables =& xarDBGetTables();

        // Takes the right table basing on module mode
        $module_varstable     = $tables['module_vars'];
        $module_itemvarstable = $tables['module_itemvars'];

        // PostGres (allows only one table in DELETE)
        // MySql: multiple table delete only from 4.0 up
        // Select the id's which need to be removed
        $sql="SELECT $module_varstable.id FROM $module_varstable WHERE $module_varstable.module_id = ?";
        $stmt = $dbconn->prepareStatement($sql);
        $result = $stmt->executeQuery(array($modBaseInfo['systemid']), ResultSet::FETCHMODE_NUM);

        // Seems that at least mysql and pgsql support the scalar IN operator
        $idlist = array();
        while ($result->next()) {
            $idlist[] = $result->getInt(1);
        }
        $result->close();
        unset($result);

        // We delete the module vars and the user vars in a transaction, which either succeeds completely or totally fails
        try {
            $dbconn->begin();
            if(count($idlist) != 0 ) {
                $bindmarkers = '?' . str_repeat(',?', count($idlist) -1);
                $sql = "DELETE FROM $module_itemvarstable WHERE $module_itemvarstable.xar_mvid IN (".$bindmarkers.")";
                $stmt = $dbconn->prepareStatement($sql);
                $result = $stmt->executeUpdate($idlist);
            }

            // Now delete the module vars
            $query = "DELETE FROM $module_varstable WHERE module_id = ?";
            $stmt  = $dbconn->prepareStatement($query);
            $result = $stmt->executeUpdate(array($modBaseInfo['systemid']));
            $dbconn->commit();
        } catch (SQLException $e) {
            // If there was an SQL exception roll back to where we started
            $dbconn->rollback();
            // and raise it again so the handler catches
            // TODO: demote to error? raise other type of exception?
            throw $e;
        }
        return true;
    }

    /**
     * Support function for xarMod*UserVar functions
     *
     * private function which delivers a module user variable
     * id based on the module name and the variable name
     *
     * @access private
     * @param  string $scope The name of the module
     * @param  string $name  The name of the variable
     * @return integer identifier for the variable
     * @throws EmptyParameterException
     * @see xarModUserVars::set(), xarModUserVars::get(), xarModUserVars::delete()
     */
    static function getID($scope, $name)
    {
        // Module name and variable name are both necesary
        if (empty($scope) or empty($name)) throw new EmptyParameterException('modName and/or name');

        // Retrieve module info, so we can decide where to look
        $modBaseInfo = xarMod::getBaseInfo($scope);
        if (!isset($modBaseInfo)) return; // throw back

        if (xarCore::isCached('Mod.GetVarID', $modBaseInfo['name'] . $name)) {
            return xarCore::getCached('Mod.GetVarID', $modBaseInfo['name'] . $name);
        }

        $dbconn =& xarDBGetConn();
        $tables =& xarDBGetTables();

        // Takes the right table basing on module mode
        $module_varstable = $tables['module_vars'];

        $query = "SELECT id FROM $module_varstable WHERE module_id = ? AND name = ?";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array((int)$modBaseInfo['systemid'],$name),ResultSet::FETCHMODE_NUM);
        // If there is no such thing, the callee is responsible, return null
        if(!$result->next()) return;

        // Return the ID
        $modvarid = $result->getInt(1);
        $result->Close();

        xarCore::setCached('Mod.GetVarID', $scope . $name, $modvarid);
        return $modvarid;
    }
}
?>
