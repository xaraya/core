<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Paul Rosania
// Purpose of file: Installer display functions
// ----------------------------------------------------------------------


/**
 * Dead
 *
 * @access public
 * @param none
 * @returns array
 * @return an array of template values
 */
function installer_admin_main()
{
    return array();
}

/**
 * Phase 1: Welcome (Set Language and Locale) Page
 *
 * @param none
 * @returns array
 * @return array of language values
 */
function installer_admin_phase1()
{
    //$locales = xarMLSListSiteLocales();

    /*
     * TODO: Find way to convert locale string into language, country, etc..
     */
    $data['languages'] = array('eng' => 'English');
    
    return $data;
}

/**
 * Phase 2: Accept License Page
 *
 * @param none
 * @returns array
 */
function installer_admin_phase2()
{
    /*
     * TODO: accept locale and run the rest of the install
     *       using that locale if the locale exists.
     */
    // Might have to unset some cached variables.
    
    // TODO: fix installer ML
    $data['language'] = 'English';
    
    return $data;
}

/**
 * Phase 3: Check system settings and ability to write config
 *
 * @param agree
 * @returns array
 */
function installer_admin_phase3()
{
    $agree = xarVarCleanFromInput('agree');

    if ($agree != 'agree') {
        // didn't agree to license, don't install
        xarResponseRedirect('install.php');
    }
    
    //Defaults
    $systemConfigIsWritable = false;
    
    $systemVarDir     = xarCoreGetVarDirPath();
    $systemConfigFile = $systemVarDir . '/config.system.php';
    $siteConfigFile   = $systemVarDir . '/config.site.xml';
    
    if (is_writable($systemConfigFile)) {
        $systemConfigIsWritable = true;
    }
    
    $data['systemConfigFile'] = $systemConfigFile;
    $data['siteConfigFile']   = $siteConfigFile;
    $data['systemConfigIsWritable'] = $systemConfigIsWritable;
    
    // TODO: fix installer ML
    $data['language'] = 'English';
    
    return $data;
}

/**
 * Phase 4: Database Settings Page
 *
 * @returns array
 * @return array of default values for the database creation
 */
function installer_admin_phase4()
{
    // Get default values from config files
    $data['database_host']       = xarCore_getSystemVar('DB.Host');
    $data['database_username']   = xarCore_getSystemVar('DB.UserName');
    $data['database_password']   = xarCore_getSystemvar('DB.Password');
    $data['database_name']       = xarCore_getSystemvar('DB.Name');
    $data['database_prefix']     = xarCore_getSystemvar('DB.TablePrefix');
    
    // Supported  Databases:
    $data['database_types']      = array('mysql'    => 'MySQL',
                                         'oci8'     => 'Oracle',
                                         'postgres' => 'Postgres');
                                         
    // TODO: fix installer ML
    $data['language'] = 'English';

    return $data;
}

/**
 * Phase 5: Pre-Boot, Modify Configuration
 *
 * @param dbHost
 * @param dbName
 * @param dbUname
 * @param dbPass
 * @param dbPrefix
 * @param dbType
 * @param intranetMode
 * @param createDb
 *
 * @returns
 */
function installer_admin_phase5()
{
    // Defaults
    $createDb = false;
    $intranetMode = false;
    $dbPass = '';
    
    // Get arguments
    list($dbHost,
         $dbName,
         $dbUname,
         $dbPass,
         $dbPrefix,
         $dbType,
         $intranetMode,
         $createDb)    = xarVarCleanFromInput('install_database_host',
                                             'install_database_name',
                                             'install_database_username',
                                             'install_database_password',
                                             'install_database_prefix',
                                             'install_database_type',
                                             'install_intranet',
                                             'install_create_database');

    // Check necessary arguments
    if (empty($dbHost) || empty($dbName) || empty($dbUname) || empty($dbPrefix) || empty($dbType)) {
       $msg = xarML('Empty dbHost (#(1)) or dbName (#(2)) or dbUname (#(3)) or dbPrefix (#(4)) or dbType (#(5)).'
              , $dbHost, $dbName, $dbUname, $dbPrefix, $dbType);
       // TODO: better error checking
       xarCore_die($msg);
    }


    // FIXME: Pre-Setup of intranet mode


    if (!xarInstallAPILoad('installer','admin')) {
        return;
    }

    // Save config data
    if (!xarInstallAPIFunc('installer', 'admin', 'modifyconfig',
                                                 array('dbHost'    => $dbHost,
                                                       'dbName'    => $dbName,
                                                       'dbUname'   => $dbUname,
                                                       'dbPass'    => $dbPass,
                                                       'dbPrefix'  => $dbPrefix,
                                                       'dbType'    => $dbType))) {
        return;
    }


    // Create the database if necessary
    if ($createDb) {
        if (!xarInstallAPIFunc('installer', 'admin', 'createdb')) {
           return;
        }
    }

    // Start the database
    xarCoreInit(XARCORE_SYSTEM_ADODB);

    // Load in modules/installer/xarinit.php and start the install
    if (!xarInstallAPIFunc('installer', 'admin', 'initialise',
                                                 array('directory' => 'installer',
                                                       'initfunc'  => 'init'))) {
        return;
    }

    session_start(); 
    session_destroy();

    // TODO: fix installer ML
    $data['language'] = 'English';
    
    return $data;
}

