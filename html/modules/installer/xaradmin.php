<?php
/**
 * File: $Id: s.xaradmin.php 1.67 03/04/19 16:34:00-04:00 johnny@falling.local.lan $
 *
 * Installer admin display functions
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage installer
 * @author Paul Rosania
 * @author Marcel van der Boom <marcel@hsdev.com>
 */


/**
 * Dead
 *
 * @access public
 * @returns array
 * @return an array of template values
 */
function installer_admin_main()
{
    $data['phase'] = 0;
    $data['phase_label'] = xarML('Welcome to Xaraya');
    return $data;
}

/**
 * Phase 1: Welcome (Set Language and Locale) Page
 *
 * @access private
 * @return data array of language values
 * @todo <johnny> Find way to convert locale string into language, country, etc..
 */
function installer_admin_phase1()
{
    //$locales = xarMLSListSiteLocales();

    $data['languages'] = array('eng' => 'English');
    $data['phase'] = 1;
    $data['phase_label'] = xarML('Step One');

    return $data;
}

/**
 * Phase 2: Accept License Page
 *
 * @access private
 * @return array
 * @todo <johnny> FIX Installer ML
 * @todo <johnny> accept locale and run the rest of the install using that locale if the locale exists.
 */
function installer_admin_phase2()
{
    // TODO: fix installer ML
    $data['language'] = 'English';
    $data['phase'] = 2;
    $data['phase_label'] = xarML('Step Two');

    return $data;
}

/**
 * Phase 3: Check system settings
 *
 * @access private
 * @param agree string
 * @return array
 * @todo <johnny> FIX Installer MLr
 * @todo <johnny> make sure php version checking works with
 *       php versions that contain strings
 */
function installer_admin_phase3()
{
    if (!xarVarFetch('agree','regexp:(agree|disagree)',$agree)) return;

    if ($agree != 'agree') {
        // didn't agree to license, don't install
        xarResponseRedirect('install.php?install_phase=2');
    }

    //Defaults
    $systemConfigIsWritable   = false;
    $cacheTemplatesIsWritable = false;
    $rssTemplatesIsWritable   = false;
    $metRequiredPHPVersion    = false;

    $systemVarDir             = xarCoreGetVarDirPath();
    $cacheTemplatesDir        = $systemVarDir . '/cache/templates';
    $rssTemplatesDir          = $systemVarDir . '/cache/rss';
    $adodbTemplatesDir        = $systemVarDir . '/cache/adodb';
    $systemConfigFile         = $systemVarDir . '/config.system.php';

    if (function_exists('version_compare')) {
        if (version_compare(PHP_VERSION,'4.1.2','>=')) $metRequiredPHPVersion = true;
    }

    $systemConfigIsWritable = is_writable($systemConfigFile);
    $cacheTemplatesIsWritable = is_writable($cacheTemplatesDir);
    $rssTemplatesIsWritable = is_writable($rssTemplatesDir);
    $adodbTemplatesIsWritable = is_writable($adodbTemplatesDir);

    $data['metRequiredPHPVersion']    = $metRequiredPHPVersion;
    $data['phpVersion']               = PHP_VERSION;
    $data['cacheTemplatesDir']        = $cacheTemplatesDir;
    $data['cacheTemplatesIsWritable'] = $cacheTemplatesIsWritable;
    $data['rssTemplatesDir']          = $rssTemplatesDir;
    $data['rssTemplatesIsWritable']   = $rssTemplatesIsWritable;
    $data['adodbTemplatesDir']        = $adodbTemplatesDir;
    $data['adodbTemplatesIsWritable'] = $adodbTemplatesIsWritable;
    $data['systemConfigFile']         = $systemConfigFile;
    $data['systemConfigIsWritable']   = $systemConfigIsWritable;

    $data['language']    = 'English';
    $data['phase']       = 3;
    $data['phase_label'] = xarML('Step Three');

    return $data;
}

