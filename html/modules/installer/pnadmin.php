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
    //$locales = pnMLSListSiteLocales();

    /*
     * TODO: Find way to convert locale string into language, country, etc..
     */

    return array('languages' => array('eng' => 'English'));
}

/**
 * Phase 2: Accept License Page
 *
 * @param none
 * @returns array
 */
function installer_admin_phase2() {
    /*
     * TODO: accept locale and run the rest of the install
     *       using that locale if the locale exists.
     */
    // Might have to unset some cached variables.
    return array();
}

/**
 * Phase 3: Check system settings and ability to write config
 *
 * @param agree
 * @returns array
 */
function installer_admin_phase3()
{
    $agree = pnVarCleanFromInput('agree');

    if ($agree != 'agree') {
        // didn't agree to license, don't install
        pnResponseRedirect('install.php');
    }

    return array();
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
    $dbHost   = pnCore_getSystemVar('DB.Host');
    $dbUser   = pnCore_getSystemVar('DB.UserName');
    $dbPass   = pnCore_getSystemvar('DB.Password');
    $dbName   = pnCore_getSystemvar('DB.Name');
    $dbPrefix = pnCore_getSystemvar('DB.TablePrefix');

    return array('database_host' => $dbHost,
                 'database_username' => $dbUser,
                 'database_password' => $dbPass,
                 'database_name' => $dbName,
                 'database_prefix' => $dbPrefix,
                 'database_types' => array('mysql'    => 'MySQL',
                                           'postgres' => 'Postgres'));
}

/**
 * Phase 5: Pre-Boot, Modify Configuration
 *    INSTALL: create minimal tables, direct to bootstrap
 *    UPGRADE: upgrade tables, direct to bootstrap
 *
 * @param dbHost
 * @param dbName
 * @param dbUname
 * @param dbPass
 * @param dbPrefix
 * @param dbType
 * @param installType
 * @param intranetMode
 * @param createDb
 *
 * @returns
 */
function installer_admin_phase5()
{
    // Get arguments
    list($dbHost,
         $dbName,
         $dbUname,
         $dbPass,
         $dbPrefix,
         $dbType,
         $installType,
         $intranetMode,
         $createDb)    = pnVarCleanFromInput('install_database_host',
                                             'install_database_name',
                                             'install_database_username',
                                             'install_database_password',
                                             'install_database_prefix',
                                             'install_database_type',
                                             'install_type',
                                             'install_intranet',
                                             'install_create_database');
    
    // Check necessary arguments
    if (empty($dbHost) || empty($dbName) || empty($dbUname)
        || empty($dbPrefix) || empty($dbType) || empty($installType)) {

       $msg = pnML('Empty dbHost (#(1)) or dbName (#(2)) or dbUname (#(3))
                   or dbPrefix (#(4)) or dbType (#(5)) or installType (#(6)).'
                  , $dbHost, $dbName, $dbUname, $dbPrefix, $dbType, $installType);
       die($msg);
    }

    // Set default for password
    if (!isset($dbPass)) {
        $dbPass = '';
    }

    // Set defaults for createdb
    if (isset($createDb)) {
        $createDb = TRUE;
    } else {
        $createDb = FALSE;
    }

    // Pre-Setup of intranet mode
    if (isset($intranetMode)) {
        $intranetMode = TRUE;
    } else {
        $intranetMode = FALSE;
    }
    
    if ('new' == $installType) {
        $initFunc = 'init';
    } elseif ('upgrade' == $installType) {
        $initfunc = 'upgrade';
    }



    pnInstallAPILoad('installer','admin');

    // Save config data
    $res = pnInstallAPIFunc('installer',
                            'admin',
                            'modifyconfig',
                            array('dbHost'    => $dbHost,
                                  'dbName'    => $dbName,
                                  'dbUname'   => $dbUname,
                                  'dbPass'    => $dbPass,
                                  'dbPrefix'  => $dbPrefix,
                                  'dbType'    => $dbType));
    // throw back
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return NULL;
    }

    // Create the database if necessary
    if ($createDb) {
        $res = pnInstallAPIFunc('installer',
                                'admin',
                                'createdb');
        if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
            return NULL;
        }
    }

    // Start the database
    pnCoreInit(PNCORE_SYSTEM_ADODB);

    // Load in modules/installer/pninit.php and choose a new install or upgrade
    $res = pnInstallAPIFunc('installer',
                            'admin',
                            'initialise',
                            array('directory' => 'installer',
                                  'initfunc'  => $initFunc));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return NULL;
    }
   
    return array();
}

