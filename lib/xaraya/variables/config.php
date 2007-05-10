<?php
/**
 * Configuration variable handling
 *
 * @package variables
 * @copyright The Digital Development Foundation, 2006
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @author Marcel van der Boom <mrb@hsdev.com>
 **/
sys::import('xaraya.variables');
sys::import('xaraya.creole');
/**
 * ConfigVars class
 *
 * @package variables
 * @todo if core was module 0 this could be a whole lot simpler by derivation (or if all config variables were moved to a module)
 **/
class xarConfigVars extends xarVars implements IxarVars
{
    private static $KEY = 'Config.Variables'; // const cannot be private :-(
    private static $preloaded = false;

    /**
     * Sets a configuration variable.
     *
     * @access public
     * @param  string $name the name of the variable
     * @param  mixed  $value (array,integer or string) the value of the variable
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

        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();
        $config_varsTable = $tables['config_vars'];

        //Here we serialize the configuration variables
        //so they can effectively contain more than one value
        $serialvalue = serialize($value);

        //Insert
        $query = "INSERT INTO $config_varsTable
                  (module_id, name, value)
                  VALUES (?,?,?)";
        $bindvars = array(null, $name, $serialvalue);
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate($bindvars);
        xarCore::setCached(self::$KEY, $name, $value);

        return true;
    }

    /**
     * Gets a configuration variable.
     *
     * @param string $scope not used
     * @param string $name  the name of the variable
     * @return mixed value of the variable(string), or void if variable doesn't exist
     * @todo do we need these aliases anymore ?
     * @todo return proper site prefix when we can store site vars
     * @todo the vars which are not in the database should probably be systemvars, not configvars
     * @todo bench the preloading
     */
    public static function get($scope, $name)
    {
        $value = null;

        // Preload the config vars once
        if(!self::$preloaded)
            self::preload();

        // Configvars which are not in the database (either in config file or in code defines)
        switch($name)
        {
            case 'Site.DB.TablePrefix':
                return xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix');
                break;
            case 'System.Core.VersionNumber':
                return XARCORE_VERSION_NUM;
                break;
            case 'System.Core.VersionId':
                return XARCORE_VERSION_ID;
                break;
            case 'System.Core.VersionSub':
                return XARCORE_VERSION_SUB;
                break;
            case 'prefix':
                // FIXME: Can we do this another way (dependency)
                return xarDBGetSiteTablePrefix();
                break;
        }

        // From the cache
        if(xarCore::isCached(self::$KEY, $name))
        {
            $value = xarCore::getCached(self::$KEY, $name);
            return $value;
        }

        // Need to retrieve it
        // @todo checkme What should we do here? preload again, or just fetch the one?
        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();
        $varstable = $tables['config_vars'];
        $query = "SELECT name, value FROM $varstable WHERE module_id is null AND name = ?";

        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($name),ResultSet::FETCHMODE_NUM);

        if($result->next()) {
            // Found it, retrieve and cache it
            $value = $result->get(2);
            $value = unserialize($value);
            xarCore::setCached(self::$KEY, $result->getString(1), $value);
        }
        $result->close();
        // @todo we really should except here.
        return $value;
    }

    public static function delete($scope, $name)
    {
        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();
        $config_varsTable = $tables['config_vars'];
        $query = "DELETE FROM $config_varsTable WHERE name = ? AND module_id is null";

        // We want to make the next two statements atomic
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate(array($name));
        xarCore::delCached(self::$KEY, $name);

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
    private static function preload()
    {
        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();

        $query = "SELECT name, value FROM $tables[config_vars] WHERE module_id is null";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array(),ResultSet::FETCHMODE_ASSOC);

        while ($result->next())
        {
            $newval = unserialize($result->getString('value'));
            xarCore::setCached(self::$KEY, $result->getString('name'), $newval);
        }
        $result->close();

        self::$preloaded = true;
        return true;
    }
}
?>