/**
 * Phase 4: Database Settings Page
 *
 * @access private
 * @return array of default values for the database creation
 * @todo FIX installer ML
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
                                         //'oci8'     => 'Oracle',
                                         'postgres' => 'Postgres');

    $data['language'] = 'English';
    $data['phase'] = 4;
    $data['phase_label'] = xarML('Step Four');

    return $data;
}

/**
 * Phase 5: Pre-Boot, Modify Configuration
 *
 * @access private
 * @param dbHost
 * @param dbName
 * @param dbUname
 * @param dbPass
 * @param dbPrefix
 * @param dbType
 * @param createDb
 * @todo FIX installer ML
 * @todo better error checking on arguments
 */
function installer_admin_phase5()
{
    xarVarSetCached('installer','installing', true);

    // Get arguments
    if (!xarVarFetch('install_database_host','str',$dbHost)) return;
    if (!xarVarFetch('install_database_name','str',$dbName,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_username','str',$dbUname,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_password','str',$dbPass,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_prefix','str',$dbPrefix,'xar',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_type','str:1:',$dbType)) return;
    if (!xarVarFetch('install_create_database','checkbox',$createDb,false,XARVAR_NOT_REQUIRED)) return;

    if ($dbName == '') {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
             new SystemException("No database was specified"));
        return;
    }
    // Save config data
    $config_args = array('dbHost'    => $dbHost,
                         'dbName'    => $dbName,
                         'dbUname'   => $dbUname,
                         'dbPass'    => $dbPass,
                         'dbPrefix'  => $dbPrefix,
                         'dbType'    => $dbType);

    if (!xarInstallAPIFunc('installer', 'admin', 'modifyconfig', $config_args)) {
        return;
    }

    // Create the database if necessary
    if ($createDb) {
        //Let's pass all input variables thru the function argument or none, as all are stored in the system.config.php
        //Now we are passing all, let's see if we gain consistency by loading config.php already in this phase?
        //Probably there is already a core function that can make that for us...
        //the config.system.php is lazy loaded in xarCore_getSystemVar($name), which means we cant reload the values
        // in this phase... Not a big deal 'though.
        if (!xarInstallAPIFunc('installer', 'admin', 'createdb', $config_args)) {
            $msg = xarML('Could not create database (#(1)).', $dbName);
            xarCore_die($msg);
            return;
        }
    }

    // Start the database
    // {ML_dont_parse 'includes/xarDB.php'}
    include_once 'includes/xarDB.php';

    $systemArgs = array('userName' => $dbUname,
                        'password' => $dbPass,
                        'databaseHost' => $dbHost,
                        'databaseType' => $dbType,
                        'databaseName' => $dbName,
                        'systemTablePrefix' => $dbPrefix,
                        // uncomment this and remove the next line when we can store
                        // site vars that are pre DB
                        //'siteTablePrefix' => xarCore_getSiteVar('DB.TablePrefix'));
                        'siteTablePrefix' => $dbPrefix);
    // Connect to database
    $whatToLoad = XARCORE_SYSTEM_NONE;
    xarDB_init($systemArgs, $whatToLoad);

    // install the security stuff here, but disable the registerMask and
    // and xarSecurityCheck functions until we've finished the installation process

    include_once 'includes/xarSecurity.php';
    xarSecurity_init();

    // Load in modules/installer/xarinit.php and start the install
    if (!xarInstallAPIFunc('installer', 'admin', 'initialise',
                                                 array('directory' => 'installer',
                                                       'initfunc'  => 'init'))) {
        return;
    }

    $data['language'] = 'English';
    $data['phase'] = 5;
    $data['phase_label'] = xarML('Step Five');

    return $data;
}

/**
 * Bootstrap Xaraya
 *
 * @access private
 */
