<?php // $Id$
// ----------------------------------------------------------------------
// PostNuke Content Management System
// Copyright (C) 2002 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// ----------------------------------------------------------------------
// Original Author of file: Paul Rosania
// Purpose of file: Installer display functions
// ----------------------------------------------------------------------

function installer_adminapi_phase1() {
    return array('languages' => array('eng' => 'English'));
}

function installer_adminapi_phase2() {
    return array();
}

function installer_adminapi_phase3() 
{
    global $HTTP_POST_VARS;
    if ($HTTP_POST_VARS['agree'] != 'agree') {
        // didn't agree to license, don't install
        pnRedirect('install.php');
    }
    
    return array();
}

function installer_adminapi_phase4() 
{
    return array('database_host' => 'localhost',
                 'database_username' => 'root',
                 'database_password' => '',
                 'database_name' => 'adam_baum',
                 'database_prefix' => 'pn',
                 'database_types' => array('mysql'    => 'MySQL',
                                           'postgres' => 'Postgres'));
}

function installer_adminapi_phase5() 
{
    global $HTTP_POST_VARS;
    
    $dbhost = $HTTP_POST_VARS['install_database_host'];
    $dbname = $HTTP_POST_VARS['install_database_name'];
    $dbuser = $HTTP_POST_VARS['install_database_username'];
    $dbpass = $HTTP_POST_VARS['install_database_password'];
    $prefix = $HTTP_POST_VARS['install_database_prefix'];
    $dbtype = $HTTP_POST_VARS['install_database_type'];
    if (isset($HTTP_POST_VARS['install_create_database'])) {
    //Ugly Switch... until we write a database connection wrapper
    //Needed because ADONewConnection requires a database to connect to
        switch($dbtype){
            case 'mysql':
            //TODO: add error checking (prolly wait til the connection wrapper)
            mysql_connect($dbhost,$dbuser,$dbpass);
            break;
        } 
       
        //TODO: add error checking and replace with pnDBCreateDB
        mysql_create_db($dbname);
    }
    
    if (isset($HTTP_POST_VARS['install_intranet'])) {
        $intranet = true;
    } else {
        $intranet = false;
    }

    // Save config data
    installer_adminapi_modifyconfig($dbhost, $dbuser, $dbpass, $dbname, $prefix, $dbtype);
    
    // Kick it
    pnInit(_PNINIT_LOAD_DATABASE);
    
    // Initialize *minimal* tableset
    // Load the installer module, the hard way - file check too
    $base_init_file = 'modules/base/pninit.php';

    if (file_exists($base_init_file)) {
        include_once ($base_init_file);
    } else {
        // modules/base/pninit.php not found?!
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module file $base_init_file doesn't exist."));return;
    }
    
    // Run the function, check for existence
    $mod_func = 'base_init';

    if (function_exists($mod_func)) {
        $res = $mod_func();
        // Handle exceptions
        if (pnExceptionMajor() != PN_NO_EXCEPTION) {
            return;
        }
        if ($res == false) {
            // exception
            pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNKNOWN',
                           new SystemException(__FILE__.'('.__LINE__.'): core initialization failed!'));return;
        }
    } else {
        // base_init() not found?!
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module API function $mod_func doesn't exist."));return;
    }
    
    // log user in

    pnRedirect('index.php?module=installer&type=admin&func=bootstrap');
}

// Update database information in config.php
// TODO: EXCEPTIONS!!
function installer_adminapi_modifyconfig($dbhost, $dbuname, $dbpass, $dbname, $prefix, $dbtype)
{
    $config_php = join('', file('config.php'));
    
    if (isset($HTTP_ENV_VARS['OS']) && strstr($HTTP_ENV_VARS['OS'], 'Win')) {
        $system = 1;
    } else {
        $system = 0;
    }
    
    $dbuname = base64_encode($dbuname);
    $dbpass = base64_encode($dbpass);

    $config_php = preg_replace('/\[\'dbtype\'\]\s*=\s*(\'|\")(.*)\\1;/', "['dbtype'] = '$dbtype';", $config_php);
    $config_php = preg_replace('/\[\'dbhost\'\]\s*=\s*(\'|\")(.*)\\1;/', "['dbhost'] = '$dbhost';", $config_php);
    $config_php = preg_replace('/\[\'dbuname\'\]\s*=\s*(\'|\")(.*)\\1;/', "['dbuname'] = '$dbuname';", $config_php);
    $config_php = preg_replace('/\[\'dbpass\'\]\s*=\s*(\'|\")(.*)\\1;/', "['dbpass'] = '$dbpass';", $config_php);
    $config_php = preg_replace('/\[\'dbname\'\]\s*=\s*(\'|\")(.*)\\1;/', "['dbname'] = '$dbname';", $config_php);
    $config_php = preg_replace('/\[\'prefix\'\]\s*=\s*(\'|\")(.*)\\1;/', "['prefix'] = '$prefix';", $config_php);
    $config_php = preg_replace('/\[\'system\'\]\s*=\s*(\'|\")(.*)\\1;/', "['system'] = '$system';", $config_php);
    $config_php = preg_replace('/\[\'encoded\'\]\s*=\s*(\'|\")(.*)\\1;/', "['encoded'] = '1';", $config_php);
    
    $fp = fopen ('config.php', 'w+');
    fwrite ($fp, $config_php);
    fclose ($fp);
}

?>