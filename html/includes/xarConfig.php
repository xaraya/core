<?php
/**
 * File: $Id: s.xarConfig.php 1.17 03/01/21 13:54:43+00:00 johnny@falling.local.lan $
 * 
 * Configuration Unit
 * 
 * @package config
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.org
 * @author Marco Canini
*/


/**
 * Initialize config system
 *
 * @author  Marco Canini
 * @access public
 * @param args array
 * @param whatElseIsGoingLoaded integer 
 * @return  bool
*/
function xarConfig_init($args, $whatElseIsGoingLoaded)
{
    // Configuration Unit Tables
    $sitePrefix = xarDBGetSiteTablePrefix();

    $tables = array('config_vars' => $sitePrefix . '_config_vars');

    xarDB_importTables($tables);

    return true;
}

/**
 * Gets a configuration variable.
 *
 * @access public
 * @param name string the name of the variable
 * @return mixed value of the variable(string), or void if variable doesn't exist
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarConfigGetVar($name)
{
    static $aliases = array('sitename' => 'Site.Core.SiteName',
                            'slogan' => 'Site.Core.Site.Slogan',
                            'prefix' => 'Site.DB.TablePrefix',
                            'footer' => 'Site.Core.PageFooter',
                            'Version_Num' => 'System.Core.VersionNumber',
                            'Version_ID' => 'System.Core.VersionId',
                            'Version_Sub' => 'System.Core.VersionSub');

    if (empty($name)) {
        $msg = xarML('Empty name.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (isset($aliases[$name])) {
        $name = $aliases[$name];
    }

    if (xarVarIsCached('Config.Variables', $name)) {
        return xarVarGetCached('Config.Variables', $name);
    }

    if ($name == 'Site.DB.TablePrefix') {
        return xarCore_getSiteVar('DB.TablePrefix');
    } elseif ($name == 'System.Core.VersionNumber') {
        return XARCORE_VERSION_NUM;
    } elseif ($name == 'System.Core.VersionId') {
        return XARCORE_VERSION_ID;
    } elseif ($name == 'System.Core.VersionSub') {
        return XARCORE_VERSION_SUB;
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    $config_varsTable = $tables['config_vars'];

    $query = "SELECT xar_value
              FROM $config_varsTable
              WHERE xar_name='" . xarVarPrepForStore($name) . "'";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    if ($result->EOF) {
        $result->Close();
        // FIXME: <marco> Trying to force strong check over config var names
        /*$msg = xarML('Unexistent config variable: #(1).', $name);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'ID_NOT_EXIST',
                       new SystemException($msg));
        return;*/
        xarVarSetCached('Config.Variables', $name, NULL);
        return;
    }

    //Get data
    list($value) = $result->fields;
    $result->Close();

    // Unserialize variable value
    $value = unserialize($value);

    //Some caching
    xarVarSetCached('Config.Variables', $name, $value);

    return $value;
}

/**
 * Sets a configuration variable.
 *
 * @access public
 * @param name string the name of the variable
 * @param value mixed (array,integer or string) the value of the variable
 * @return bool true on success, or false if you're trying to set unallowed variables
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarConfigSetVar($name, $value)
{
    if (empty($name)) {
        $msg = xarML('Empty name.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // see if the variable has already been set
    $oldValue = xarConfigGetVar($name);
    $mustInsert = false;
    if (!isset($oldValue)) {
        if (xarExceptionMajor()) return; // thorw back
        $mustInsert = true;
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();
    $config_varsTable = $tables['config_vars'];

    //Here we serialize the configuration variables
    //so they can effectively contain more than one value
    $value = serialize($value);

    //Here we insert the value if it's new
    //or update the value if it already exists
    if ($mustInsert == true) {
        //Insert
        $seqId = $dbconn->GenId($config_varsTable);
        $query = "INSERT INTO $config_varsTable
                  (xar_id,
                   xar_name,
                   xar_value)
                  VALUES ('$seqId',
                          '" . xarVarPrepForStore($name) . "',
                          '" . xarVarPrepForStore($value). "')";
    } else {
         //Update
         $query = "UPDATE $config_varsTable
                   SET xar_value='" . xarVarPrepForStore($value) . "'
                   WHERE xar_name='" . xarVarPrepForStore($name) . "'";
    }

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    //Update configuration variables
    xarVarSetCached('Config.Variables', $name, $value);

    return true;
}

?>