function installer_admin_bootstrap()
{
    xarVarSetCached('installer','installing', true);
    xarTplSetThemeName('installer');

    // activate the security stuff
    // create the default roles and privileges setup
    include 'modules/privileges/xarsetup.php';
    initializeSetup();

    // Set up default user properties, etc.

    // load modules into *_modules table
    if (!xarModAPIFunc('modules', 'admin', 'regenerate')) return;

    // Set the state and activate the following modules
    $modlist=array('roles','privileges','blocks','themes');
    foreach ($modlist as $mod) {
        // Set state to inactive
        $regid=xarModGetIDFromName($mod);
        if (isset($regid)) {
            if (!xarModAPIFunc('modules','admin','setstate',
                                array('regid'=> $regid, 'state'=> XARMOD_STATE_INACTIVE))) return;

            // Activate the module
            if (!xarModAPIFunc('modules','admin','activate', array('regid'=> $regid))) return;
        }
    }

    // load themes into *_themes table
    if (!xarModAPIFunc('themes', 'admin', 'regenerate')) {
        return NULL;
    }

    // Set the state and activate the following themes
    $themelist=array('print','rss','Xaraya_Classic');
    foreach ($themelist as $theme) {
        // Set state to inactive
        $regid=xarThemeGetIDFromName($theme);
        if (isset($regid)) {
            if (!xarModAPIFunc('themes','admin','setstate', array('regid'=> $regid,'state'=> XARTHEME_STATE_INACTIVE))){
                return;
            }
            // Activate the module
            if (!xarModAPIFunc('themes','admin','activate', array('regid'=> $regid)))
            {
                return;
            }
        }
    }

    // Initialise and activate adminpanels, mail, dynamic data
    $modlist = array('adminpanels','mail', 'dynamicdata');
    foreach ($modlist as $mod) {
        // Initialise the module
        $regid = xarModGetIDFromName($mod);
        if (isset($regid)) {
            if (!xarModAPIFunc('modules', 'admin', 'initialise', array('regid' => $regid))) return;
            // Activate the module
            if (!xarModAPIFunc('modules', 'admin', 'activate', array('regid' => $regid))) return;
        }
    }

    //initialise and activate base module by setting the states
    $baseId = xarModGetIDFromName('base');
    if (!xarModAPIFunc('modules', 'admin', 'setstate', array('regid' => $baseId, 'state' => XARMOD_STATE_INACTIVE))) return;
    // Set module state to active
    if (!xarModAPIFunc('modules', 'admin', 'setstate', array('regid' => $baseId, 'state' => XARMOD_STATE_ACTIVE))) return;

    xarResponseRedirect(xarModURL('installer', 'admin', 'create_administrator'));
}

/**
 * Create default administrator and default blocks
 *
 * @access public
 * @param create
 * @return bool
 * @todo make confirm password work
 * @todo remove URL field from users table
 * @todo normalize user's table
 */
