<?php
/**
 * Configuration Unit
 * 
 * @package config
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marco Canini
*/


/**
 * Initialize config system
 *
 * @author  Marco Canini
 * @access public
 * @param array args
 * @param integer whatElseIsGoingLoaded
 * @return bool
*/
function xarConfig_init(&$args, $whatElseIsGoingLoaded)
{
    // Configuration Unit Tables
    $sitePrefix = xarDBGetSiteTablePrefix();

    $tables = array('config_vars' => $sitePrefix . '_config_vars');

    xarDB_importTables($tables);
    
    // Pre-load site config variables
    // CHECKME: see if this doesn't hurt install before activating :-)
    xarConfig_loadVars();

    // Subsystem initialized, register a handler to run when the request is over
    //register_shutdown_function ('xarConfig__shutdown_handler');
    return true;
}

/**
 * Shutdown handler for xarConfig subsystem
 *
 * @access private
 */
function xarConfig__shutdown_handler()
{
    //xarLogMessage('xarConfig shutdown handler');
}

/**
 * Gets a configuration variable.
 *
 * @access public
 * @param string name the name of the variable
 * @return mixed value of the variable(string), or void if variable doesn't exist
 * @raise DATABASE_ERROR, BAD_PARAM
 * @todo do we need these aliases anymore ?
 * @todo return proper site prefix when we can store site vars
 */
function xarConfigGetVar($name, $prep = NULL)
{
    static $aliases = array('Version_Num' => 'System.Core.VersionNumber',
                            'Version_ID' => 'System.Core.VersionId',
                            'Version_Sub' => 'System.Core.VersionSub');

    if (isset($aliases[$name])) {
        $name = $aliases[$name];
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
    return xarVar__GetVarByAlias($modname = NULL, $name, $uid = NULL, $prep, $type = 'configvar');
}

/**
 * Sets a configuration variable.
 *
 * @access public
 * @param string name the name of the variable
 * @param mixed value (array,integer or string) the value of the variable
 * @return bool true on success, or false if you're trying to set unallowed variables
 * @todo return states that it should return false if we're setting
 *       unallowed variables.. there is no such code to do that in the function
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarConfigSetVar($name, $value)
{
    // Nice, but introduces dependency
    return xarVar__SetVarByAlias($modName = NULL, $name, $value, $prime = NULL, $description = NULL, $uid = NULL, $type = 'configvar');
}

/**
 * Pre-load site configuration variables
 *
 * @access private
 * @return bool true on success, or void on database error
 * @raise DATABASE_ERROR
 */
//FIXME: We need someway to delete configuration (useless without a certain module) 
//variables from the table!!!
function xarConfig_loadVars()
{
    $cacheCollection = 'Config.Variables';

    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    $query = "SELECT xar_name,
                     xar_value
                FROM $tables[config_vars]";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    while (!$result->EOF) {
        list($name,$value) = $result->fields;
        $newval = unserialize($value);
        xarCore_SetCached($cacheCollection, $name, $newval);
        $result->MoveNext();
    }
    $result->Close();

    //Tells the cache system it has already checked this particular table
    //(It's a escape when you are caching at a higher level than that of the
    //individual variables)
    //This whole cache systems must be remade to a central one.    
    xarCore_SetCached($cacheCollection, 0, true);

    return true;
}

?>