/*function installer_admin_phase6
{


    return array();
}*/

/**
 * Bootstrap Xaraya
 *
 * @param none
 * @returns bool
 */
function installer_admin_bootstrap()
{

    // log in admin user
    $res = pnUserLogIn('admin', 'password', 0);
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }
    
    // Load installer API
    pnModAPILoad('installer','admin');

    // Activate modules
    $res = pnModAPIFunc('installer',
                        'admin',
                        'initialise',
                        array('directory' => 'base',
                              'initfunc' => 'activate'));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    pnResponseRedirect(pnModURL('installer', 'admin', 'create_administrator'));

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
    if (!pnSecAuthAction(0, 'Installer::', '::', ACCESS_ADMIN)) {
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException(__FILE__."(".__LINE__."): You do not have permission to access the Installer module."));return;
    }

    if (!pnVarCleanFromInput('create')) {
        return array();
    }

    list ($username,
          $name,
          $password,
          $email,
          $url) = pnVarCleanFromInput('install_admin_username',
                                       'install_admin_name',
                                       'install_admin_password',
                                       'install_admin_email',
                                       'install_admin_url');

    pnModAPILoad('users', 'admin');

    $password = md5($password);

    $res = pnModAPIFunc('users', 'admin', 'update', array('uid'   => 2,
                                                          'name'  => $name,
                                                          'uname' => $username,
                                                          'email' => $email,
                                                          'pass'  => $password,
                                                          'url'   => $url));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    pnResponseRedirect(pnModURL('installer', 'admin', 'finish'));
}



function installer_admin_finish()
{
    $res = pnModAPILoad('blocks', 'admin');
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return NULL;
    }

    // Load up database
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();
    $block_groups_table          = $pntable['block_groups'];

    $query = "SELECT    pn_id as id
              FROM      $block_groups_table
              WHERE     pn_name = 'left'";

    $result = $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Freak if we don't get one and only one result
    if ($result->PO_RecordCount() != 1) {
        $msg = pnML("Group 'left' not found.");
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    list ($group_id) = $result->fields;

    $type_id = pnBlockTypeExists('adminpanels', 'adminmenu');

    $block_id = pnModAPIFunc('blocks',
                             'admin',
                             'create_instance', array('title'    => 'Admin',
                                                      'type'     => $type_id,
                                                      'group'    => $group_id,
                                                      'template' => '',
                                                      'state'    => 2));

    if (!isset($block_id) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    $msg = pnML('Reminder message body will go here.');

    $type_id = pnBlockTypeExists('base', 'html');
    $block_id = pnModAPIFunc('blocks',
                             'admin',
                             'create_instance', array('title'    => 'Reminder',
                                                      'content'  => $msg,
                                                      'type'     => $type_id,
                                                      'group'    => $group_id,
                                                      'template' => '',
                                                      'state'    => 2));
    if (!isset($block_id) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }
    
    if (pnVarIsCached('Config.Variables', 'Site.BL.DefaultTheme')) {
        pnVarDelCached('Config.Variables', 'Site.BL.DefaultTheme');
    }
    pnConfigSetVar('Site.BL.DefaultTheme','Xaraya_Classic');

    return array();
}

function installer_admin_modifyconfig(){}

?>
