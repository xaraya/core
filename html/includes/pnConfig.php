<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Marco Canini
// Purpose of file: Configuration Unit
// ----------------------------------------------------------------------

function pnConfig_init($args)
{
    // Configuration Unit Tables
    $sitePrefix = pnDBGetSiteTablePrefix();

    $tables = array('config_vars' => $sitePrefix . '_config_vars');

    pnDB_importTables($tables);

    return true;
}

/**
 * Gets a configuration variable.
 *
 * @access public
 * @param name the name of the variable
 * @return mixed value of the variable, or void if variable doesn't exist
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function pnConfigGetVar($name)
{
    static $aliases = array('sitename' => 'SiteName',
                            'slogan' => 'SiteSlogan',
                            'prefix' => 'TablePrefix',
                            'footer' => 'PageFooter',
                            'Version_Num' => 'VersionNumber',
                            'Version_ID' => 'VersionId',
                            'Version_Sub' => 'VersionSub');

    if (empty($name)) {
        $msg = pnML('Empty name.');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (isset($aliases[$name])) {
        $name = $aliases[$name];
    }

    if (pnVarIsCached('Config.Variables', $name)) {
        return pnVarGetCached('Config.Variables', $name);
    }

    if ($name == 'TablePrefix') {
        return pnCore_getSiteVar('DB.TablePrefix');
    } elseif ($name == 'VersionNumber') {
        return PNCORE_VERSION_NUM;
    } elseif ($name == 'VersionId') {
        return PNCORE_VERSION_ID;
    } elseif ($name == 'VersionSub') {
        return PNCORE_VERSION_SUB;
    }

    list($dbconn) = pnDBGetConn();
    $tables = pnDBGetTables();

    $config_varsTable = $tables['config_vars'];

    $query = "SELECT pn_value
              FROM $config_varsTable
              WHERE pn_name='" . pnVarPrepForStore($name) . "'";
    $result = $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }
    if ($result->EOF) {
        $result->Close();
        // FIXME: <marco> Trying to force strong check over config var names
        /*$msg = pnML('Unexistent config variable: #(1).', $name);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'ID_NOT_EXIST',
                       new SystemException($msg));
        return;*/
        pnVarSetCached('Config.Variables', $name, NULL);
        return;
    }

    //Get data
    list($value) = $result->fields;
    $result->Close();

    // Unserialize variable value
    $value = unserialize($value);

    //Some caching
    pnVarSetCached('Config.Variables', $name, $value);

    return $value;
}

/**
 * Sets a configuration variable.
 *
 * @access public
 * @param name the name of the variable
 * @param value the value of the variable
 * @return bool true on success, or false if you're trying to set unallowed variables
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function pnConfigSetVar($name, $value)
{
    if (empty($name)) {
        $msg = pnML('Empty name.');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // see if the variable has already been set
    $oldValue = pnConfigGetVar($name);
    $mustInsert = false;
    if (!isset($oldValue)) {
        if (pnExceptionMajor()) return; // thorw back
        $mustInsert = true;
    }

    list($dbconn) = pnDBGetConn();
    $tables = pnDBGetTables();
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
                  (pn_id,
                   pn_name,
                   pn_value)
                  VALUES ('$seqId',
                          '" . pnVarPrepForStore($name) . "',
                          '" . pnVarPrepForStore($value). "')";
    } else {
         //Update
         $query = "UPDATE $config_varsTable
                   SET pn_value='" . pnVarPrepForStore($value) . "'
                   WHERE pn_name='" . pnVarPrepForStore($name) . "'";
    }

    $dbconn->Execute($query);
    if($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }

    //Update configuration variables
    pnVarSetCached('Config.Variables', $name, $value);

    return true;
}

?>