/**
 * Bootstrap Xaraya
 *
 * @param none
 * @returns bool
 */
function installer_admin_bootstrap()
{
     xarTplSetThemeName('installer');

    // log in admin user
    if (!xarUserLogIn('Admin', 'password', 0)) {
        return;
    }

    // Load installer API
    if (!xarModAPILoad('installer','admin')) {
        return;
    }

    // Activate modules
    if (!xarModAPIFunc('installer',
                        'admin',
                        'initialise',
                        array('directory' => 'base',
                              'initfunc' => 'activate'))) {
        return;
    }

    xarResponseRedirect(xarModURL('installer', 'admin', 'create_administrator'));
}

/**
 * Create default administrator
 *
 * @access public
 * @param create
 * @returns bool
 */
function installer_admin_create_administrator()
{
	xarTplSetThemeName('installer');

    if (!xarSecAuthAction(0, 'Installer::', '::', ACCESS_ADMIN)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    if (!xarVarCleanFromInput('create')) {
        return array();
    }

    list ($username,
          $name,
          $password,
          $email,
          $url) = xarVarCleanFromInput('install_admin_username',
                                       'install_admin_name',
                                       'install_admin_password',
                                       'install_admin_email',
                                       'install_admin_url');

    if (!xarModAPILoad('users', 'admin')) {
        return;
    }

    xarModSetVar('mail', 'adminname', $name);
    xarModSetVar('mail', 'adminmail', $email);

    if(!xarModAPIFunc('users', 'admin', 'update', array('uid'   => 2,
                                                        'name'  => $name,
                                                        'uname' => $username,
                                                        'email' => $email,
                                                        'pass'  => $password,
                                                        'url'   => $url,
                                                        'state' => 3))) {
        return;
    }

    xarResponseRedirect(xarModURL('installer', 'admin', 'finish'));
}



function installer_admin_finish()
{
    // Load up database
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    $blockGroupsTable = $tables['block_groups'];

    $query = "SELECT    xar_id as id
              FROM      $blockGroupsTable
              WHERE     xar_name = 'left'";

    $result = $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // Freak if we don't get one and only one result
    if ($result->PO_RecordCount() != 1) {
        $msg = xarML("Group 'left' not found.");
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    list ($leftBlockGroup) = $result->fields;

    if (!xarModAPILoad('blocks', 'admin')) {
        return;
    }

    $adminBlockId = xarBlockTypeExists('adminpanels', 'adminmenu');

    if (!isset($adminBlockId) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }
    if (!xarModAPIFunc('blocks',
                       'admin',
                       'create_instance', array('title'    => 'Admin',
                                                'type'     => $adminBlockId,
                                                'group'    => $leftBlockGroup,
                                                'template' => '',
                                                'state'    => 2))) {
        return;
    }



    $now = time();

    $varshtml['html_content'] = 'Please delete the install.php from your webroot .';
    $varshtml['expire'] = $now + 24000;
    $msg = serialize($varshtml);

    $htmlBlockId = xarBlockTypeExists('base', 'html');

    if (!isset($htmlBlockId) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    if (!xarModAPIFunc('blocks',
                       'admin',
                       'create_instance', array('title'    => 'Reminder',
                                                'content'  => $msg,
                                                'type'     => $htmlBlockId,
                                                'group'    => $leftBlockGroup,
                                                'template' => '',
                                                'state'    => 2))) {
        return;
    }

    $query = "SELECT    xar_id as id
              FROM      $blockGroupsTable
              WHERE     xar_name = 'right'";

    $result = $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // Freak if we don't get one and only one result
    if ($result->PO_RecordCount() != 1) {
        $msg = xarML("Group 'right' not found.");
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    list ($rightBlockGroup) = $result->fields;

    $loginBlockId = xarBlockTypeExists('users', 'login');

    if (!isset($loginBlockId) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }
    if (!xarModAPIFunc('blocks',
                       'admin',
                       'create_instance', array('title'    => 'Login',
                                                'type'     => $loginBlockId,
                                                'group'    => $rightBlockGroup,
                                                'template' => '',
                                                'state'    => 2))) {
        return;
    }

    xarConfigSetVar('Site.BL.DefaultTheme','Xaraya_Classic');

    if (xarVarIsCached('Config.Variables', 'Site.BL.DefaultTheme')) {
        xarVarDelCached('Config.Variables', 'Site.BL.DefaultTheme');
    }

    return array();
}


function installer_admin_modifyconfig(){}

?>