function installer_admin_create_administrator()
{

    xarVarSetCached('installer','installing', true);

    xarTplSetThemeName('installer');
    $data['language'] = 'English';
    $data['phase'] = 6;
    $data['phase_label'] = xarML('Create Administrator');

    $role = xarFindRole('Everybody');
    xarModSetVar('roles', 'everybody', $role->getID());
    $role = xarFindRole('Anonymous');
    xarConfigSetVar('Site.User.AnonymousUID', $role->getID());

    include_once 'modules/roles/xarroles.php';
    $role = xarFindRole('Admin');

    if (!xarVarFetch('create', 'isset', $create, FALSE, XARVAR_NOT_REQUIRED)) return;
    if (!$create) {
        // create a role from the data

        // assemble the template data
        $data['install_admin_username'] = $role->getUser();
        $data['install_admin_name']     = $role->getName();
        $data['install_admin_email']    = $role->getEmail();
        return $data;
    }

    if (!xarVarFetch('install_admin_username','str:1:100',$userName)) return;
    if (!xarVarFetch('install_admin_name','str:1:100',$name)) return;
    if (!xarVarFetch('install_admin_password','str:4:100',$pass)) return;
    if (!xarVarFetch('install_admin_password1','str:4:100',$pass1)) return;
    if (!xarVarFetch('install_admin_email','str:1:100',$email)) return;

    xarModSetVar('mail', 'adminname', $name);
    xarModSetVar('mail', 'adminmail', $email);
    xarModSetVar('themes', 'SiteCopyRight', '&copy; Copyright 2003 ' . $name);

    if ($pass != $pass1) {
        $msg = xarML('The passwords do not match');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    // assemble the args into an array for the role constructor
    $pargs = array('uid'   => $role->getID(),
                   'name'  => $name,
                   'type'  => 0,
                   'uname' => $userName,
                   'email' => $email,
                   'pass'  => $pass,
                   'state' => 3);

    xarModSetVar('roles', 'lastuser', $userName);
    xarModSetVar('roles', 'adminpass', $pass);

    // create a role from the data
    $role = new xarRole($pargs);

    //Try to update the role to the repository and bail if an error was thrown
    $modifiedrole = $role->update();
    if (!$modifiedrole) {return;}

    // Register Block types
    $blocks = array('finclude','html','menu','php','text');

    foreach ($blocks as $block) {
        if (!xarModAPIFunc('blocks', 'admin', 'register_block_type', array('modName'  => 'base', 'blockType'=> $block))) return;
    }

    if (xarVarIsCached('Mod.BaseInfos', 'blocks')) xarVarDelCached('Mod.BaseInfos', 'blocks');

    // Create default block groups/instances
    if (!xarModAPIFunc('blocks', 'admin', 'create_group', array('name'  => 'left')))                                    return;
    if (!xarModAPIFunc('blocks', 'admin', 'create_group', array('name'  => 'right',     'template' => 'right')))        return;
    if (!xarModAPIFunc('blocks', 'admin', 'create_group', array('name'  => 'header',    'template' => 'header')))       return;
    if (!xarModAPIFunc('blocks', 'admin', 'create_group', array('name'  => 'syndicate', 'template' => 'syndicate')))    return;
    if (!xarModAPIFunc('blocks', 'admin', 'create_group', array('name'  => 'admin')))                                   return;
    if (!xarModAPIFunc('blocks', 'admin', 'create_group', array('name'  => 'center',    'template' => 'center')))       return;
    if (!xarModAPIFunc('blocks', 'admin', 'create_group', array('name'  => 'topnav',    'template' => 'topnav')))       return;

    // Load up database
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    $blockGroupsTable = $tables['block_groups'];

    $query = "SELECT    xar_id as id
              FROM      $blockGroupsTable
              WHERE     xar_name = 'left'";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Freak if we don't get one and only one result
    if ($result->PO_RecordCount() != 1) {
        $msg = xarML("Group 'left' not found.");
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    list ($leftBlockGroup) = $result->fields;

    $adminBlockId = xarModAPIFunc('blocks', 'admin', 'block_type_exists',
                            array('modName'  => 'adminpanels',
                                  'blockType'=> 'adminmenu'));

    if (!isset($adminBlockId) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }
    if (!xarModAPIFunc( 'blocks', 'admin', 'create_instance',
                array(  'title'    => 'Admin',
                        'name'     => 'adminpanel',
                        'type'     => $adminBlockId,
                        'group'    => $leftBlockGroup,
                        'template' => '',
                        'state'    => 2))) {
        return;
    }

    $now = time();

    $varshtml['html_content'] = 'Please delete install.php and upgrade.php from your webroot .';
    $varshtml['expire'] = $now + 24000;
    $msg = serialize($varshtml);

    $htmlBlockId= xarModAPIFunc('blocks',
                                 'admin',
                                 'block_type_exists',
                                 array('modName'  => 'base',
                                       'blockType'=> 'html'));

    if (!isset($htmlBlockId) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    if (!xarModAPIFunc('blocks',
                       'admin',
                       'create_instance', array('title'    => 'Reminder',
                                                'name'     => 'reminder',
                                                'content'  => $msg,
                                                'type'     => $htmlBlockId,
                                                'group'    => $leftBlockGroup,
                                                'template' => '',
                                                'state'    => 2))) {
        return;
    }

    xarResponseRedirect(xarModURL('installer', 'admin', 'choose_configuration', array('theme' => 'installer')));
}

/**
 * Choose the configuration to be installed
 *
 * @access public
 * @param create
 * @return bool
 */
function installer_admin_choose_configuration()
{

    xarTplSetThemeName('installer');
    $data['language'] = 'English';
    $data['phase'] = 7;
    $data['phase_label'] = xarML('Choose your configuration');

    //Get all modules in the filesystem
    $fileModules = xarModAPIFunc('modules','admin','getfilemodules');
    if (!isset($fileModules)) return;

    // Make sure all the core modules are here
    // Remove them from the list if name and regid coincide
    $awol = array();
    include 'modules/installer/xarconfigurations/coremoduleslist.php';
    foreach ($coremodules as $coremodule) {
        if (in_array($coremodule['name'],array_keys($fileModules))) {
            if ($coremodule['regid'] == $fileModules[$coremodule['name']]['regid'])
                unset($fileModules[$coremodule['name']]);
        }
        else $awol[] = $coremodule['name'];
    }

    if (count($awol) != 0) {
        $msg = xarML("Xaraya cannot install bcause the following core modules are missing or corrupted: #(1)",implode(', ', $awol));
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST',
                       new SystemException($msg));
        return;
    }

    $basedir = realpath('modules/installer/xarconfigurations');

    $files = array();
    if ($handle = opendir($basedir)) {
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != ".." && !is_dir($file)) $files[] = $file;
        }
        closedir($handle);
    }

    if (!isset($files) || count($files) < 1) {
        $data['warning'] = xarML('There are currently no configuration files available.');
        return $data;
    }

        xarModSetVar('installer','modulelist',serialize($fileModules));
    if (count($fileModules) == 0){
    // No non-core modules present. Show only the minimal configuration
        $names = array();
        include 'modules/installer/xarconfigurations/core.conf.php';
        $names[] = array('value' => 'modules/installer/xarconfigurations/core.conf.php',
                         'display'  => 'Core Xaraya install (aka minimal)');
    }
    // Add more criteria for filtering the configurations to be displayed here
    else {
    // Show all the configurations
        $names = array();
        foreach ($files as $file) {
            $pos = strrpos($file,'conf.php');
            if($pos == strlen($file)-8) {
                include $basedir . '/' . $file;
                $names[] = array('value' => $basedir . '/' . $file,
                                'display' => $configuration_name);
            }
        }
    }
    $data['names'] = $names;

    return $data;
}

