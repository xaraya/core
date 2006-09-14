<?php
/**
 * Build upon IxarVars to define interface for ModVars
 *
 */
sys::import('variables');
interface IxarModVars extends IxarVars
{
    static function getID     ($scope, $name);
    static function delete_all($scope);
    static function load      ($scope);
}

/**
 * Class to model interface to module variables
 *
 */
class xarModVars implements IxarModVars
{
    /**
     * Get a module variable
     *
     * @access public
     * @param modName The name of the module
     * @param name The name of the variable
     * @return mixed The value of the variable or void if variable doesn't exist
     * @throws EmptyParameterException
     */
    static function get($modName, $name, $prep = NULL)
    {
        if (empty($modName)) throw new EmptyParameterException('modName');
        return xarVar__GetVarByAlias($modName, $name, $uid = NULL, $prep, 'modvar');
    }

    /**
     * Load all module variables for a particular module
     *
     * @author Michel Dalle
     * @access protected
     * @param modName string
     * @return mixed true on success
     * @throws EmptyParameterException
     * @todo  This has some duplication with xarVar.php
     */
    static function load($modName)
    {
        if (empty($modName)) throw new EmptyParameterException('modName');

        $modBaseInfo = xarMod::getBaseInfo($modName);
        if (!isset($modBaseInfo)) return;

        $dbconn =& xarDBGetConn();
        $tables =& xarDBGetTables();

        // Takes the right table basing on module mode
        $module_varstable = $tables['module_vars'];

        $query = "SELECT xar_name, xar_value FROM $module_varstable WHERE xar_modid = ?";
        $stmt =& $dbconn->prepareStatement($query);
        $result =& $stmt->executeQuery(array($modBaseInfo['systemid']),ResultSet::FETCHMODE_ASSOC);

        while ($result->next()) {
            xarCore::setCached('Mod.Variables.' . $modName, $result->getString('xar_name'), $result->get('xar_value'));
        }
        $result->Close();

        xarCore::setCached('Mod.GetVarsByModule', $modName, true);
        return true;
    }

    /**
     * Set a module variable
     *
     * @access public
     * @param modName The name of the module
     * @param name The name of the variable
     * @param value The value of the variable
     * @return bool true on success
     * @throws EmptyParameterException
     * @todo  We could delete the item vars for the module with the new value to save space?
     */
    static function set($modName, $name, $value)
    {
        if (empty($modName)) throw new EmptyParameterException('modName');
        return xarVar__SetVarByAlias($modName, $name, $value, $prime = NULL, $description = NULL, $uid = NULL, $type = 'modvar');
    }

    /**
     * Delete a module variable
     *
     * @access public
     * @param modName The name of the module
     * @param name The name of the variable
     * @return bool true on success
     * @throws EmptyParameterException
     * @todo Add caching for item variables?
     */
    static function delete($modName, $name)
    {
        if (empty($modName)) throw new EmptyParameterException('modName');
        return xarVar__DelVarByAlias($modName, $name, $uid = NULL, $type = 'modvar');
    }

    /**
     * Delete all module variables
     *
     * @access public
     * @param modName The name of the module
     * @return bool true on success
     * @throws EmptyParameterException, SQLException
     * @todo Add caching for item variables?
     */
    static function delete_all($modName)
    {
        if(empty($modName)) throw new EmptyParameterException('modName');

        $modBaseInfo = xarMod::getBaseInfo($modName);

        $dbconn =& xarDBGetConn();
        $tables =& xarDBGetTables();

        // Takes the right table basing on module mode
        $module_varstable     = $tables['module_vars'];
        $module_itemvarstable = $tables['module_itemvars'];

        // PostGres (allows only one table in DELETE)
        // MySql: multiple table delete only from 4.0 up
        // Select the id's which need to be removed
        $sql="SELECT $module_varstable.xar_id FROM $module_varstable WHERE $module_varstable.xar_modid = ?";
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
            $query = "DELETE FROM $module_varstable WHERE xar_modid = ?";
            $stmt  = $dbconn->prepareStatement($query);
            $result = $stmt->executeUpdate(array($modBaseInfo['systemid']));
            $dbconn->commit();
        } catch (SQLException $e) {
            // If there was an SQL exception roll back to where we started
            $dbconn->rollback();
            // and raise it again so the handler catches
            // TODO: demote to error? rais other type of exception?
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
     * @param modName The name of the module
     * @param name    The name of the variable
     * @return int id identifier for the variable
     * @throws EmptyParameterException
     * @see xarModUserVars::set(), xarModUserVars::get(), xarModUserVars::delete()
     */
    static function getID($modName, $name)
    {
        // Module name and variable name are both necesary
        if (empty($modName) or empty($name)) throw new EmptyParameterException('modName and/or name');

        // Retrieve module info, so we can decide where to look
        $modBaseInfo = xarMod::getBaseInfo($modName);
        if (!isset($modBaseInfo)) return; // throw back

        if (xarCore::isCached('Mod.GetVarID', $modBaseInfo['name'] . $name)) {
            return xarCore::getCached('Mod.GetVarID', $modBaseInfo['name'] . $name);
        }

        $dbconn =& xarDBGetConn();
        $tables =& xarDBGetTables();

        // Takes the right table basing on module mode
        $module_varstable = $tables['module_vars'];

        $query = "SELECT xar_id FROM $module_varstable WHERE xar_modid = ? AND xar_name = ?";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array((int)$modBaseInfo['systemid'],$name),ResultSet::FETCHMODE_NUM);
        // If there is no such thing, the callee is responsible, return null
        if(!$result->next()) return;

        // Return the ID
        $modvarid = $result->getInt(1);
        $result->Close();

        xarCore::setCached('Mod.GetVarID', $modName . $name, $modvarid);
        return $modvarid;
    }
}
?>