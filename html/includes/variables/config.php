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
        // FIXME: do we really want that ?
        // This way, worst case: 3 queries:
        // 1. deleting it
        // 2. Getting a new id (for some backends)
        // 3. inserting it.
        // Question is wether we want to invent new configvars on the fly or not
        self::delete(null,$name);

        $dbconn =& xarDBGetConn();
        $tables =& xarDBGetTables();
        $config_varsTable = $tables['config_vars'];

        //Here we serialize the configuration variables
        //so they can effectively contain more than one value
        $serialvalue = serialize($value);

        //Insert
        $seqId = $dbconn->GenId($config_varsTable);
        $query = "INSERT INTO $config_varsTable
                  (xar_id, xar_modid, xar_name, xar_value)
                  VALUES (?,?,?,?)";
        $bindvars = array($seqId, 0, $name, $serialvalue);
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate($bindvars);
        xarCore::setCached('Config.Variables', $name, $value);
        
        return true;
    }
    
    /**
     * Gets a configuration variable.
     *
     * @access public
     * @param string name the name of the variable
     * @return mixed value of the variable(string), or void if variable doesn't exist
     * @todo do we need these aliases anymore ?
     * @todo return proper site prefix when we can store site vars
     * @todo this is still too long and windy
     */
    public static function get($scope, $name)
    {
        static $cached = false;
        
        if(!$cached)
        {
            self::load();
            $cached = true;
        }
        
        // Configvars which are not in the database (why not?)
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
        $cacheName = $name;
        $cacheCollection = 'Config.Variables';
        if (xarCore::isCached($cacheCollection, $cacheName)) {
            $value = xarCore::getCached($cacheCollection, $cacheName);
            if (!isset($value)) {
                return;
            } else {
                return $value;
            }
        } elseif (xarCore::isCached($cacheCollection, 0)) {
            // variable missing.
            // we should really throw an exception here
            return;
        }
        
        $dbconn =& xarDBGetConn();
        $tables =& xarDBGetTables();
        $varstable = $tables['config_vars'];
        $query = "SELECT xar_name, xar_value FROM $varstable WHERE xar_modid = ?";

        // TODO : Here used to be a resultset cache option, reconsider it
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array(0),ResultSet::FETCHMODE_NUM);

        if ($result->getRecordCount() == 0) {
            $result->close(); unset($result);
            return;
        }

        while ($result->next()) { // while? we expect one value, no?
            $value = $result->get(2); // Unlike creole->set this does *not* unserialize/escape automatically
            $value = unserialize($value);
            xarCore::setCached($cacheCollection, $result->getString(1), $value);
        }
        
        // CHECKME: What's all this about then?
        // Special value to tell this select has already been run, any
        // variable not found now on is missing
        xarCore::setCached($cacheCollection, 0, true);
        //It should be here!
        if (xarCore::isCached($cacheCollection, $cacheName)) {
            $value = xarCore::getCached($cacheCollection, $cacheName);
        } else {
            return;
        }
        return $value;
    }
    
    public static function delete($scope, $name)
    {
        $dbconn =& xarDBGetConn();
        $tables =& xarDBGetTables();
        $config_varsTable = $tables['config_vars'];
        $query = "DELETE FROM $config_varsTable WHERE xar_name = ? AND xar_modid=?";
        
        // We want to make the next two statements atomic
        $dbconn->execute($query,array($name,0));
        xarCore::delCached('Config.Variables.', $name);
        
        return true;
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