/**
 * Choose the configuration options
 *
 * @access public
 * @param create
 * @return bool
 */
function installer_admin_confirm_configuration()
{
    xarVarSetCached('installer','installing', true);

    //We should probably break here if $configuration is not set.
    if(!xarVarFetch('configuration', 'isset', $configuration, NULL,  XARVAR_DONT_SET))  return;

    //I am not sure if these should these break
    if(!xarVarFetch('confirmed',     'isset', $confirmed,     NULL, XARVAR_DONT_SET))   return;
    if(!xarVarFetch('chosen',        'isset', $chosen,        array(),  XARVAR_NOT_REQUIRED))  return;
    if(!xarVarFetch('options',       'isset', $options,       NULL, XARVAR_DONT_SET))   return;

    xarTplSetThemeName('installer');
    $data['language'] = 'English';
    $data['phase'] = 8;
    $data['phase_label'] = xarML('Choose configuration options');

    include $configuration;
    $fileModules = unserialize(xarModGetVar('installer','modulelist'));
    $func = "installer_" . basename("$configuration",'.conf.php') . "_moduleoptions";
    $modules = $func();
    $availablemodules = $awolmodules = array();
    foreach ($modules as $module) {
        if (in_array($module['name'],array_keys($fileModules))) {
            if ($module['regid'] == $fileModules[$module['name']]['regid']) {
                $availablemodules[] = $module;
                unset($fileModules[$module['name']]);
            }
        }
        else $awolmodules[] = ucfirst($module['name']);
    }

    $options2 = $options3 = array();
    foreach ($availablemodules as $availablemodule) {
//            if(xarMod_getState($availablemodule['regid']) != XARMOD_STATE_MISSING_FROM_UNINITIALISED) {
//                echo var_dump($availablemodule);exit;
            $options2[] = array(
                       'item' => $availablemodule['regid'],
                       'option' => 'true',
                       'comment' => xarML('Install the #(1) module.',ucfirst($availablemodule['name']))
                       );
//            }
    }
    foreach ($fileModules as $fileModule) {
//            if(xarMod_getState($fileModule['regid']) != XARMOD_STATE_MISSING_FROM_UNINITIALISED) {
            $options3[] = array(
                       'item' => $fileModule['regid'],
                       'option' => 'false',
                       'comment' => xarML('Install the #(1) module.',ucfirst($fileModule['name']))
                       );
//            }
    }

    if (!$confirmed) {

        $func = "installer_" . basename("$configuration",'.conf.php') . "_privilegeoptions";
        $data['options1'] = $func();
        $data['options2'] = $options2;
        $data['options3'] = $options3;
        $data['missing'] = implode(', ',$awolmodules);
        $data['configuration'] = $configuration;
        return $data;
    }
    else {
        /*********************************************************************
        * Empty the privilege tables
        *********************************************************************/
        list($dbconn) = xarDBGetConn();
        $sitePrefix = xarDBGetSiteTablePrefix();
        $query = "DELETE FROM " . $sitePrefix . '_privileges';
        if (!$dbconn->Execute($query)) return;
        $query = "DELETE FROM " . $sitePrefix . '_privmembers';
        if (!$dbconn->Execute($query)) return;
        $query = "DELETE FROM " . $sitePrefix . '_security_acl';
        if (!$dbconn->Execute($query)) return;

        /*********************************************************************
        * Enter some default privileges
        * Format is
        * register(Name,Realm,Module,Component,Instance,Level,Description)
        *********************************************************************/

        xarRegisterPrivilege('Administration','All','All','All','All','ACCESS_ADMIN',xarML('Admin access to all modules'));
        xarRegisterPrivilege('GeneralLock','All','empty','All','All','ACCESS_NONE',xarML('A container privilege for denying access to certain roles'));
        xarRegisterPrivilege('LockMyself','All','roles','Roles','Myself','ACCESS_NONE',xarML('Deny access to Myself role'));
        xarRegisterPrivilege('LockEverybody','All','roles','Roles','Everybody','ACCESS_NONE',xarML('Deny access to Everybody role'));
        xarRegisterPrivilege('LockAnonymous','All','roles','Roles','Anonymous','ACCESS_NONE',xarML('Deny access to Anonymous role'));
        xarRegisterPrivilege('LockAdministrators','All','roles','Roles','Administrators','ACCESS_NONE',xarML('Deny access to Administrators role'));
        xarRegisterPrivilege('LockAdministration','All','privileges','Privileges','Administration','ACCESS_NONE',xarML('Deny access to Administration privilege'));
        xarRegisterPrivilege('LockGeneralLock','All','privileges','Privileges','GeneralLock','ACCESS_NONE',xarML('Deny access to GeneralLock privilege'));

        /*********************************************************************
        * Arrange the  privileges in a hierarchy
        * Format is
        * makeEntry(Privilege)
        * makeMember(Child,Parent)
        *********************************************************************/

        xarMakePrivilegeRoot('Administration');
        xarMakePrivilegeRoot('GeneralLock');
        xarMakePrivilegeMember('LockMyself','GeneralLock');
        xarMakePrivilegeMember('LockEverybody','GeneralLock');
        xarMakePrivilegeMember('LockAnonymous','GeneralLock');
        xarMakePrivilegeMember('LockAdministrators','GeneralLock');
        xarMakePrivilegeMember('LockAdministration','GeneralLock');
        xarMakePrivilegeMember('LockGeneralLock','GeneralLock');

        /*********************************************************************
        * Assign the default privileges to groups/users
        * Format is
        * assign(Privilege,Role)
        *********************************************************************/

        xarAssignPrivilege('Administration','Administrators');
        xarAssignPrivilege('GeneralLock','Everybody');
        xarAssignPrivilege('GeneralLock','Administrators');
        xarAssignPrivilege('GeneralLock','Users');

        // disable caching of module state in xarMod.php
            $GLOBALS['xarMod_noCacheState'] = true;
            xarModAPIFunc('modules','admin','regenerate');

        // load the modules from the configuration
            foreach ($options2 as $module) {
                if(in_array($module['item'],$chosen)) {
                   $dependents = xarModAPIFunc('modules','admin','getalldependencies',array('regid'=>$module['item']));
                   if (count($dependents['unsatisfiable']) > 0) {
//                   echo var_dump($dependents);exit;
                        $msg = xarML("Cannot load because of unsatisfied dependencies. One or more of the following modules is missing: ");
                        foreach ($dependents['unsatisfiable'] as $dependent) {
                            $modname = isset($dependent['name']) ? $dependent['name'] : "Unknown";
                            $modid = isset($dependent['id']) ? $dependent['id'] : $dependent;
                            $msg .= $modname . " (ID: " . $modid . "), ";
                        }
                        $msg = trim($msg,', ') . ". " . xarML("Please check the listings at www.xaraya.com to identify any modules flagged as 'Unknown'.");
                        $msg .= " " . xarML('Add the missing module(s) to the modules directory and run the installer again.');
                        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_DEPENDENCY', $msg);
                        return;
                   }
                   xarModAPIFunc('modules','admin','installwithdependencies',array('regid'=>$module['item']));
//                    xarModAPIFunc('modules','admin','activate',array('regid'=>$module['item']));
                }
            }
        // load any other modules chosen
            xarModAPIFunc('modules','admin','regenerate');
            foreach ($options3 as $module) {
                if(in_array($module['item'],$chosen)) {
                    xarModAPIFunc('modules','admin','installwithdependencies',array('regid'=>$module['item']));
//                    xarModAPIFunc('modules','admin','activate',array('regid'=>$module['item']));
                }
            }
        $func = "installer_" . basename("$configuration",'.conf.php') . "_configuration_load";
        $func($chosen);
        $content['marker'] = '[x]';                                           // create the user menu
        $content['displaymodules'] = 1;
        $content['content'] = '';

        // Load up database
        list($dbconn) = xarDBGetConn();
        $tables = xarDBGetTables();

        $blockGroupsTable = $tables['block_groups'];

        $query = "SELECT    xar_id as id
                  FROM      $blockGroupsTable
                  WHERE     xar_name = 'left'";

        $result =& $dbconn->Execute($query);
        if (!$result) return;

        // Freak if we don't get one and only one result
        if ($result->PO_RecordCount() != 1) {
            $msg = xarML("Group 'left' not found.");
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return;
        }

        list ($leftBlockGroup) = $result->fields;

        $adminBlockId= xarModAPIFunc('blocks',
                                     'admin',
                                     'block_type_exists',
                                     array('modName'  => 'base',
                                           'blockType'=> 'menu'));

        if (!isset($adminBlockId) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
            return;
        }

        xarModAPIFunc('blocks','admin','create_instance',array('title' => 'Main Menu',
                                                               'name' => 'mainmenu',
                                                               'type' => $adminBlockId,
                                                               'group' => $leftBlockGroup,
                                                               'template' => '',
                                                               'content' => serialize($content),
                                                               'state' => 2));

        xarResponseRedirect(xarModURL('installer', 'admin', 'finish', array('theme' => 'installer')));
    }

}


