<?php
/**
 * Interface declaration for config vars
 *
 */
sys::import('variables');

interface IxarConfigVars extends IxarVars
{}

class xarConfigVars implements IxarConfigVars
{
    /**
     * Sets a configuration variable.
     *
     * @access public
     * @param string name the name of the variable
     * @param mixed value (array,integer or string) the value of the variable
     * @return bool true on success, or false if you're trying to set unallowed variables
     * @todo return states that it should return false if we're setting
     *       unallowed variables.. there is no such code to do that in the function
     */
    public static function set($scope, $name, $value)
    {
        // Nice, but introduces dependency
        return xarVar__SetVarByAlias(null, $name, $value, $prime = null, $description = null, $uid = null, $type = 'configvar');
    }
    
    /**
     * Gets a configuration variable.
     *
     * @access public
     * @param string name the name of the variable
     * @return mixed value of the variable(string), or void if variable doesn't exist
     * @todo do we need these aliases anymore ?
     * @todo return proper site prefix when we can store site vars
     */
    public static function get($scope, $name)
    {
        static $cached = false;
        static $aliases = array('Version_Num' => 'System.Core.VersionNumber',
                                'Version_ID' => 'System.Core.VersionId',
                                'Version_Sub' => 'System.Core.VersionSub');
        
        if (isset($aliases[$name])) {
            $name = $aliases[$name];
        }
        
        if(!$cached)
        {
            self::load();
            $cached = true;
        }

        if ($name == 'Site.DB.TablePrefix') {
            return xarCore_getSystemVar('DB.TablePrefix');
        } elseif ($name == 'System.Core.VersionNumber') {
            return XARCORE_VERSION_NUM;
        } elseif ($name == 'System.Core.VersionId') {
            return XARCORE_VERSION_ID;
        } elseif ($name == 'System.Core.VersionSub') {
            return XARCORE_VERSION_SUB;
        } elseif ($name == 'prefix') {
            // Can we do this another way (dependency)
            return xarDBGetSiteTablePrefix();
        }

        // Nice, but introduces dependency
        $t =xarVar__GetVarByAlias($modname = null, $name, $uid = null, null, $type = 'configvar');

        return $t;
    }
    
    public static function delete($scope, $name)
    {
        // Not supported
        return false;
    }
    
    /**
     * Pre-load site configuration variables
     *
     * @access private
     * @return bool true on success, or void on database error
     * @todo We need some way to delete configuration (useless without a certain module) variables from the table!!!
     * @todo look into removing the serialisation, creole does this when needed, automatically (well, almost)
     */
    private static function load()
    {
        $cacheCollection = 'Config.Variables';

        $dbconn =& xarDBGetConn();
        $tables =& xarDBGetTables();

        $query = "SELECT xar_name, xar_value FROM $tables[config_vars] WHERE xar_modid=?";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array(0),ResultSet::FETCHMODE_ASSOC);

        while ($result->next()) {
            $newval = unserialize($result->getString('xar_value'));
            xarCore::setCached($cacheCollection, $result->getString('xar_name'), $newval);
        }
        $result->Close();

        //Tells the cache system it has already checked this particular table
        //(It's a escape when you are caching at a higher level than that of the
        //individual variables)
        //This whole cache systems must be remade to a central one.    
        xarCore::setCached($cacheCollection, 0, true);

        return true;
    }
}
?>