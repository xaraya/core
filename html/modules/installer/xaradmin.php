<?php

/**
 * File: $Id$
 *
 * Installer admin display functions
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage module name
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
 * Phase 3: Check system settings and ability to write config
 *
 * @access private
 * @param args[agree] string
 * @returns array
 * @todo <johnny> FIX Installer ML
 * @todo <johnny> decide what to about var dir
 */
function installer_admin_phase3()
{
    $agree = xarVarCleanFromInput('agree');

    if ($agree != 'agree') {
        // didn't agree to license, don't install
        xarResponseRedirect('install.php');
    }
    
    //Defaults
    $systemConfigIsWritable   = false;
    $siteConfigIsWritable     = true;
    $cacheTemplatesIsWritable = true;
    
    $systemVarDir             = xarCoreGetVarDirPath();
    $cacheTemplatesDir        = $systemVarDir . '/cache/templates';
    $systemConfigFile         = $systemVarDir . '/config.system.php';
    $siteConfigFile           = $systemVarDir . '/config.site.xml';
    
    if (is_writable($systemConfigFile)) {
        $systemConfigIsWritable = true;
    }
    
    $data['cacheTemplatesIsWritable'] = $cacheTemplatesIsWritable;
    $data['systemConfigFile'] = $systemConfigFile;
    $data['siteConfigFile']   = $siteConfigFile;
    $data['siteConfigIsWritable'] = $siteConfigIsWritable;
    $data['systemConfigIsWritable'] = $systemConfigIsWritable;
    
    $data['language'] = 'English';
    $data['phase'] = 3;
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
                                         'oci8'     => 'Oracle',
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
 * @param intranetMode
 * @param createDb
 * @todo FIX installer ML
 * @todo better error checking on arguments
 * @todo Fix intranet mode
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
       xarCore_die($msg);
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
            $msg = xarML('Could not create database (#(1)).', $dbName);
            xarCore_die($msg);
            return;
        }
    }

    // Start the database
    xarCoreInit(XARCORE_SYSTEM_ADODB);

// install the security stuff here, but disable the registerMask and
// and securitycheck functions until we've finished the installation process
	global $installing;
	$installing = true;
	include_once 'includes/xarSecurity.php';
	xarSecurity_init();

    // Load in modules/installer/xarinit.php and start the install
    if (!xarInstallAPIFunc('installer', 'admin', 'initialise',
                                                 array('directory' => 'installer',
                                                       'initfunc'  => 'init'))) {
        return;
    }

    //session_start(); 
    //session_destroy();

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
     xarTplSetThemeName('installer');

// activate the security stuff
// create the default roles and privileges setup
	include 'modules/privileges/xarsetup.php';
	initializeSetup();

// log in admin user
    if (!xarUserLogIn('admin', 'xaraya', 0)) {
        $msg = xarML('Cannot log in the default administrator. Check your setup.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
			return false;
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
 * @return bool
 */
function installer_admin_create_administrator()
{
	xarTplSetThemeName('installer');
    $data['language'] = 'English';
    $data['phase'] = 6;
    $data['phase_label'] = xarML('Create Administrator');

// Security Check
	if(!securitycheck('Admin')) return;

    include_once 'modules/roles/xarroles.php';
     
    if (!xarVarCleanFromInput('create')) {
// create a role from the data
    	$roles = new xarRoles();
    	$role = $roles->getRole(3);

// assemble the template data
		$data['install_admin_username'] = $role->getUser();
    	$data['install_admin_name'] = $role->getName();
    	$data['install_admin_email'] = $role->getEmail();
        return $data;
    }
    
    list ($username,
          $name,
          $pass,
          $email,
          $url) = xarVarCleanFromInput('install_admin_username',
                                       'install_admin_name',
                                       'install_admin_password',
                                       'install_admin_email',
                                       'install_admin_url');
    
    xarModSetVar('mail', 'adminname', $name);
    xarModSetVar('mail', 'adminmail', $email);


// assemble the args into an array for the role constructor
	$password = md5($pass);
	$pargs = array('pid'=>3,
					'name'=>$name,
					'type'=>0,
					'uname'=>$username,
					'email'=>$email,
					'pass'=>$password,
					'url'=>$url,
					'state'=>3);

// create a role from the data
    $role = new xarRole($pargs);

//Try to update the role to the repository and bail if an error was thrown
    $modifiedrole = $role->update();
    if (!$modifiedrole) {return;}

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

    $loginBlockId = xarBlockTypeExists('roles', 'login');

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

    $metaBlockId = xarBlockTypeExists('themes', 'meta');

    if (!isset($metaBlockId) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    if (!xarModAPIFunc('blocks',
                       'admin',
                       'create_instance', array('title'    => 'Meta',
                                                'type'     => $metaBlockId,
                                                'group'    => $headerBlockGroup,
                                                'template' => '',
                                                'state'    => 2))) {
        return;
    }

    xarConfigSetVar('Site.BL.DefaultTheme','Xaraya_Classic');

    if (xarVarIsCached('Config.Variables', 'Site.BL.DefaultTheme')) {
        xarVarDelCached('Config.Variables', 'Site.BL.DefaultTheme');
    }

    $data['phase'] = 6;
    $data['phase_label'] = xarML('Step Six');
    return $data;
}


function installer_admin_modifyconfig(){}