function installer_admin_finish()
{

    xarUserLogOut();
// log in admin user
    $uname = xarModGetVar('roles','lastuser');
    $pass = xarModGetVar('roles','adminpass');
    if (!xarUserLogIn($uname, $pass, 0)) {
        $msg = xarML('Cannot log in the default administrator. Check your setup.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return false;
    }

    $data['phase'] = 6;
    $data['phase_label'] = xarML('Step Six');
    $data['finalurl'] = xarModURL('installer', 'admin', 'cleanup');

    return $data;
}


function installer_admin_cleanup() {
    $remove = xarModDelVar('roles','adminpass');
    $remove = xarModDelVar('installer','modules');

    // Load up database
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    $blockGroupsTable = $tables['block_groups'];

    $query = "SELECT    xar_id as id
              FROM      $blockGroupsTable
              WHERE     xar_name = 'right'";

    // Check for db errors
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Freak if we don't get one and only one result
    if ($result->PO_RecordCount() != 1) {
        $msg = xarML("Group 'right' not found.");
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    list ($rightBlockGroup) = $result->fields;

    $loginBlockId = xarModAPIFunc(  'blocks', 'admin', 'block_type_exists',
                            array(  'modName'   => 'roles',
                                    'blockType' => 'login'));

    if (!isset($loginBlockId) && xarExceptionMajor() != XAR_NO_EXCEPTION) return;

    if (!xarModAPIFunc( 'blocks', 'admin', 'create_instance',
                array(  'title'    => 'Login',
                        'name'     => 'login',
                        'type'     => $loginBlockId,
                        'group'    => $rightBlockGroup,
                        'template' => '',
                        'state'    => 2))) return;

    $query = "SELECT    xar_id as id
              FROM      $blockGroupsTable
              WHERE     xar_name = 'header'";

    // Check for db errors
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Freak if we don't get one and only one result
    if ($result->PO_RecordCount() != 1) {
        $msg = xarML("Group 'header' not found.");
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    list ($headerBlockGroup) = $result->fields;

    $metaBlockId = xarModAPIFunc('blocks',
                                 'admin',
                                 'block_type_exists',
                             array('modName'  => 'themes',
                                       'blockType'=> 'meta'));

    if (!isset($metaBlockId) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    if (!xarModAPIFunc('blocks',
                       'admin',
                       'create_instance', array('title'    => 'Meta',
                                                'name'     => 'meta',
                                                'type'     => $metaBlockId,
                                                'group'    => $headerBlockGroup,
                                                'template' => '',
                                                'state'    => 2))) {
        return;
    }

    $query = "SELECT    xar_id as id
              FROM      $blockGroupsTable
              WHERE     xar_name = 'syndicate'";

    // Check for db errors
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Freak if we don't get one and only one result
    if ($result->PO_RecordCount() != 1) {
        $msg = xarML("Group 'syndicate' not found.");
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    list ($syndicateBlockGroup) = $result->fields;

    $syndicateBlockId= xarModAPIFunc('blocks',
                                     'admin',
                                     'block_type_exists',
                                     array('modName'  => 'themes',
                                           'blockType'=> 'syndicate'));

    if (!isset($syndicateBlockId) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    if (!xarModAPIFunc('blocks',
                       'admin',
                       'create_instance', array('title'    => 'Syndicate',
                                                'name'     => 'syndicate',
                                                'type'     => $syndicateBlockId,
                                                'group'    => $syndicateBlockGroup,
                                                'template' => '',
                                                'state'    => 2))) {
        return;
    }
    xarResponseRedirect('index.php');
}
function installer_admin_modifyconfig() {}
?>