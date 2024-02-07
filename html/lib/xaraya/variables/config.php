<?php
/**
 * Configuration variable handling
 *
 * @package core\variables
 * @subpackage variables
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marcel van der Boom <mrb@hsdev.com>
 */

sys::import('xaraya.variables');

/**
 * Class to handle configuration variables
 *
 * @todo if core was module 0 this could be a whole lot simpler by derivation (or if all config variables were moved to a module)
 */
class xarConfigVars extends xarVars implements IxarVars
{
    private static $KEY = 'Config.Variables'; // const cannot be private :-(
    private static $preloaded = false;

    /**
     * Sets a configuration variable.
     *
     * @param string|null $scope not used
     * @param  string $name the name of the variable
     * @param  mixed  $value (array,integer or string) the value of the variable
     * @return boolean true on success, or false if you're trying to set unallowed variables
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
        xarCoreCache::setCached(self::$KEY, $name, $value);

        return true;
    }

    /**
     * Gets a configuration variable.
     *
     * @param string|null $scope not used
     * @param string $name  the name of the variable
     * @return mixed value of the variable(string), or void if variable doesn't exist
     * @todo do we need these aliases anymore ?
     * @todo the vars which are not in the database should probably be systemvars, not configvars
     * @todo bench the preloading
     */
    public static function get($scope, $name, $value=null)
    {
        // Preload the config vars once
        if(!self::$preloaded) self::preload();

        if(!self::$preloaded)
            throw new VariableNotFoundException($name, "Variable #(1) not found");
        
        // Configvars which are not in the database (either in config file or in code defines)
        switch($name)
        {
            case 'Site.DB.TablePrefix':
                return xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix');
            case 'System.Core.Generation':
                return xarCore::GENERATION;
            case 'System.Core.VersionNumber':
                return xarCore::VERSION_NUM;
            case 'System.Core.VersionId':
                return xarCore::VERSION_ID;
            case 'System.Core.VersionSub':
                return xarCore::VERSION_SUB;
            case 'prefix':
                // FIXME: Can we do this another way (dependency)
                return xarDB::getPrefix();
        }

        // From the cache
        if(xarCoreCache::isCached(self::$KEY, $name))
        {
            $value = xarCoreCache::getCached(self::$KEY, $name);
            return $value;
        }

        // Need to retrieve it
        // @todo checkme What should we do here? preload again, or just fetch the one?
	    $dbconn = xarDB::getConn();
	    $tables = xarDB::getTables();
	    $varstable = $tables['config_vars'] ?? null;
	    // No tables, probably installing
	    if($value == null) throw new VariableNotFoundException($name, "Variable #(1) not found (no tables found, in fact)");

        $query = "SELECT name, value FROM $varstable WHERE module_id is null AND name = ?";

        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($name),xarDB::FETCHMODE_NUM);

        if($result->next()) {
            // Found it, retrieve and cache it
            $value = $result->get(2);
            $value = unserialize($value);
            xarCoreCache::setCached(self::$KEY, $result->getString(1), $value);
            $result->close();
            return $value;
        }
        // @todo: We found nothing, return the default if we had one
        if($value !== null) return $value;        
        throw new VariableNotFoundException($name, "Variable #(1) not found");
    }

    /**
     * Summary of delete
     * @param string|null $scope not used
     * @param string $name  the name of the variable
     * @return bool
     */
    public static function delete($scope, $name)
    {
        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();
        $config_varsTable = $tables['config_vars'];
        $query = "DELETE FROM $config_varsTable WHERE name = ? AND module_id is null";

        // We want to make the next two statements atomic
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate(array($name));
        xarCoreCache::delCached(self::$KEY, $name);

        return true;
    }

    /**
     * Pre-load site configuration variables
     *
     * @return boolean true on success, or void on database error
     * @todo We need some way to delete configuration (useless without a certain module) variables from the table!!!
     * @todo look into removing the serialisation, creole does this when needed, automatically (well, almost)
     */
    private static function preload()
    {
        if (xarCoreCache::hasPreload(self::$KEY) && xarCoreCache::loadCached(self::$KEY)) {
            self::$preloaded = true;
            return true;
        }

        try {
          $dbconn = xarDB::getConn();
          $tables = xarDB::getTables();
          $varstable = xarDB::getPrefix() . '_module_vars';
        } catch (Exception $e) {
          return false;
        }
        
        $query = "SELECT name, value FROM $varstable WHERE module_id is null";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array(), xarDB::FETCHMODE_ASSOC);

        while ($result->next())
        {
            $newval = unserialize($result->getString('value'));
            xarCoreCache::setCached(self::$KEY, $result->getString('name'), $newval);
        }
        $result->close();

        if (xarCoreCache::hasPreload(self::$KEY)) {
            xarCoreCache::saveCached(self::$KEY);
        }

        self::$preloaded = true;
        return true;
    }

    /**
     * Cache all site configuration variables (if CoreCache.Preload is enabled for it)
     * @param string|null $scope not used
     * @return void
     */
    public static function cache($scope = null)
    {
        if (xarCoreCache::hasPreload(self::$KEY)) {
            xarCoreCache::saveCached(self::$KEY);
        }
    }
}
