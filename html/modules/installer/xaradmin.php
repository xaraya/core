<?php
/**
 * Installer
 *
 * @package core modules
 * @copyright (C) 2005-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Installer
 * @link http://xaraya.com/index.php/release/200.html
 */

/* Do not allow this script to run if the install script has been removed.
 * This assumes the install.php and index.php are in the same directory.
 * @author Paul Rosania
 * @author Marcel van der Boom <marcel@hsdev.com>
 */

if (!file_exists('install.php')) {xarCore_die(xarML('Already installed'));}

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
 */
function installer_admin_phase1()
{
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

    // Get the installed locales
    $locales = xarMLSListSiteLocales();

    // Construct the array for the selectbox (iso3code, string in own locale)
    if(!empty($locales)) {
        $languages = array();
        foreach ($locales as $locale) {
            // Get the isocode and the description
            // Before we load the locale data, let's check if the locale is there

            // <marco> This check is really not necessary since available locales are
            // already determined from existing files. The relative code is in install.php
            //$fileName = xarCoreGetVarDirPath() . "/locales/$locale/locale.xml";
            //if(file_exists($fileName)) {
            $locale_data =& xarMLSLoadLocaleData($locale);
            $languages[$locale] = $locale_data['/language/display'];
            //}
        }
    }

    $data['install_language'] = $install_language;
    $data['languages'] = $languages;
    $data['phase'] = 1;
    $data['phase_label'] = xarML('Step One');

    return $data;
}

/**
 * Phase 2: Accept License Page
 *
 * @access private
 * @return array
 */
function installer_admin_phase2()
{
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

    $data['language'] = $install_language;
    $data['phase'] = 2;
    $data['phase_label'] = xarML('Step Two');

    return $data;
}

/**
 * Check whether directory permissions allow to write and read files inside it
 *
 * @access private
 * @param string dirname directory name
 * @return bool true if directory is writable, readable and executable
 */
function check_dir($dirname)
{
    if (@touch($dirname . '/.check_dir')) {
        $fd = @fopen($dirname . '/.check_dir', 'r');
        if ($fd) {
            fclose($fd);
            unlink($dirname . '/.check_dir');
        } else {
            return false;
        }
    } else {
        return false;
    }
    return true;
}

/**
 * Phase 3: Check system settings
 *
 * @access private
 * @param agree string
 * @return array
 * @todo <johnny> make sure php version checking works with
 *       php versions that contain strings
 */
function installer_admin_phase3()
{
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

    if (!xarVarFetch('agree','regexp:(agree|disagree)',$agree)) return;

    if ($agree != 'agree') {
        // didn't agree to license, don't install
        xarResponseRedirect('install.php?install_phase=2&install_language='.$install_language);
    }

    //Defaults
    $systemConfigIsWritable   = false;
    $cacheTemplatesIsWritable = false;
    $rssTemplatesIsWritable   = false;
    $metRequiredPHPVersion    = false;

    $systemVarDir             = xarCoreGetVarDirPath();
    $cacheDir                 = $systemVarDir . '/cache';
    $cacheTemplatesDir        = $systemVarDir . '/cache/templates';
    $rssTemplatesDir          = $systemVarDir . '/cache/rss';
    $adodbTemplatesDir        = $systemVarDir . '/cache/adodb';
    $systemConfigFile         = $systemVarDir . '/config.system.php';
    $phpLanguageDir           = $systemVarDir . '/locales/' . $install_language . '/php';
    $xmlLanguageDir           = $systemVarDir . '/locales/' . $install_language . '/xml';

    if (function_exists('version_compare')) {
        if (version_compare(PHP_VERSION,'4.1.2','>=')) $metRequiredPHPVersion = true;
    }

    $systemConfigIsWritable     = is_writable($systemConfigFile);
    $cacheIsWritable            = check_dir($cacheDir);
    $cacheTemplatesIsWritable   = (check_dir($cacheTemplatesDir) || @mkdir($cacheTemplatesDir, 0700));
    $rssTemplatesIsWritable     = (check_dir($rssTemplatesDir) || @mkdir($rssTemplatesDir, 0700));
    $adodbTemplatesIsWritable   = (check_dir($adodbTemplatesDir) || @mkdir($adodbTemplatesDir, 0700));
    $phpLanguageFilesIsWritable = xarMLS__iswritable($phpLanguageDir);
    $xmlLanguageFilesIsWritable = xarMLS__iswritable($xmlLanguageDir);
    $memLimit = trim(ini_get('memory_limit'));
    $memLimit = empty($memLimit) ? '8M' : $memLimit;
    $memVal = substr($memLimit,0,strlen($memLimit)-1);
    switch(strtolower($memLimit{strlen($memLimit)-1})) {
        case 'g': $memVal *= 1024;
        case 'm': $memVal *= 1024;
        case 'k': $memVal *= 1024;
    }

    // Extension Check
    $data['xmlextension']             = extension_loaded('xml');
    $data['mysqlextension']           = extension_loaded('mysql');
    $data['pgsqlextension']           = extension_loaded ('pgsql');
    $data['xsltextension']            = extension_loaded ('xslt');
    $data['ldapextension']            = extension_loaded ('ldap');
    $data['gdextension']              = extension_loaded ('gd');

    $data['metRequiredPHPVersion']    = $metRequiredPHPVersion;
    $data['phpVersion']               = PHP_VERSION;
    $data['cacheDir']                 = $cacheDir;
    $data['cacheIsWritable']          = $cacheIsWritable;
    $data['cacheTemplatesDir']        = $cacheTemplatesDir;
    $data['cacheTemplatesIsWritable'] = $cacheTemplatesIsWritable;
    $data['rssTemplatesDir']          = $rssTemplatesDir;
    $data['rssTemplatesIsWritable']   = $rssTemplatesIsWritable;
    $data['adodbTemplatesDir']        = $adodbTemplatesDir;
    $data['adodbTemplatesIsWritable'] = $adodbTemplatesIsWritable;
    $data['systemConfigFile']         = $systemConfigFile;
    $data['systemConfigIsWritable']   = $systemConfigIsWritable;
    $data['phpLanguageDir']             = $phpLanguageDir;
    $data['phpLanguageFilesIsWritable'] = $phpLanguageFilesIsWritable;
    $data['xmlLanguageDir']             = $xmlLanguageDir;
    $data['xmlLanguageFilesIsWritable'] = $xmlLanguageFilesIsWritable;
    $data['memory_limit']               = $memLimit;
    $data['metMinMemRequirement']       = $memVal >= 8 * 1024 * 1024;

    $data['language']    = $install_language;
    $data['phase']       = 3;
    $data['phase_label'] = xarML('Step Three');

    return $data;
}

/**
 * Phase 4: Database Settings Page
 *
 * @access private
 * @return array of default values for the database creation
 */
function installer_admin_phase4()
{
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

    // Get default values from config files
    $data['database_host']       = xarCore_getSystemVar('DB.Host');
    $data['database_username']   = xarCore_getSystemVar('DB.UserName');
    $data['database_password']   = '';//xarCore_getSystemvar('DB.Password');
    $data['database_name']       = xarCore_getSystemvar('DB.Name');
    $data['database_prefix']     = xarCore_getSystemvar('DB.TablePrefix');
    $data['database_type']       = xarCore_getSystemvar('DB.Type');
    // Supported  Databases:
    $data['database_types']      = array('mysql'    => array('name' => 'MySQL'   , 'available' => extension_loaded('mysql')),
                                         'postgres' => array('name' => 'Postgres', 'available' => extension_loaded('pgsql')),
                                         'sqlite'   => array('name' => 'SQLite'  , 'available' => extension_loaded('sqlite')),
                                         // use portable version of OCI8 driver to support ? bind variables
                                         'oci8po'   => array('name' => 'Oracle 9+ (not supported)'  , 'available' => extension_loaded('oci8')),
                                         'mssql'    => array('name' => 'MS SQL Server (not supported)' , 'available' => extension_loaded('mssql')),
                                        );

    $data['language'] = $install_language;
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
 * @todo better error checking on arguments
 */
function installer_admin_phase5()
{
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    xarVarSetCached('installer','installing', true);

    // Get arguments
    if (!xarVarFetch('install_database_host','pre:trim:passthru:str',$dbHost)) return;
    if (!xarVarFetch('install_database_name','pre:trim:passthru:str',$dbName,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_username','pre:trim:passthru:str',$dbUname,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_password','pre:trim:passthru:str',$dbPass,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_prefix','pre:trim:passthru:str',$dbPrefix,'xar',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_type','str:1:',$dbType)) return;
    if (!xarVarFetch('install_create_database','checkbox',$createDB,false,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('confirmDB','bool',$confirmDB,false,XARVAR_NOT_REQUIRED)) return;

    if ($dbName == '') {
        $msg = xarML('No database was specified');
        xarCore_die($msg);
        return;
    }

    // allow only a-z 0-9 and _ in table prefix
    if (!preg_match('/^\w*$/',$dbPrefix)) {
        $msg = xarML('Invalid character in table prefix');
        xarCore_die($msg);
        return;
    }
    // Save config data
    $config_args = array('dbHost'    => $dbHost,
                         'dbName'    => $dbName,
                         'dbUname'   => $dbUname,
                         'dbPass'    => $dbPass,
                         'dbPrefix'  => $dbPrefix,
                         'dbType'    => $dbType);

    if (!xarInstallAPIFunc('modifyconfig', $config_args)) {
        return;
    }

    //Do we already have a db?
    //TODO: rearrange the loading sequence so that I can use xar functions
    //rather than going directly to adodb
    // Load in ADODB
    // FIXME: This is also in xarDB init, does it need to be here?
    if (!defined('XAR_ADODB_DIR')) {
        define('XAR_ADODB_DIR','xaradodb');
    }
    include_once XAR_ADODB_DIR . '/adodb.inc.php';
    $ADODB_CACHE_DIR = xarCoreGetVarDirPath() . "/cache/adodb";

    // {ML_dont_parse 'includes/xarDB.php'}
    include_once 'includes/xarDB.php';

    // Check if there is a xar- version of the driver, and use it.
    // Note the driver we load does not affect the database type.
    if (xarDBdriverExists('xar' . $dbType, 'adodb')) {
        $dbDriver = 'xar' . $dbType;
    } else {
        $dbDriver = $dbType;
    }

    $dbconn = ADONewConnection($dbDriver);
    $dbExists = TRUE;

    // Not all Database Servers support selecting the specific db *after* connecting
    // so let's try connecting with the dbname first, and then without if that fails
    $dbConnected = @$dbconn->Connect($dbHost, $dbUname, $dbPass, $dbName);

    if (!$dbConnected) {
        // Couldn't connect to the specified dbName. Let's try connecting without dbName now
        // Need to reset dbconn prior to trying just a normal connection
        unset($dbconn);
        $dbconn = ADONewConnection($dbDriver);

        if ($dbConnected = @$dbconn->Connect($dbHost, $dbUname, $dbPass)) {
            $dbExists = FALSE;
        } else {
            $dbConnected = FALSE;
            $dbExists = FALSE;
        }
    }

    if (!$dbConnected) {
        $msg = xarML('Database connection failed. The information supplied was erroneous, such as a bad or missing password or wrong username.');
        xarCore_die($msg);
        return;
    }

    if (!$createDB && !$dbExists) {
        $msg = xarML('Database #(1) doesn\'t exist and it wasnt selected to be created.', $dbName);
        xarCore_die($msg);
        return;
    }

    $data['confirmDB']  = $confirmDB;
    if ($dbExists && !$confirmDB) {
        $data['dbHost']     = $dbHost;
        $data['dbName']     = $dbName;
        $data['dbUname']    = $dbUname;
        $data['dbPass']     = $dbPass;
        $data['dbPrefix']   = $dbPrefix;
        $data['dbType']     = $dbType;
        $data['install_create_database']      = $createDB;
        $data['language']    = $install_language;
        return $data;
    }

    // Create the database if necessary
    if ($createDB) {
        $data['confirmDB']  = true;
        //Let's pass all input variables thru the function argument or none, as all are stored in the system.config.php
        //Now we are passing all, let's see if we gain consistency by loading config.php already in this phase?
        //Probably there is already a core function that can make that for us...
        //the config.system.php is lazy loaded in xarCore_getSystemVar($name), which means we cant reload the values
        // in this phase... Not a big deal 'though.
        if ($dbExists) {
            if (!$dbconn->Execute('DROP DATABASE ' . $dbName)) return;
        }
        if (!xarInstallAPIFunc('createdb', $config_args)) {
            $msg = xarML('Could not create database (#(1)). Check if you already have a database by that name and remove it.', $dbName);
            xarCore_die($msg);
            return;
        }
    }
    else {
        $removetables = true;
    }

    // Start the database
    $systemArgs = array('userName' => $dbUname,
                        'password' => $dbPass,
                        'databaseHost' => $dbHost,
                        'databaseType' => $dbType,
                        'databaseName' => $dbName,
                        'systemTablePrefix' => $dbPrefix,
                        'siteTablePrefix' => $dbPrefix);
    // Connect to database
    $whatToLoad = XARCORE_SYSTEM_NONE;
    xarDB_init($systemArgs, $whatToLoad);

    // drop all the tables that have this prefix
    //TODO: in the future need to replace this with a check further down the road
    // for which modules are already installed
    xarDBLoadTableMaintenanceAPI();
    if (isset($removetables) && $removetables) {
        $dbconn =& xarDBGetConn();
        $result = $dbconn->Execute($dbconn->metaTablesSQL);
        if(!$result) return;
        $tables = array();
        while(!$result->EOF) {
            list($table) = $result->fields;
            $parts = explode('_',$table);
            if ($parts[0] == $dbPrefix) $tables[] = $table;
            $result->MoveNext();
        }
        foreach ($tables as $table) {
            // FIXME: a lot!
            // 1. the drop table drops the sequence while the table gets dropped in the second statement
            //    so if that fails, the table remains while the sequence is gone, at least transactions is needed
            // 3. generating sql and executing in 2 parts sucks, wrt encapsulation
            $sql = xarDBDropTable($table,$dbType);
            $result = $dbconn->Execute($sql);
            if(!$result) return;
        }
    }

    // install the security stuff here, but disable the registerMask and
    // and xarSecurityCheck functions until we've finished the installation process

    include_once 'includes/xarSecurity.php';
    xarSecurity_init();

    // Load in modules/installer/xarinit.php and start the install
    // This effectively initializes the base module.
    if (!xarInstallAPIFunc('initialise',
                           array('directory' => 'installer',
                                 'initfunc'  => 'init'))) {
        return;
    }

    // If we are here, the base system has completed
    // We can now pass control to xaraya.
    include_once 'includes/xarConfig.php';
    $params=array();
    xarConfig_init($params,XARCORE_SYSTEM_ADODB);
    xarConfigSetVar('Site.MLS.DefaultLocale', $install_language);

    // Set the allowed locales to our "C" locale and the one used during installation
    // TODO: make this a bit more friendly.
    $necessaryLocale = array('en_US.utf-8');
    $install_locale  = array($install_language);
    $allowed_locales = array_merge($necessaryLocale, $install_locale);

    xarConfigSetVar('Site.MLS.AllowedLocales',$allowed_locales);    $data['language'] = $install_language;

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
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    xarVarSetCached('installer','installing', true);

    // create the default roles and privileges setup
    include 'modules/privileges/xarsetup.php';
    initializeSetup();

    // Set up default user properties, etc.

    // load modules into *_modules table
    if (!xarModAPIFunc('modules', 'admin', 'regenerate')) return;


    $regid=xarModGetIDFromName('authsystem');
	if (empty($regid)) {
		die(xarML('I cannot load the Authsystem module. Please make it available and reinstall'));
    }


    // Set the state and activate the following modules
    $modlist=array('roles','privileges','blocks','authsystem','themes');
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
            // Activate the theme
            if (!xarModAPIFunc('themes','admin','activate', array('regid'=> $regid)))
            {
                return;
            }
        }
    }

    // Initialise and activate mail, dynamic data
    $modlist = array('mail', 'dynamicdata');
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

# --------------------------------------------------------
#
# Create wrapper DD objects for the native itemtypes of the privileges module
#
	if (!xarModAPIFunc('privileges','admin','createobjects')) return;

    xarResponseRedirect(xarModURL('installer', 'admin', 'create_administrator',array('install_language' => $install_language)));
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
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

    xarVarSetCached('installer','installing', true);
    xarTplSetPageTemplateName('installer');

    $data['language'] = $install_language;
    $data['phase'] = 6;
    $data['phase_label'] = xarML('Create Administrator');

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
    xarModSetVar('themes', 'SiteCopyRight', '&copy; Copyright ' . date("Y") . ' ' . $name);

    if ($pass != $pass1) {
        $msg = xarML('The passwords do not match');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    if (empty($userName)) {
        $msg = xarML('You must provide a preferred username to continue.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;

    // check for spaces in the username
    } elseif (preg_match("/[[:space:]]/",$userName)) {
        $msg = xarML('There is a space in the username.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;

    // check the length of the username
    } elseif (strlen($userName) > 255) {
        $msg = xarML('Your username is too long.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;

    // check for spaces in the username (again ?)
    } elseif (strrpos($userName,' ') > 0) {
        $msg = xarML('There is a space in your username.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
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
    $blocks = array('adminmenu','waitingcontent','finclude','html','menu','php','text','content');

    foreach ($blocks as $block) {
        if (!xarModAPIFunc('blocks', 'admin', 'register_block_type', array('modName'  => 'base', 'blockType'=> $block))) return;
    }

    if (xarVarIsCached('Mod.BaseInfos', 'blocks')) xarVarDelCached('Mod.BaseInfos', 'blocks');

    // Create default block groups/instances
    //                            name        template
    $default_blockgroups = array ('left'   => '',
                                  'right'  => 'right',
                                  'header' => 'header',
                                  'admin'  => '',
                                  'center' => 'center',
                                  'topnav' => 'topnav'
                                  );

    foreach ($default_blockgroups as $name => $template) {
        if(!xarModAPIFunc('blocks','user','groupgetinfo', array('name' => $name))) {
            // Not there yet
            if(!xarModAPIFunc('blocks','admin','create_group', array('name' => $name, 'template' => $template))) return;
        }
    }

    // Load up database
    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    $blockGroupsTable = $tables['block_groups'];

    $query = "SELECT    xar_id as id
              FROM      $blockGroupsTable
              WHERE     xar_name = 'left'";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Freak if we don't get one and only one result
    if ($result->PO_RecordCount() != 1) {
        $msg = xarML("Group 'left' not found.");
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    list ($leftBlockGroup) = $result->fields;
    /* We don't need this for adminpanels now - done in Base module */
        $adminBlockType = xarModAPIFunc('blocks', 'user', 'getblocktype',
                                    array('module'  => 'base',
                                          'type'    => 'adminmenu'));

    if (empty($adminBlockType) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        return;
    }

    $adminBlockTypeId = $adminBlockType['tid'];

    if (!xarModAPIFunc('blocks', 'user', 'get', array('name'  => 'adminpanel'))) {
        if (!xarModAPIFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Admin',
                                 'name'     => 'adminpanel',
                                 'type'     => $adminBlockTypeId,
                                 'groups'   => array(array('gid'      => $leftBlockGroup,
                                                           'template' => '')),
                                 'template' => '',
                                 'state'    =>  2))) { 
            return;
        }
    }

    
    $now = time();

    $varshtml['html_content'] = 'Please delete install.php and upgrade.php from your webroot .';
    $varshtml['expire'] = $now + 24000;
    $msg = serialize($varshtml);

    $htmlBlockType = xarModAPIFunc('blocks', 'user', 'getblocktype',
                                 array('module'  => 'base',
                                       'type'    => 'html'));

    if (empty($htmlBlockType) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        return;
    }

    $htmlBlockTypeId = $htmlBlockType['tid'];

    if (!xarModAPIFunc('blocks', 'user', 'get', array('name'  => 'reminder'))) {
        if (!xarModAPIFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Reminder',
                                 'name'     => 'reminder',
                                 'content'  => $msg,
                                 'type'     => $htmlBlockTypeId,
                                 'groups'   => array(array('gid'      => $leftBlockGroup,
                                                           'template' => '')),
                                 'template' => '',
                                 'state'    => 2))) {
            return;
        }
    }

    // Initialise authentication
    // TODO: this is happening late here because we need to create a block
//	$regid = xarModGetIDFromName('authsystem');
//	if (isset($regid)) {
//		if (!xarModAPIFunc('modules', 'admin', 'initialise', array('regid' => $regid))) return;
		// Activate the module
//		if (!xarModAPIFunc('modules', 'admin', 'activate', array('regid' => $regid))) return;
//	}

    xarResponseRedirect(xarModURL('installer', 'admin', 'choose_configuration',array('install_language' => $install_language)));
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
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

    $data['language'] = $install_language;
    $data['phase'] = 7;
    $data['phase_label'] = xarML('Choose your configuration');
    xarTplSetPageTemplateName('installer');

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
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST',
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
                         'display'  => 'Core Xaraya install (aka minimal)',
                         'selected' => true);
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
                                 'display' => $configuration_name,
                                 'selected' => count($names)==0);
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
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

    xarVarSetCached('installer','installing', true);
    xarTplSetPageTemplateName('installer');

    if(!xarVarFetch('configuration', 'isset', $configuration, NULL,  XARVAR_DONT_SET))  return;
    if(!isset($configuration)) {
        $msg = xarML("Please go back and select one of the available configurations.");
        xarErrorSet(XAR_USER_EXCEPTION, 'Please select a configuration', $msg);
        return;
    }

    //I am not sure if these should these break
    if(!xarVarFetch('confirmed',     'isset', $confirmed,     NULL, XARVAR_DONT_SET))   return;
    if(!xarVarFetch('chosen',        'isset', $chosen,        array(),  XARVAR_NOT_REQUIRED))  return;
    if(!xarVarFetch('options',       'isset', $options,       NULL, XARVAR_DONT_SET))   return;

    $data['language'] = $install_language;
    $data['phase'] = 8;
    $data['phase_label'] = xarML('Choose configuration options');

    include $configuration;
    $fileModules = unserialize(xarModGetVar('installer','modulelist'));
    $func = "installer_" . basename(strval($configuration),'.conf.php') . "_moduleoptions";
    $modules = $func();
    $availablemodules = $awolmodules = $installedmodules = array();
    foreach ($modules as $module) {
        if (in_array($module['name'],array_keys($fileModules))) {
            if ($module['regid'] == $fileModules[$module['name']]['regid']) {
                if (xarMod_getState($module['regid']) == XARMOD_STATE_ACTIVE ||
                xarMod_getState($module['regid']) == XARMOD_STATE_INACTIVE) {
                    $installedmodules[] = ucfirst($module['name']);
                }
                else {
                    $availablemodules[] = $module;
                }
                unset($fileModules[$module['name']]);
            }
        }
        else $awolmodules[] = ucfirst($module['name']);
    }

    $options2 = $options3 = array();
    foreach ($availablemodules as $availablemodule) {
//        if(xarMod_getState($availablemodule['regid']) != XARMOD_STATE_MISSING_FROM_UNINITIALISED) {
//            echo var_dump($availablemodule);exit;
            $options2[] = array(
                       'item' => $availablemodule['regid'],
                       'option' => 'true',
                       'comment' => xarML('Install the #(1) module.',ucfirst($availablemodule['name']))
                       );
//        }
    }
    if (!$confirmed) {

        $func = "installer_" . basename(strval($configuration),'.conf.php') . "_privilegeoptions";
        $data['options1'] = $func();
        $data['options2'] = $options2;
        $data['options3'] = $options3;
        $data['installed'] = implode(', ',$installedmodules);
        $data['missing'] = implode(', ',$awolmodules);
        $data['configuration'] = $configuration;
        return $data;
    }
    else {
        /*********************************************************************
        * Empty the privilege tables
        *********************************************************************/
        $dbconn =& xarDBGetConn();
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
                        $msg = xarML("Cannot load because of unsatisfied dependencies. One or more of the following modules is missing: ");
                        foreach ($dependents['unsatisfiable'] as $dependent) {
                            $modname = isset($dependent['name']) ? $dependent['name'] : "Unknown";
                            $modid = isset($dependent['id']) ? $dependent['id'] : $dependent;
                            $msg .= $modname . " (ID: " . $modid . "), ";
                        }
                        $msg = trim($msg,', ') . ". " . xarML("Please check the listings at www.xaraya.com to identify any modules flagged as 'Unknown'.");
                        $msg .= " " . xarML('Add the missing module(s) to the modules directory and run the installer again.');
                        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_DEPENDENCY', $msg);
                        return;
                   }
                   xarModAPIFunc('modules','admin','installwithdependencies',array('regid'=>$module['item']));
//                    xarModAPIFunc('modules','admin','activate',array('regid'=>$module['item']));
                }
            }
        $func = "installer_" . basename(strval($configuration),'.conf.php') . "_configuration_load";
        $func($chosen);
        $content['marker'] = '[x]';                                           // create the user menu
        $content['displaymodules'] = 'All';
        $content['modulelist'] = '';
        $content['content'] = '';

        // Load up database
        $dbconn =& xarDBGetConn();
        $tables =& xarDBGetTables();

        $blockGroupsTable = $tables['block_groups'];

        $query = "SELECT    xar_id as id
                  FROM      $blockGroupsTable
                  WHERE     xar_name = 'left'";

        $result =& $dbconn->Execute($query);
        if (!$result) return;

        // Freak if we don't get one and only one result
        if ($result->PO_RecordCount() != 1) {
            $msg = xarML("Group 'left' not found.");
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return;
        }

        list ($leftBlockGroup) = $result->fields;

        $menuBlockType = xarModAPIFunc('blocks', 'user', 'getblocktype',
                                     array('module'  => 'base',
                                           'type'=> 'menu'));

        if (empty($menuBlockType) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
            return;
        }

        $menuBlockTypeId = $menuBlockType['tid'];

        if (!xarModAPIFunc('blocks', 'user', 'get', array('name'  => 'mainmenu'))) {
            if (!xarModAPIFunc('blocks', 'admin', 'create_instance',
                          array('title' => 'Main Menu',
                                'name'  => 'mainmenu',
                                'type'  => $menuBlockTypeId,
                                'groups' => array(array('gid' => $leftBlockGroup,
                                                        'template' => '',)),
                                'template' => '',
                                'content' => serialize($content),
                                'state' => 2))) {
                return;
            }
        }

        xarResponseRedirect(xarModURL('installer', 'admin', 'cleanup'));
    }

}


function installer_admin_cleanup()
{
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    xarTplSetPageTemplateName('installer');

    xarUserLogOut();
// log in admin user
    $uname = xarModGetVar('roles','lastuser');
    $pass = xarModGetVar('roles','adminpass');

    if (!xarUserLogIn($uname, $pass, 0)) {
        $msg = xarML('Cannot log in the default administrator. Check your setup.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return false;
    }

    $remove = xarModDelVar('roles','adminpass');
    $remove = xarModDelVar('installer','modules');

    // Load up database
    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

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
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    list ($rightBlockGroup) = $result->fields;

   //Get the info and add the Login block which is now in authsystem module
	$loginBlockType = xarModAPIFunc('blocks', 'user', 'getblocktype',
                                    array('module' => 'authsystem',
                                          'type'   => 'login'));

    if (empty($loginBlockType) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        return;
    }
   //Check for any sign of the Registration module (may have been installed in the configurations)
	$regloginBlockType = xarModAPIFunc('blocks', 'user', 'getblocktype',
                                    array('module' => 'registration',
                                          'type'   => 'rlogin'));

    if (empty($regloginBlockType) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        //return; no don't return, it may not have been loaded
    }
    $loginBlockTypeId = $loginBlockType['tid'];
    //We only want to create the login block if one doesn't already exist - with registration module or authsystem
    //Registration module might be selected in the config options
    if (!xarModAPIFunc('blocks', 'user', 'get', array('name'  => 'login')) && !isset($regloginBlockType['tid'])) {
        if (!xarModAPIFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Login',
                                 'name'     => 'login',
                                 'type'     => $loginBlockTypeId,
                                 'groups'    => array(array('gid'      => $rightBlockGroup,
                                                           'template' => '')),
                                 'template' => '',
                                 'state'    => 2))) {
            return;
        }
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
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    list ($headerBlockGroup) = $result->fields;

    $metaBlockType = xarModAPIFunc('blocks', 'user', 'getblocktype',
                                   array('module' => 'themes',
                                         'type'   => 'meta'));

    if (empty($metaBlockType) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        return;
    }

    $metaBlockTypeId = $metaBlockType['tid'];

    if (!xarModAPIFunc('blocks', 'user', 'get', array('name'  => 'meta'))) {
        if (!xarModAPIFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Meta',
                                 'name'     => 'meta',
                                 'type'     => $metaBlockTypeId,
                                 'groups'    => array(array('gid'      => $headerBlockGroup,
                                                           'template' => '')),
                                 'template' => '',
                                 'state'    => 2))) {
            return;
        }
    }

    $data['language']    = $install_language;
    $data['phase'] = 6;
    $data['phase_label'] = xarML('Step Six');
    $data['finalurl'] = xarModURL('installer', 'admin', 'finish');

    return $data;
}


function installer_admin_finish()
{
    xarModAPIFunc('dynamicdata','admin','importpropertytypes', array('flush' => true));
    xarResponseRedirect('index.php');
}

function installer_admin_upgrade1()
{

    $data['xarProduct'] = xarConfigGetVar('System.Core.VersionId');
    $data['xarVersion'] = xarConfigGetVar('System.Core.VersionNum');
    $data['xarRelease'] = xarConfigGetVar('System.Core.VersionSub');
    $data['descr'] = xarML('Now preparing to run an upgrade from prior #(1) Version <strong>#(2)</strong> (release #(3))
                    to #(4) version <strong>#(5)</strong> (release #(6))',
                    $data['xarProduct'],$data['xarVersion'],$data['xarRelease'],
                    XARCORE_VERSION_ID, XARCORE_VERSION_NUM, XARCORE_VERSION_SUB);
        $data['$title'] = xarML('Xaraya Upgrade');

    if (XARCORE_VERSION_NUM == $data['xarVersion']) {
        $data['alreadydone']=xarML('You have already upgraded to #(1). The upgrade script only needs to run once.', $data['xarVersion']);
    }else{
        $data['alreadydone']='';
    }
    
    if ($data['xarVersion'] < '1.0.0') {
        $data['versionlow']=
        xarML('<p><strong>WARNING</strong>: Your current site is #(1).
               You must upgrade to Xaraya Version <strong>1.0.x</strong> before continuing.</p>
               <p>Please download the Version 1.0.2 and upgrade your Xaraya site before continuing</p>', $data['xarVersion']);
    }else{
        $data['versionlow']='';
    }
    $data['phase'] = 1;
    $data['phase_label'] = xarML('Step One');

    return $data;
}

function installer_admin_upgrade2()
{
     $thisdata['finishearly']=0;
     $thisdata['xarProduct'] = xarConfigGetVar('System.Core.VersionId');
     $thisdata['xarVersion'] = xarConfigGetVar('System.Core.VersionNum');
     $thisdata['xarRelease'] = xarConfigGetVar('System.Core.VersionSub');
     
     //Load this early
     xarDBLoadTableMaintenanceAPI();
     $sprefix=xarDBGetSiteTablePrefix();

    $instancestable = $sprefix."_security_instances";
    $privilegestable = $sprefix."_privileges";
    $modulestable=$sprefix.'_modules';
    $categorytable=$sprefix.'_categories';
    $blockinstancetable=$sprefix.'_block_instances';
    $blocktypestable=$sprefix.'_block_types';
    $hitcounttable =$sprefix.'_hitcount';
    $ratingstable=$sprefix.'_ratings';

    //Upgrade the Base module
    $content='';
        // upgrades for the base module (since it is a core module, and they cannot be upgraded in the normal way)
        // - theme tags for JavaScript
        if (xarModIsAvailable('base')) {
            // Add theme tags that do not yet exist.
            // Leave the attributes open for now, until we know how it's going to work.
            $module_base_update_count = 0;

            // Include a JavaScript file in a page,
            $base_update_theme_tag = 'base-include-javascript';
            if (!xarTplGetTagObjectFromName($base_update_theme_tag)) {
            xarTplRegisterTag(
                'base', $base_update_theme_tag, array(),
                'base_javascriptapi_handlemodulejavascript'
            );
            $module_base_update_count += 1;
            $content .= "Base module: added theme tag '$base_update_theme_tag'.<br />";
        }
        // Render JavaScript in a page
        $base_update_theme_tag = 'base-render-javascript';
        if (!xarTplGetTagObjectFromName($base_update_theme_tag)) {
            xarTplRegisterTag(
                'base', $base_update_theme_tag, array(),
                'base_javascriptapi_handlerenderjavascript'
            );
            $module_base_update_count += 1;
           $content .= "Base module: added theme tag '$base_update_theme_tag'.<br />";
        }

        if ($module_base_update_count == 0) {
            $content .= "Base module does not require updating.<br />";
        }
    } else {
        $content .= "Base module not available - no upgrade carried out.<br />";
    } // endif modavailable('base')

    $dbconn =& xarDBGetConn();


    // replace DynamicData component 'Type' by 'Field'
    $content .=  "Updating security instance for DynamicData.<br />";
    $query = "UPDATE $instancestable
              SET xar_component='Field'
              WHERE xar_module='dynamicdata' AND xar_component='Type'";
    $result =& $dbconn->Execute($query);

    $content .=  "Updating privileges for DynamicData.<br />";
    $query = "UPDATE $privilegestable
              SET xar_component='Field'
              WHERE xar_module='dynamicdata' AND xar_component='Type'";
    $result =& $dbconn->Execute($query);


    //check roles instances
    $rolesupdate=false;
    $rolesinstance ='SELECT DISTINCT xar_name FROM ' . xarDBGetSystemTablePrefix() . '_roles';
    $systemPrefix = xarDBGetSystemTablePrefix();
    $roleMembersTable    = $systemPrefix . '_rolemembers';
    $dbconn =& xarDBGetConn();

    // Do the Parent instance
    $query = "SELECT xar_iid, xar_header, xar_query
                FROM $instancestable
                WHERE xar_module= 'roles' AND xar_component = 'Relation' AND xar_header='Parent:'";
    $result =&$dbconn->Execute($query);

    list($iid, $header, $xarquery) = $result->fields;
    if ($rolesinstance != $xarquery) {
        $rolesupdate=true;
        $content .= "Attempting to update roles instance with component Relation and header Parent:.<br />";

        $instances = array(array('header' => 'Parent:',
                                 'query' => $rolesinstance,
                                 'limit' => 20));
        xarDefineInstance('roles','Relation',$instances,0,$roleMembersTable,'xar_uid','xar_parentid','Instances of the roles module, including multilevel nesting');
    }
    if (!$rolesupdate) {
       $content .= "Roles security_instance entry Relation/Parent does not require updating.<br />";
    }

    // Do the Child instance
    $query = "SELECT xar_iid, xar_header, xar_query
            FROM $instancestable
            WHERE xar_module= 'roles' AND xar_component = 'Relation' AND xar_header='Child:'";
    $result =&$dbconn->Execute($query);

    list($iid, $header, $xarquery) = $result->fields;
    if ($rolesinstance != $xarquery) {
        $rolesupdate=true;
        $content .= "Attempting to update roles instance with component Relation and header Child:.<br />";

        $instances = array(array('header' => 'Child:',
                                 'query' => $rolesinstance,
                                 'limit' => 20));
        xarDefineInstance('roles','Relation',$instances,0,$roleMembersTable,'xar_uid','xar_parentid','Instances of the roles module, including multilevel nesting');
    }
    if (!$rolesupdate) {
       $content .= "Roles security_instance entry Relation/Child does not require updating.<br />";
    }

    // Upgrade will check to make sure that upgrades in the past have worked, and if not, correct them now.
    $sitePrefix = xarDBGetSiteTablePrefix();
    $content .= "<p><strong>Checking Table Structure</strong></p>";

    $dbconn =& xarDBGetConn();
    // create and populate the security levels table
    $table_name['security_levels'] = $sitePrefix . '_security_levels';

    $upgrade['security_levels'] = xarModAPIFunc('installer',
                                                'admin',
                                                'CheckTableExists',
                                                array('table_name' => $table_name['security_levels']));
    if (!$upgrade['security_levels']) {
        $content .= "$table_name[security_levels] table does not exist, attempting to create... ";
        $leveltable = $table_name['security_levels'];
        $query = xarDBCreateTable($table_name['security_levels'],
                 array('xar_lid'  => array('type'       => 'integer',
                                          'null'        => false,
                                          'default'     => '0',
                                          'increment'   => true,
                                          'primary_key' => true),
                       'xar_level' => array('type'      => 'integer',
                                          'null'        => false,
                                          'default'     => '0'),
                       'xar_leveltext' => array('type'=> 'varchar',
                                          'size'        => 255,
                                          'null'        => false,
                                          'default'     => ''),
                       'xar_sdescription' => array('type'=> 'varchar',
                                          'size'        => 255,
                                          'null'        => false,
                                          'default'     => ''),
                       'xar_ldescription' => array('type'=> 'varchar',
                                          'size'        => 255,
                                          'null'        => false,
                                          'default'     => '')));
        $result = $dbconn->Execute($query);
        if (!$result){
            $content .= "failed</font><br/>\r\n";
        } else {
            $content .= "done!</font><br/>\r\n";
        }

        $content .= "Attempting to set index and fill $table_name[security_levels]... ";

        $sitePrefix = xarDBGetSiteTablePrefix();
        $index = array('name'      => 'i_'.$sitePrefix.'_security_levels_level',
                       'fields'    => array('xar_level'),
                       'unique'    => FALSE);
        $query = xarDBCreateIndex($leveltable,$index);
        $result = @$dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, -1, 'ACCESS_INVALID', 'Access Invalid', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 0, 'ACCESS_NONE', 'No Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 100, 'ACCESS_OVERVIEW', 'Overview Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 200, 'ACCESS_READ', 'Read Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 300, 'ACCESS_COMMENT', 'Comment Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 400, 'ACCESS_MODERATE', 'Moderate Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 500, 'ACCESS_EDIT', 'Edit Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 600, 'ACCESS_ADD', 'Add Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 700, 'ACCESS_DELETE', 'Delete Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 800, 'ACCESS_ADMIN', 'Admin Access', '')";
        $result =& $dbconn->Execute($query);

        if (!$result){
            $content .= "failed</font><br/>\r\n";
        } else {
            $content .= "done!</font><br/>\r\n";
        }
    } else {
        $content .= "<p>$table_name[security_levels] already exists, moving to next check. </p>";
    }

    // Drop the admin_wc table and the hooks for the admin panels.
    $table_name['admin_wc'] = $sitePrefix . '_admin_wc';

    $upgrade['waiting_content'] = xarModAPIFunc('installer',
                                                'admin',
                                                'CheckTableExists',
                                                array('table_name' => $table_name['admin_wc']));
    if ($upgrade['waiting_content']) {
        $content .= "<p>$table_name[admin_wc] table still exists, attempting to drop... </p>";
            xarModRegisterHook('item', 'waitingcontent', 'GUI',
                               'articles', 'admin', 'waitingcontent');
            xarModUnregisterHook('item', 'create', 'API',
                                 'adminpanels', 'admin', 'createwc');
            xarModUnregisterHook('item', 'update', 'API',
                                 'adminpanels', 'admin', 'deletewc');
            xarModUnregisterHook('item', 'delete', 'API',
                                 'adminpanels', 'admin', 'deletewc');
            xarModUnregisterHook('item', 'remove', 'API',
                                 'adminpanels', 'admin', 'deletewc');

            // Generate the SQL to drop the table using the API
            $query = xarDBDropTable($table_name['admin_wc']);
            $result =& $dbconn->Execute($query);
            if (!$result){
                $content .= "failed</font><br/>\r\n";
            } else {
                $content .= "done!</font><br/>\r\n";
            }
    } else {
        $content .= "<p>$table_name[admin_wc] has been dropped previously, moving to next check. </p>";
    }

    // Drop the security_privsets table
    $table_name['security_privsets'] = $sitePrefix . '_security_privsets';

    $upgrade['security_privsets'] = xarModAPIFunc('installer',
                                                'admin',
                                                'CheckTableExists',
                                                array('table_name' => $table_name['security_privsets']));
    if ($upgrade['security_privsets']) {
        $content .= "<p>$table_name[security_privsets] table still exists, attempting to drop... </p>";
        // Generate the SQL to drop the table using the API
        $query = xarDBDropTable($table_name['security_privsets']);
        $result =& $dbconn->Execute($query);
        if (!$result){
            $content .= "<p>failed</p>";
        } else {
            $content .= "<p>done!</p>";
        }
    } else {
        $content .= "<p>$table_name[security_privsets] has been dropped previously, moving to next check. </p>";
    }

    // Dynamic Data Change to prop type.
    $dynproptable = xarDBGetSiteTablePrefix() . '_dynamic_properties';

    $query = "SELECT xar_prop_type
              FROM $dynproptable
              WHERE xar_prop_name='default'
              AND xar_prop_objectid=2";
    // Check for db errors
    $result =& $dbconn->Execute($query);

    list($prop_type) = $result->fields;
    $result->Close();

    if ($prop_type != 3){
        $content .= "Dynamic Data table 'default' property with objectid 2 is not set to property type 3, attempting to change... ";
        // Generate the SQL to drop the table using the API
        $query = "UPDATE $dynproptable
                     SET xar_prop_type=3
                   WHERE xar_prop_objectid=2
                     AND xar_prop_name='default'";
        // Check for db errors
        $result =& $dbconn->Execute($query);
        if (!$result){
            $content .= "<p>failed</p>";
        } else {
            $content .= "<p>done!</p>";
        }
    } else {
        $content .= "<p>Dynamic Data table 'default' property with objectid 2 has correct property type of 3, moving to next check. </p>";
    }

    // ****************************
    // * Changes to blocks tables *
    // ****************************

    {
        // Bugs 1581/1586/1838: Update the blocks table definitions.
        // Use the data dictionary to do the checking and altering.
        $content .= "<p><strong>Checking Block Table Definitions</strong></p>";
        $dbconn =& xarDBGetConn();
        $datadict =& xarDBNewDataDict($dbconn, 'CREATE');

        // Upgrade the xar_block_instances table.
        $blockinstancestable = xarDBGetSiteTablePrefix() . '_block_instances';
        // Get column definitions for block instances table.
        $columns = $datadict->getColumns($blockinstancestable);
        // Do we have a xar_name column?
        $blocks_column_found = false;
        foreach($columns as $column) {
            if ($column->name == 'xar_name') {
                $blocks_column_found = true;
                break;
            }
        }
        // Upgrade the table (xar_block_instances) if the name column is not found.
        if (!$blocks_column_found) {
            // Create the column.
            $result = $datadict->addColumn($blockinstancestable, 'xar_name C(100) Null');
            // Update the name column with unique values.
            $query = "UPDATE $blockinstancestable"
                . " SET xar_name = " . $dbconn->Concat("'block_'", 'xar_id')
                . " WHERE xar_name IS NULL";
            $dbconn->Execute($query);
            // Now make it mandatory, and add a unique index.
            $result = $datadict->alterColumn($blockinstancestable, 'xar_name C(100) NotNull');
            $result = $datadict->createIndex(
                'i_'.xarDBGetSiteTablePrefix().'_block_instances_u2',
                $blockinstancestable,
                'xar_name',
                array('UNIQUE')
            );
            $content .= "<p>Added column xar_name to table $blockinstancestable</p>";
        } else {
            $content .= "<p>Table $blockinstancestable is up-to-date</p>";
        }

        // Upgrade the xar_block_group_instances table.
        $blockgroupinstancestable = xarDBGetSiteTablePrefix() . '_block_group_instances';
        // Get column definitions for block instances table.
        $columns = $datadict->getColumns($blockgroupinstancestable);
        // Do we have a xar_template column?
        $blocks_column_found = false;
        foreach($columns as $column) {
            if ($column->name == 'xar_template') {
                $blocks_column_found = true;
                break;
            }
        }
        if (!$blocks_column_found) {
            // Create the column.
            $result = $datadict->addColumn($blockgroupinstancestable, 'xar_template C(100) Null');
            $content .= "<p>Added column xar_template to table $blockgroupinstancestable</p>";
        } else {
            $content .= "<p>Table $blockgroupinstancestable is up-to-date</p>";
        }

        // Upgrade the xar_block_types table.
        $blocktypestable = xarDBGetSiteTablePrefix() . '_block_types';
        // Get column definitions for block instances table.
        $columns = $datadict->getColumns($blocktypestable);

        // Do we have a xar_template column?
        $blocks_column_found = false;
        foreach($columns as $column) {
            if ($column->name == 'xar_info') {
                $blocks_column_found = true;
                break;
            }
        }

        if (!$blocks_column_found) {
            // Create the column.
            $result = $datadict->addColumn($blocktypestable, 'xar_info X(2000) Null');
            $content .= "<p>Added column xar_info to table $blocktypestable</p>";
        } else {
            $content .= "<p>Table $blocktypestable already has a xar_info column</p>";
        }

        // Ensure the module and type columns are the correct length.
        $data = 'xar_type C(64) NotNull DEFAULT \'\',
        xar_module C(64) NotNull DEFAULT \'\'';
        $result = $datadict->changeTable($blocktypestable, $data);
        $content .= "<p>Table $blocktypestable xar_module and xar_type columns are up-to-date</p>";

        // Drop index i_xar_block_types and create unique compound index
        // i_xar_block_types2 on xar_module and xar_type.
        $indexes = $datadict->getIndexes($blocktypestable);
        $indexname = 'i_' . xarDBGetSiteTablePrefix() . '_block_types';
        if (isset($indexes[$indexname])) {
            $result = $datadict->dropIndex($indexname, $blocktypestable);
            $content .= "Dropped index $indexname from table $blocktypestable<br/>";
        }
        $indexname .= '2';
        if (!isset($indexes[$indexname])) {
            $result = $datadict->createIndex($indexname, $blocktypestable, 'xar_module,xar_type', array('UNIQUE'));
            $content .= "<p>Created unique index $indexname on table $blocktypestable</p>";
        }
    }

    // Add the syndicate block type and syndicate block for RSS display.
    $content .= "<p><strong>Checking Installed Blocks</strong></p>";

    $upgrade['syndicate'] = xarModAPIFunc(
        'blocks', 'admin', 'block_type_exists',
        array(
            'modName'      => 'themes',
            'blockType'    => 'syndicate'
        )
    );
    if ($upgrade['syndicate']) {
        $content .= "Syndicate block exists, attempting to remove... ";
        $blockGroupsTable = xarDBGetSiteTablePrefix() . '_block_groups';
        // Register blocks
        if (!xarModAPIFunc('blocks',
                           'admin',
                           'unregister_block_type',
                           array('modName'  => 'themes',
                                 'blockType'=> 'syndicate'))) return;

        $query = "SELECT    xar_id as id
                  FROM      $blockGroupsTable
                  WHERE     xar_name = 'syndicate'";
        // Check for db errors
        $result =& $dbconn->Execute($query);
        if (!$result) return;

        // Freak if we don't get one and only one result
        if ($result->PO_RecordCount() != 1) {
            $msg = xarML("Group 'syndicate' not found.");
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return;
        }
        list ($syndicateBlockGroup) = $result->fields;
        $result = xarModAPIFunc('blocks', 'admin', 'delete_group', array('gid' => $syndicateBlockGroup));

        if (!$result){
            $content .= "<p>failed</p>";
        } else {
            $content .= "<p>done!</p>";
        }
    } else {
        $content .= "<p>Syndicate block type does not exist, moving to next check. </p>";
    }

    // Set any empty modvars.
    $content .= "<p><strong>Checking Module and Config Variables</strong></p>";

    /* Bug 2204 - the mod var roles - admin is more than likely set in 99.9 percent installs
                  since it was introduced around the beginning of 2004. Let's check it's set,
                  and use that, else check for a new name. If the new name in that rare case
                  is not Admin, then we'll have to display message to check and set as such first.
    */
  $realadmin = xarModGetVar('roles','admin');

    if (!isset($realadmin) || empty($realadmin)) {
        $admin = xarUFindRole('Admin');
        if (!isset($admin)) $admin = xarFindRole('Admin');
        if (!isset($admin)) {
            $content .= "<h2 style=\"color:red; font-weigh:bold;\">WARNING!</h2><p>Your installation has a missing roles variable.</p>";
            $content .= "<p>Please change your administrator username to 'Admin' and re-run upgrade.php</p>
                  <p>You can change it back once your site is upgraded.</p>";

            $content .= "<p>REMEMBER! Don't forget to re-run upgrade.php</p>";
            //CatchOutput();
           $thisdata['content']=$content;
           $thisdata['finishearly']=1;
            return $thisdata;
        }
    } else {

        $thisadmin= xarUserGetVar('uname', $realadmin);
         $admin = xarUFindRole($thisadmin);
    }


    $role = xarFindRole('Everybody');

    /* Bug 2204 - this var is not reliable for admin name
       if (!isset($admin)) $admin = xarFindRole(xarModGetVar('mail','adminname'));
    */
    $modvars[] = array(array('name'    =>  'hidecore',
                             'module'  =>  'themes',
                             'set'     =>  0),
                       array('name'    =>  'selstyle',
                             'module'  =>  'themes',
                             'set'     =>  'plain'),
                       array('name'    =>  'rssxml',
                             'module'  =>  'themes',
                             'set'     =>  '<?xml version="1.0" encoding="utf-8"?>'),
                       array('name'    =>  'selfilter',
                             'module'  =>  'themes',
                             'set'     =>  'XARMOD_STATE_ANY'),
                       array('name'    =>  'selsort',
                             'module'  =>  'themes',
                             'set'     =>  'namedesc'),
                       array('name'    =>  'SiteTitleSeparator',
                             'module'  =>  'themes',
                             'set'     =>  ' :: '),
                       array('name'    =>  'SiteTitleOrder',
                             'module'  =>  'themes',
                             'set'     =>  'default'),
                       array('name'    =>  'SiteFooter',
                             'module'  =>  'themes',
                             'set'     =>  '<a href="http://www.xaraya.com"><img src="modules/base/xarimages/xaraya.gif" alt="Powered by Xaraya" class="xar-noborder" /></a>'),
                       array('name'    =>  'everybody',
                             'module'  =>  'roles',
                             'set'     =>  $role->getID()),
                       array('name'    =>  'allowregistration',
                             'module'  =>  'roles',
                             'set'     =>  1),
                       array('name'    =>  'ShowPHPCommentBlockInTemplates',
                             'module'  =>  'themes',
                             'set'     =>  0),
                       array('name'    =>  'ShowTemplates',
                             'module'  =>  'themes',
                             'set'     =>  0),
                       array('name'    =>  'CollapsedBranches',
                             'module'  =>  'comments',
                             'set'     =>  serialize(array())),
                       array('name'    =>  'expertlist',
                             'module'  =>  'modules',
                             'set'     =>  0),
                       array('name'    =>  'lockdata',
                             'module'  =>  'roles',
                             'set'     =>  serialize(array('roles' => array( array('uid' => 4,
                                                  'name' => 'Administrators',
                                                  'notify' => TRUE)
                                           ),
                                          'message' => '',
                                          'locked' => 0,
                                          'notifymsg' => ''))),
                       array('name'    =>  'askwelcomeemail',
                             'module'  =>  'roles',
                             'set'     =>  1),
                       array('name'    =>  'askvalidationemail',
                             'module'  =>  'roles',
                             'set'     =>  1),
                       array('name'    =>  'askdeactivationemail',
                             'module'  =>  'roles',
                             'set'     =>  1),
                       array('name'    =>  'askpendingemail',
                             'module'  =>  'roles',
                             'set'     =>  1),
                       array('name'    =>  'askpasswordemail',
                             'module'  =>  'roles',
                             'set'     =>  1),
                       array('name'    =>  'admin',
                             'module'  =>  'roles',
                             'set'     =>  $admin->getID()),
                       array('name'    =>  'uniqueemail',
                             'module'  =>  'roles',
                             'set'     =>  true),
                       array('name'    =>  'rolesdisplay',
                             'module'  =>  'roles',
                             'set'     =>  'tabbed'),
                       array('name'    =>  'showrealms',
                             'module'  =>  'privileges',
                             'set'     =>  0),
                       array('name'    =>  'inheritdeny',
                             'module'  =>  'privileges',
                             'set'     =>  true),
                       array('name'    =>  'tester',
                             'module'  =>  'privileges',
                             'set'     =>  0),
                       array('name'    =>  'test',
                             'module'  =>  'privileges',
                             'set'     =>  false),
                       array('name'    =>  'testdeny',
                             'module'  =>  'privileges',
                             'set'     =>  false),
                       array('name'    =>  'testmask',
                             'module'  =>  'privileges',
                             'set'     =>  'All'),
                       array('name'    =>  'realmvalue',
                             'module'  =>  'privileges',
                             'set'     =>  'none'),
                       array('name'    =>  'realmcomparison',
                             'module'  =>  'privileges',
                             'set'     =>  'exact'),
                       array('name'    =>  'suppresssending',
                             'module'  =>  'mail',
                             'set'     =>  'false'),
                       array('name'    =>  'redirectsending',
                             'module'  =>  'mail',
                             'set'     =>  'exact'),
                       array('name'    =>  'redirectaddress',
                             'module'  =>  'mail',
                             'set'     =>  ''),
                          );

    foreach($modvars as $modvar){
        foreach($modvar as $var){
            $currentvar = xarModGetVar("$var[module]", "$var[name]");
            if (isset($currentvar)){
                if (isset($var['override'])) {
                    xarModSetVar($var['module'], $var['name'], $var['set']);
                    $content .= "<p>$var[module] -> $var[name] has been overridden, proceeding to next check</p>";
                }
                else $content .= "<p>$var[module] -> $var[name] is set, proceeding to next check</p>";
            } else {
                xarModSetVar($var['module'], $var['name'], $var['set']);
                $content .= "<p>$var[module] -> $var[name] empty, attempting to set.... done!</p>";
            }
        }
    }

// TODO: save modified email templates from module variables to var/messages !

    // Delete any empty modvars.
    $delmodvars[] = array(array('name'    =>  'showtacs',
                                'module'  =>  'roles'),
                          array('name'    =>  'confirmationtitle',
                                'module'  =>  'roles'),
                          array('name'    =>  'confirmationemail',
                                'module'  =>  'roles'),
                          array('name'    =>  'remindertitle',
                                'module'  =>  'roles'),
                          array('name'    =>  'reminderemail',
                                'module'  =>  'roles'),
                          array('name'    =>  'validationtitle',
                                'module'  =>  'roles'),
                          array('name'    =>  'validationemail',
                                'module'  =>  'roles'),
                          array('name'    =>  'deactivationtitle',
                                'module'  =>  'roles'),
                          array('name'    =>  'deactivationemail',
                                'module'  =>  'roles'),
                          array('name'    =>  'pendingtitle',
                                'module'  =>  'roles'),
                          array('name'    =>  'pendingemail',
                                'module'  =>  'roles'),
                          array('name'    =>  'passwordtitle',
                                'module'  =>  'roles'),
                          array('name'    =>  'passwordemail',
                                'module'  =>  'roles'),
                         );

    foreach($delmodvars as $delmodvar){
        foreach($delmodvar as $var){
            $currentvar = xarModGetVar("$var[module]", "$var[name]");
            if (!isset($currentvar)){
                $content .= "<p>$var[module] -> $var[name] is deleted, proceeding to next check</p>";
            } else {
                xarModDelVar($var['module'], $var['name']);
                $content .= "<p>$var[module] -> $var[name] has value, attempting to delete.... done!</p>";
            }
        }
    }

    // Set Config Vars
    $roleanon = xarFindRole('Anonymous');
    $configvars[] = array(array('name'    =>  'Site.User.AnonymousUID',
                                'set'     =>  $roleanon->getID()),
                          array('name'    =>  'System.Core.VersionNum',
                                'set'     =>  XARCORE_VERSION_NUM));

    foreach($configvars as $configvar){
        foreach($configvar as $var){
            $currentvar = xarConfigGetVar("$var[name]");
            if ($currentvar == $var['set']){
                $content .= "<p>$var[name] is set, proceeding to next check</p>";
            } else {
                xarConfigSetVar($var['name'], $var['set']);
                $content .= "<p>$var[name] incorrect, attempting to set.... done!</p>";
            }
        }
    }

    $timezone = xarConfigGetVar('Site.Core.TimeZone');
    if (!isset($timezone) || substr($timezone,0,2) == 'US') {
        xarConfigSetVar('Site.Core.TimeZone', '');
        $content .= "<p>Site.Core.TimeZone incorrect, attempting to set.... done!</p>";
    }
    $offset = xarConfigGetVar('Site.MLS.DefaultTimeOffset');
    if (!isset($offset)) {
        xarConfigSetVar('Site.MLS.DefaultTimeOffset', 0);
        $content .= "<p>Site.MLS.DefaultTimeOffset incorrect, attempting to set.... done!</p>";
    }
    $cookiename = xarConfigGetVar('Site.Session.CookieName');
    if (!isset($cookiename)) {
        xarConfigSetVar('Site.Session.CookieName', '');
        $content .= "<p>Site.Session.CookieName incorrect, attempting to set.... done!</p>";
    }
    $cookiepath = xarConfigGetVar('Site.Session.CookiePath');
    if (!isset($cookiepath)) {
        xarConfigSetVar('Site.Session.CookiePath', '');
        $content .= "<p>Site.Session.CookiePath incorrect, attempting to set.... done!</p>";
    }
    $cookiedomain = xarConfigGetVar('Site.Session.CookieDomain');
    if (!isset($cookiedomain)) {
        xarConfigSetVar('Site.Session.CookieDomain', '');
        $content .= "<p>Site.Session.CookieDomain incorrect, attempting to set.... done!</p>";
    }
    $referercheck = xarConfigGetVar('Site.Session.RefererCheck');
    if (!isset($referercheck)) {
        xarConfigSetVar('Site.Session.RefererCheck', '');
        $content .= "<p>Site.Session.RefererCheck incorrect, attempting to set.... done!</p>";
    }

    // Check the installed roles
    $content .= "<p><strong>Checking Role Structure</strong></p>";

    $upgrade['myself'] = xarModAPIFunc('roles',
                                       'user',
                                       'get',
                                       array('uname' => 'myself'));
    if (!$upgrade['myself']) {
        $content .= "Myself role does not exist, attempting to create... ";
        //This creates the new Myself role and makes it a child of Everybody
        $result = xarMakeUser('Myself','myself','myself@xaraya.com','password');
        $result .= xarMakeRoleMemberByName('Myself','Everybody');
        if (!$result){
            $content .= "<p>failed</p>";
        } else {
            $content .= "<p>done!</p>";
        }
    } else {
        $content .= "<p>Myself role has been created previously, moving to next check. </p>";
    }

    $upgrade['roles_masks'] = xarMaskExists('AttachRole',$module='roles');
    if (!$upgrade['roles_masks']) {
        $content .= "<p>AttachRole, RemoveRole masks do not exist, attempting to create... done! </p>";
        xarRegisterMask('AttachRole','All','roles','Relation','All','ACCESS_ADD');
        xarRegisterMask('RemoveRole','All','roles','Relation','All','ACCESS_DELETE');
    } else {
        $content .= "<p>AttachRole, RemoveRole masks have been created previously, moving to next check. </p>";
    }

    // Check the installed privs and masks.
    $content .= "<p><strong>Checking Privilege Structure</strong></p>";

    $upgrade['article_masks'] = xarMaskExists('ReadArticlesBlock',$module='articles');
    if (!$upgrade['article_masks']) {
        $content .= "<p>Articles Masks do not exist, attempting to create... done! </p>";
            // Remove Masks and Instances
            xarRemoveMasks('articles');
            xarRemoveInstances('articles');
            $instances = array(
                               array('header' => 'external', // this keyword indicates an external "wizard"
                                     'query'  => xarModURL('articles', 'admin', 'privileges'),
                                     'limit'  => 0
                                    )
                            );
            xarDefineInstance('articles', 'Article', $instances);
            $xartable =& xarDBGetTables();
            $query = "SELECT DISTINCT instances.xar_title FROM $xartable[block_instances] as instances LEFT JOIN $xartable[block_types] as btypes ON btypes.xar_id = instances.xar_type_id WHERE xar_module = 'articles'";
            $instances = array(
                                array('header' => 'Article Block Title:',
                                        'query' => $query,
                                        'limit' => 20
                                    )
                            );
            xarDefineInstance('articles','Block',$instances);

            xarRegisterMask('ViewArticles','All','articles','Article','All','ACCESS_OVERVIEW');
            xarRegisterMask('ReadArticles','All','articles','Article','All','ACCESS_READ');
            xarRegisterMask('SubmitArticles','All','articles','Article','All','ACCESS_COMMENT');
            xarRegisterMask('EditArticles','All','articles','Article','All','ACCESS_EDIT');
            xarRegisterMask('DeleteArticles','All','articles','Article','All','ACCESS_DELETE');
            xarRegisterMask('AdminArticles','All','articles','Article','All','ACCESS_ADMIN');
            xarRegisterMask('ReadArticlesBlock','All','articles','Block','All','ACCESS_READ');
    } else {
        $content .= "<p>Articles Masks have been created previously, moving to next check. </p>";
    }

    $upgrade['category_masks'] = xarMaskExists('ViewCategoryLink',$module='categories');
    if (!$upgrade['category_masks']) {
        $content .= "<p>Category Masks do not exist, attempting to create... done!</p>";
            // Remove Masks and Instances
        $instances = array(
                           array('header' => 'external', // this keyword indicates an external "wizard"
                                 'query'  => xarModURL('categories', 'admin', 'privileges'),
                                 'limit'  => 0
                                )
                          );
        xarDefineInstance('categories', 'Link', $instances);
        xarRegisterMask('ViewCategoryLink','All','categories','Link','All:All:All:All','ACCESS_OVERVIEW');
        xarRegisterMask('SubmitCategoryLink','All','categories','Link','All:All:All:All','ACCESS_COMMENT');
        xarRegisterMask('EditCategoryLink','All','categories','Link','All:All:All:All','ACCESS_EDIT');
        xarRegisterMask('DeleteCategoryLink','All','categories','Link','All:All:All:All','ACCESS_DELETE');
        xarRegisterMask('AdminCategories','All','categories','Category','All:All','ACCESS_ADMIN');
    } else {
        $content .= "<p>Category Masks have been created previously, moving to next check. </p>";
    }

    $upgrade['priv_masks'] = xarMaskExists('AssignPrivilege',$module='privileges');
    if (!$upgrade['priv_masks']) {
        $content .= "<p>Some Privileges Masks do not exist, attempting to create... done! </p>";

        // create a couple of new masks
        //xarRegisterMask('ViewPanel','All','adminpanels','All','All','ACCESS_OVERVIEW');
        xarRegisterMask('AssignPrivilege','All','privileges','All','All','ACCESS_ADD');
        xarRegisterMask('DeassignPrivilege','All','privileges','All','All','ACCESS_DELETE');
    } else {
        $content .= "<p>Privileges Masks have been created previously, moving to next check. </p>";
    }

    $upgrade['priv_masks'] = xarMaskExists('pnLegacyMask',$module='All');
    if (!$upgrade['priv_masks']) {
        $content .= "<p>pnLegacy Masks do not exist, attempting to create... done!</p>";

        // create a couple of new masks
        xarRegisterMask('pnLegacyMask','All','All','All','All','ACCESS_NONE');
    } else {
        $content .= "<p>pnLegacy Masks have been created previously, moving to next check.</p>";
    }

    $upgrade['priv_masks'] = xarMaskExists('ViewPrivileges','privileges','Realm');
    if (!$upgrade['priv_masks']) {
        $content .= "<p>Privileges realm Masks do not exist, attempting to create... done! </p>";

        // create a couple of new masks
        xarRegisterMask('ViewPrivileges','All','privileges','Realm','All','ACCESS_OVERVIEW');
        xarRegisterMask('ReadPrivilege','All','privileges','Realm','All','ACCESS_READ');
        xarRegisterMask('EditPrivilege','All','privileges','Realm','All','ACCESS_EDIT');
        xarRegisterMask('AddPrivilegem','All','privileges','Realm','All','ACCESS_ADD');
        xarRegisterMask('DeletePrivilege','All','privileges','Realm','All','ACCESS_DELETE');
    } else {
        $content .= "<p>Privileges realm masks have been created previously, moving to next check. </p>";
    }

    $upgrade['priv_locks'] = xarPrivExists('GeneralLock');
    if (!$upgrade['priv_locks']) {
        $content .= "<p>Privileges Locks do not exist, attempting to create... done! </p>";

        // This creates the new lock privileges and assigns them to the relevant roles
        xarRegisterPrivilege('GeneralLock','All','empty','All','All','ACCESS_NONE',xarML('A container privilege for denying access to certain roles'));
        xarRegisterPrivilege('LockMyself','All','roles','Roles','Myself','ACCESS_NONE',xarML('Deny access to Myself role'));
        xarRegisterPrivilege('LockEverybody','All','roles','Roles','Everybody','ACCESS_NONE',xarML('Deny access to Everybody role'));
        xarRegisterPrivilege('LockAnonymous','All','roles','Roles','Anonymous','ACCESS_NONE',xarML('Deny access to Anonymous role'));
        xarRegisterPrivilege('LockAdministrators','All','roles','Roles','Administrators','ACCESS_NONE',xarML('Deny access to Administrators role'));
        xarRegisterPrivilege('LockAdministration','All','privileges','Privileges','Administration','ACCESS_NONE',xarML('Deny access to Administration privilege'));
        xarRegisterPrivilege('LockGeneralLock','All','privileges','Privileges','GeneralLock','ACCESS_NONE',xarML('Deny access to GeneralLock privilege'));
        xarMakePrivilegeRoot('GeneralLock');
        xarMakePrivilegeMember('LockMyself','GeneralLock');
        xarMakePrivilegeMember('LockEverybody','GeneralLock');
        xarMakePrivilegeMember('LockAnonymous','GeneralLock');
        xarMakePrivilegeMember('LockAdministrators','GeneralLock');
        xarMakePrivilegeMember('LockAdministration','GeneralLock');
        xarMakePrivilegeMember('LockGeneralLock','GeneralLock');
        xarAssignPrivilege('Administration','Administrators');
        xarAssignPrivilege('GeneralLock','Everybody');
        xarAssignPrivilege('GeneralLock','Administrators');
        xarAssignPrivilege('GeneralLock','Users');

    } else {
        $content .= "<p>Privileges Locks have been created previously, moving to next check. </p>";
    }

    $upgrade['priv_masks'] = xarMaskExists('AdminPrivilege',$module='privileges');
    if (!$upgrade['priv_masks']) {
        $content .= "<p>Some Privileges Masks do not exist, attempting to create... done! </p>";

        // create a couple of new masks
        xarRegisterMask('AdminPrivilege','All','privileges','All','All','ACCESS_ADMIN');
    } else {
        $content .= "<p>0.9.11 Privileges Masks have been created previously, moving to next check.</p>";
    }

    //Move this mask from privileges module
    xarUnregisterMask('AssignRole');

    // Check the installed privs and masks.
    $content .= "<p><strong>Checking Time / Date Structure</strong></p>";

    include 'includes/xarDate.php';
    $dbconn =& xarDBGetConn();
    $sitePrefix = xarDBGetSiteTablePrefix();
    $rolestable = $sitePrefix . '_roles';

    $query = " SELECT xar_uid, xar_date_reg FROM $rolestable";
    $result = &$dbconn->Execute($query);
    if (!$result) return;

    while (!$result->EOF) {
        list($uid,$datereg) = $result->fields;
        $thisdate = new xarDate();
        if(!is_numeric($datereg)) {
            $thisdate->DBtoTS($datereg);
            $datereg = $thisdate->getTimestamp();
            $query = "UPDATE $rolestable SET xar_date_reg = $datereg WHERE xar_uid = $uid";
            if(!$dbconn->Execute($query)) return;
        }
        $result->MoveNext();
    }

    $content .= "<p>Time / Date structure verified in Roles. </p> ";

    // Check the installed privs and masks.
    $content .= "<p><strong>Update Xaraya Installer theme name</strong></p>";
    $dbconn =& xarDBGetConn();
    $sitePrefix = xarDBGetSiteTablePrefix();
    $themestable = $sitePrefix . '_themes';
    $query = "SELECT xar_id FROM $themestable WHERE xar_name = 'Xaraya Installer'";
    $result =& $dbconn->Execute($query);
    if ($result->EOF){
        $content .= "<p>Theme name update not required.</p>";
    } else {
        $query2 = "UPDATE $themestable SET xar_name = 'Xaraya_Installer' WHERE xar_name = 'Xaraya Installer'";
        // Check for db errors
        $result2 =& $dbconn->Execute($query2);
        if (!$result2){
            $content .= "<p>Theme name update failed</p>";
        } else {
            $content .= "<p>Theme name updated.</p>";
        }
    }

    // Bug 1716 module states table
    {
        $module_states_table = $sitePrefix . '_module_states';
        $content .= "<p><strong>Upgrade $module_states_table table</strong></p>";

        // TODO: use adodb transactions to ensure atomicity?
        // The changes for bug 1716:
        // - add xar_id as primary key
        // - make index on xar_regid unique

        $dbconn =& xarDBGetConn();
        $datadict =& xarDBNewDataDict($dbconn, 'CREATE');

        // Upgrade the module states table.
        // Get column definitions for module states table.
        $columns = $datadict->getColumns($module_states_table);
        // Do we have a xar_id column?
        $modules_column_found = false;
        foreach($columns as $column) {
            if ($column->name == 'xar_id') {
                $modules_column_found = true;
                break;
            }
        }
        // Upgrade the table (xar_module_states) if the name column is not found.
        if (!$modules_column_found) {
            // Create the column.
            $result = $datadict->addColumn($module_states_table, 'xar_id I AUTO PRIMARY');
            if ($result) {
                $content .= "<p>Added column xar_id to table $module_states_table</p>";
            } else {
                $content .= "<p>Failed to add column xar_id to table $module_states_table</p>";
            }

            // Bug #1971 - Have to use GenId to create values for xar_id on
            // existing rows or the create unique index will fail
            // TODO: check this: can PGSQL do this? Can it create a primary key on a table
            // with existing rows, when the primary key is, by definition, NOT NULL?
            // MySQL will automatically prefill the column with autoincrement values, but I
            // doubt PGSQL will.
            $query = "SELECT xar_regid, xar_state
                      FROM $module_states_table
                      WHERE xar_id IS NULL";
            $result = &$dbconn->Execute($query);
            if ($result) {
                // Get items from result array
                while (!$result->EOF) {
                    list ($regid, $state) = $result->fields;
                    $seqId = $dbconn->GenId($module_states_table);
                    $query = "UPDATE $module_states_table
                              SET xar_id = $seqId
                              WHERE xar_regid = $regid
                              AND xar_state = $state";
                    $updresult = &$dbconn->Execute($query);
                    if (!$updresult) {
                        $content .= "<p>FAILED to update the $module_states_table table ID column</p>";
                    }

                    $result->MoveNext();
                }
                // Close result set
                $result->Close();
            }

        } else {
            $content .= "<p>Table $module_states_table does not require updating</p>";
        }

        // Drop index i_xar_module_states_regid and create unique index
        // i_xar_module_states_regid2 on xar_regid.
        // By renaming the index, we know that it has been changed.
        $indexes = $datadict->getIndexes($module_states_table);
        $indexname = 'i_' . xarDBGetSiteTablePrefix() . '_module_states_regid';
        if (isset($indexes[$indexname])) {
            $result = $datadict->dropIndex($indexname, $module_states_table);
            if ($result) {
                $content .= "<p>Dropped non-unique index $indexname from table $module_states_table</p>";
            } else {
                $content .= "<p>Failed to drop non-unique index $indexname from table $module_states_table</p>";
            }
        }

        $indexname .= '2';
        if (!isset($indexes[$indexname])) {
            // We need to remove duplicate regids before creating a unique index on that column.
            $query = "select min(xar_id), xar_regid from $module_states_table group by xar_regid having count(xar_regid) > 1";
            $result = &$dbconn->Execute($query);
            if ($result) {
                // Get items from result array
                while (!$result->EOF) {
                    list ($xar_min_id, $xar_regid) = $result->fields;
                    $query2 = "delete from $module_states_table where xar_id <> $xar_min_id and xar_regid = $xar_regid";
                    $result2 = &$dbconn->Execute($query2);
                    $result2->close();
                    $content .= "<p>Deleted duplicate module state rows (xar_regid=$xar_regid, leaving xar_id=$xar_min_id)</p>";

                    $result->MoveNext();
                }
            }

            // Create the unique index.
            $result = $datadict->createIndex($indexname, $module_states_table, 'xar_regid', array('UNIQUE'));
            if ($result) {
                $content .= "<p>Created unique index $indexname on $module_states_table.regid</p>";
            } else {
                $content .= "<p>Failed to create unique index $indexname on $module_states_table.regid</p>";
            }
        }
    }

    // If output caching if enabled, check to see if the table xar_cache_blocks exists.
    // If it does not exist, disable output caching so that xarcachemanager can be upgraded.
    $content .= "<p><strong>Checking for and adding the xarCache block cache table</strong></p>";

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $cacheblockstable = xarDBGetSiteTablePrefix() . '_cache_blocks';
    $datadict =& xarDBNewDataDict($dbconn, 'ALTERTABLE');
    $flds = "
        xar_bid             I           NotNull DEFAULT 0,
        xar_nocache         L           NotNull DEFAULT 0,
        xar_page            L           NotNull DEFAULT 0,
        xar_user            L           NotNull DEFAULT 0,
        xar_expire          I           Null
    ";
    // Create or alter the table as necessary.
    $result = $datadict->changeTable($cacheblockstable, $flds);

    if (!$result) {return;}

    // Create a unique key on the xar_bid collumn
    /* $result = $datadict->createIndex('i_' . xarDBGetSiteTablePrefix() . '_cache_blocks_1',                                     $cacheblockstable,
                                     'xar_bid',
                                     array('UNIQUE'));
    $content .= "<p>...done.</p>";
    */

    // Bug 630, let's throw the reminder back up after upgrade.

    if (!xarModAPIFunc('blocks', 'user', 'get', array('name' => 'reminder'))) {
        $varshtml['html_content'] = 'Please delete install.php and upgrade.php from your webroot.';
        $varshtml['expire'] = time() + 7*24*60*60; // 7 days

        $htmlBlockType = xarModAPIFunc(
            'blocks', 'user', 'getblocktype',
            array('module' => 'base', 'type' => 'html')
        );

        if (empty($htmlBlockType) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
            return;
        }

        // Get the first available group ID, and assume that will be
        // visible to the administrator.
        $allgroups = xarModAPIFunc(
            'blocks', 'user', 'getallgroups',
            array('order' => 'id')
        );
        $topgroup = array_shift($allgroups);

        if (!xarModAPIFunc(
            'blocks', 'admin', 'create_instance',
            array(
                'title'    => 'Reminder',
                'name'     => 'reminder',
                'content'  => $varshtml,
                'type'     => $htmlBlockType['tid'],
                'groups'   => array(array('gid' => $topgroup['gid'])),
                'state'    => 2))) {
            return;
        }
    } // End bug 630

    // after 0911, make sure CSS class lib is deployed and css tags are registered
    $content .= "<p><strong>Making sure CSS tags are registered</strong></p>";
    if(!xarModAPIFunc('themes', 'css', 'registercsstags')) {
        $content .= "<p>FAILED to register CSS tags</p>";
    } else {
        $content .= "<p>CSS tags registered successfully, css subsystem is ready to be deployed.</p>";
    }

    // Bug 3164, store locale in ModUSerVar
    xarModSetVar('roles', 'locale', '');

  $content .= "<p><strong>Checking <strong>include/properties</strong> directory for moved DD properties</strong></p>";
    //From 1.0.0rc2 propsinplace was merged and dd propertie began to move to respective modules
    //Check they don't still exisit in the includes directory  bug 4371
    // set the array of properties that have moved
    $ddmoved=array(
        array('Dynamic_AIM_Property.php',1,'Roles'),
        array('Dynamic_Affero_Property.php',1,'Roles'),
        array('Dynamic_Array_Property.php',1,'Base'),
        array('Dynamic_Categories_Property.php',0,'Categories'),
        array('Dynamic_CheckboxList_Property.php',1,'Base'),
        array('Dynamic_CheckboxMask_Property.php',1,'Base'),
        array('Dynamic_Checkbox_Property.php',1,'Base'),
        array('Dynamic_Combo_Property.php',1,'Base'),
        array('Dynamic_CommentsNumberOf_Property.php',0,'Comments'),
        array('Dynamic_Comments_Property.php',0,'Comments'),
        array('Dynamic_CountryList_Property.php',1,'Base'),
        array('Dynamic_DateFormat_Property.php',1,'Base'),
        array('Dynamic_Email_Property.php',1,'Roles'),
        array('Dynamic_ExtendedDate_Property.php',1,'Base'),
        array('Dynamic_FileUpload_Property.php',1,'Roles'),
        array('Dynamic_FloatBox_Property.php',1,'Roles'),
        array('Dynamic_HTMLArea_Property.php',0,'HTMLArea'),
        array('Dynamic_HTMLPage_Property.php',1,'Base'),
        array('Dynamic_HitCount_Property.php',0,'HitCount'),
        array('Dynamic_ICQ_Property.php',1,'Roles'),
        array('Dynamic_ImageList_Property.php',1,'Roles'),
        array('Dynamic_Image_Property.php',1,'Roles'),
        array('Dynamic_LanguageList_Property.php',1,'Base'),
        array('Dynamic_LogLevel_Property.php',0,'Logconfig'),
        array('Dynamic_MSN_Property.php',1,'Roles'),
        array('Dynamic_MultiSelect_Property.php',1,'Base'),
        array('Dynamic_NumberBox_Property.php',1,'Base'),
        array('Dynamic_NumberList_Property.php',1,'Base'),
        array('Dynamic_PassBox_Property.php',1,'Base'),
        array('Dynamic_PayPalCart_Property.php',0,'Paypalsetup'),
        array('Dynamic_PayPalDonate_Property.php',0,'Paypalsetup'),
        array('Dynamic_PayPalNow_Property.php',0,'Paypalsetup'),
        array('Dynamic_PayPalSubscription_Property.php',0,'Paypalsetup'),
        array('Dynamic_RadioButtons_Property.php',1,'Base'),
        array('Dynamic_Rating_Property.php',0,'Ratings'),
        array('Dynamic_Select_Property.php',0,'Base'),
        array('Dynamic_SendToFriend_Property.php',0,'Recommend'),
        array('Dynamic_StateList_Property.php',1,'Base'),
        array('Dynamic_StaticText_Property.php',1,'Base'),
        array('Dynamic_Status_Property.php',0,'Articles'),
        array('Dynamic_TextArea_Property.php',1,'Base'),
        array('Dynamic_TextBox_Property.php',1,'Base'),
        array('Dynamic_TextUpload_Property.php',1,'Base'),
        array('Dynamic_TinyMCE_Property.php',0,'TinyMCE'),
        array('Dynamic_URLIcon_Property.php',1,'Base'),
        array('Dynamic_URLTitle_Property.php',1,'Base'),
        array('Dynamic_URL_Property.php',1,'Roles'),
        array('Dynamic_Upload_Property.php',0,'Uploads'),
        array('Dynamic_Yahoo_Property.php',1,'Roles'),
        array('Dynamic_Calendar_Property.php',1,'Base'),
        array('Dynamic_TColorPicker_Property.php',1,'Base'),
        array('Dynamic_TimeZone_Property.php',1,'Base'),
        array('Dynamic_Module_Property.php',1,'Modules'),
        array('Dynamic_GroupList_Property.php',1,'Roles'),
        array('Dynamic_UserList_Property.php',1,'Roles'),
        array('Dynamic_Username_Property.php',1,'Roles'),
        array('Dynamic_DataSource_Property.php',1,'DynamicData'),
        array('Dynamic_FieldStatus_Property.php',1,'DynamicData'),
        array('Dynamic_FieldType_Property.php',1,'DynamicData'),
        array('Dynamic_Hidden_Property.php',1,'Base'),
        array('Dynamic_ItemID_Property.php',1,'DynamicData'),
        array('Dynamic_ItemType_Property.php',1,'DynamicData'),
        array('Dynamic_Object_Property.php',1,'DynamicData'),
        array('Dynamic_SubForm_Property.php',1,'DynamicData'),
        array('Dynamic_Validation_Property.php',1,'DynamicData')
    );
    //set the array to hold properties that have not moved and should do!
    $ddtomove=array();

    //Check the files in the includes/properties dir against the initial array
    $oldpropdir='includes/properties';
    $var = is_dir($oldpropdir);
    $handle=opendir($oldpropdir);
    $skip_array = array('.','..','SCCS','index.htm','index.html');

    if ($var) {
             while (false !== ($file = readdir($handle))) {
                  // check the  dd file array and add to the ddtomove array if the file exists
                  if (!in_array($file,$skip_array))  {

                     foreach ($ddmoved as $key=>$propname) {
                          if ($file == $ddmoved[$key][0]){
                            $ddtomove[]=$ddmoved[$key];
                           }
                    }
                  }
            }
            closedir($handle);
    }
    if (is_array($ddtomove) && !empty($ddtomove[0])){

        $content .= "<h3 style=\"font:size:large;color:red; font-weigh:bold;\">WARNING!</h3><p>The following DD property files exist in your Xaraya <strong>includes/properties</strong> directory.</p>";
        $content .= "<p>Please delete each of the following and ONLY the following from your <strong>includes/properties</strong> directory as they have now been moved to the relevant module in core, or the 3rd party module concerned.</p>";
        $content .= "<p>Once you have removed the duplicated property files from <strong>includes/properties</strong> please re-run upgrade.php.</p>";

        foreach ($ddtomove as $ddkey=>$ddpropname) {
             if ($ddtomove[$ddkey][1] == 1) {
                $content .= "<p><strong>".$ddtomove[$ddkey][0]."</strong> exits. Please remove it from includes/properties.</p>";
             }else{
                $content .= "<p><strong>".$ddtomove[$ddkey][0]."</strong> is a ".$ddtomove[$ddkey][2]." module property. Please remove it from includes/properties. IF you have ".$ddtomove[$ddkey][2]." installed, check you have the property in the <strong>".strtolower($ddtomove[$ddkey][2])."/xarproperties</strong> directory else upgrade your ".$ddtomove[$ddkey][2]." module.</p>";
             }
        }

        $content .= "<p>REMEMBER! Run upgrade.php again when you delete the above properties from the includes/properties directory.</p>";

        unset($ddtomove);
        $thisdata['content']=$content;
        $thisdata['finishearly']=1;
       return $thisdata;
       // return;
     }else{
         $content .= "<p>Done! All properties have been checked and verified for location!</p>";
    }

    $content .= "<p><strong>Updating Roles and Authsystem for changes in User Login and Authentication</strong></p>";

    //TODO: tidy up - look at this and other changes once we finish the refactoring for this and adminpanels
    //Check for allow registration in existing Roles module
    $allowregistration =xarModGetVar('roles','allowregistration');
    if (isset($allowregistration) && ($allowregistration==1)) {
        //We need to tell user about the new Registration module - let's just warn them for now
        if (!xarModIsAvailable('registration')){
            $content .= "<h2 style=\"color:red;\">WARNING!</h2><p>Your setup indicates you allow User Registration on your site.</p>";
            $content .= "<p>Handling of User Registration has changed in this version. Please install and activate the <strong>Registration</strong> module to continue User Registration on your site.</p>";
            $content .= "<p>You should also remove any existing login blocks and install the Registration module Login block if you wish to include a Registration link in the block.</p>";
        }
    }

    //we need to check the login block is the Authsystem login block, not the Roles
    //see if there is an existing roles login blocktype instance
    //As the block is the same we could just change the type id of any login block type.
    $blocktypeTable = $systemPrefix .'_block_types';
    $blockinstanceTable = $systemPrefix .'_block_instances';
    $blockproblem=array();
       //Get the block type id of the existing block type
        $query = "SELECT xar_id,
                         xar_type,
                         xar_module
                         FROM $blocktypeTable
                 WHERE xar_type='login' and xar_module='roles'";
        $result =& $dbconn->Execute($query);
        list($blockid,$blocktype,$module)= $result->fields;
        $blocktype = array('id' => $blockid,
                           'blocktype' => $blocktype,
                           'module'=> $module);

        if (is_array($blocktype) && $blocktype['module']=='roles') {

            $blockid=$blocktype['id'];
            //set the module to authsystem and it can be used for the existing block instance
            $query = "UPDATE $blocktypeTable
                      SET xar_module = 'authsystem'
                      WHERE xar_id=?";
            $bindvars=array($blockid);
            $result =& $dbconn->Execute($query,$bindvars);

        }

    //Authsystem ... we need to put this here as the authsystem upgrade is not happening (fully ...)
    // Define and setup privs
    xarRegisterPrivilege('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');
    xarRegisterPrivilege('ViewAthsystem','All','authsystem','All','All','ACCESS_OVERVIEW');

    xarRegisterMask('ViewLogin','All','authsystem','Block','login:Login:All','ACCESS_OVERVIEW');
    xarRegisterMask('ViewAuthsystemBlocks','All','authsystem','Block','All','ACCESS_OVERVIEW');
    xarRegisterMask('ViewAuthsystem','All','authsystem','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('EditAuthsystem','All','authsystem','All','All','ACCESS_EDIT');
    xarRegisterMask('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');
      // Define Module vars
 	xarModSetVar('authsystem', 'lockouttime', 15);
	xarModSetVar('authsystem', 'lockouttries', 3);
	xarModSetVar('authsystem', 'uselockout', false);
	xarModSetVar('roles', 'defaultauthmodule', xarModGetIDFromName('authsystem'));
	//End of this authsystem info that should be adde din the module upgrade function
      if (count($blockproblem) >0) {
        $content .= "<p><span style=\"color:red;\">WARNING!</span> There was a problem in updating Waiting Content and Adminpanels menu block to Base blocks. Please check!</p>";

     }else {
        $content .= "<p>Done! Roles, authentication and registration checked!</p>";
    }

    $content .= "<p><strong>Removing Adminpanels module - moving functions to other  modules</strong></p>";
   // Move of Adminpanels module overviews modvar to Modules module
    $oldvalue=xarModGetVar('adminpanels','overview');
    if (isset($oldvalue)) {
        xarModSetVar('modules','overview',$oldvalue);
    }
    // Move off Adminpanels dashboard modvar to Themes module
    $oldvalue=xarModGetVar('adminpanels','dashboard');
    if (isset($oldvalue)) {
        xarModSetVar('themes','usedashboard',$oldvalue);
    }
    //dashtemplate will always override admin.xt

    if (isset($oldvalue) && ($oldvalue==1)) {
        //will use admin.xt if present
        xarModSetVar('themes','dashtemplate','admin');
    }else{
        //set it to the new dashboard template
        xarModSetVar('themes','dashtemplate','dashboard');
    }

    $table_name['admin_menu']=$sitePrefix . '_admin_menu';
    $upgrade['admin_menu'] = xarModAPIFunc('installer',
                                                'admin',
                                                'CheckTableExists',
                                                array('table_name' => $table_name['admin_menu']));
    //Let's remove the now unused admin menu table
    if ($upgrade['admin_menu']) {

          $adminmenuTable = $systemPrefix .'_admin_menu';
        $query = xarDBDropTable($adminmenuTable);
        $result = &$dbconn->Execute($query);
     }
    xarRegisterMask('AdminPanel','All','base','All','All','ACCESS_ADMIN');

    //We need to upgrade the blocks, and as the block is the same we could just change the type id of any login.
    $blocktypeTable = $systemPrefix .'_block_types';
    $blockinstanceTable = $systemPrefix .'_block_instances';
    $newblocks=array('waitingcontent','adminmenu');
    $blockproblem=array();
    foreach ($newblocks as $newblock) {
        // We don't need to register new block = just change the existing block

        //Get the ID of the old block type
        $query = "SELECT xar_id,
                         xar_type,
                         xar_module
                         FROM $blocktypeTable
                 WHERE xar_type='".$newblock."' and xar_module='adminpanels'";
        $result =& $dbconn->Execute($query);

        if ($result) {
            list($blockid,$blocktype,$module)= $result->fields;
            //update the module name in the block with that id to 'base'
            $blocktype = array('id' => $blockid,
                           'blocktype' => $blocktype,
                           'module'=> $module);

            if (is_array($blocktype) && $blocktype['module']=='adminpanels') {
               $blockid=$blocktype['id'];
               //set the module to base
               $query = "UPDATE $blocktypeTable
                         SET xar_module = 'base'
                         WHERE xar_id=?";
               $bindvars=array($blockid);
               $result =& $dbconn->Execute($query,$bindvars);


               if (($newblock='waitingcontent') && isset($blockid)) {
               //We need to disable existing hooks and enable new ones - but which :)
               $hookTable = $systemPrefix .'_hooks';
               $query = "UPDATE $hookTable
                         SET xar_smodule = 'base'
                         WHERE xar_action='waitingcontent' AND xar_smodule='adminpanels'";
               }
            }
            //Remove the original block
            if (!xarModAPIFunc('blocks','admin','unregister_block_type',
                       array('modName'  => 'adminpanels',
                             'blockType'=> $newblock))) {
              $blockproblem[]=1;
            }

        }
      }

     // Delete any module variables
     //Problem if adminpanels does not exist
      xarModDelAllVars('adminpanels');
      // Remove Masks and Instances
      xarRemoveMasks('adminpanels');
      xarRemoveInstances('adminpanels');

    if (count($blockproblem) >0) {
        $content .= "<p><span style=\"color:red;\">WARNING!</span> There was a problem in updating Waiting Content and Adminpanels menu block to Base blocks. Please check!</p>";
    }else {
        $content .= "<p>Done! Waiting content and Admin Menu block updated in Base module!</p>";
    }

    $thisdata['content']=$content;
    $thisdata['phase'] = 2;
    $thisdata['phase_label'] = xarML('Step Two');

    return $thisdata;
}
function installer_admin_upgrade3()
{
    $thisdata['xarProduct'] = xarConfigGetVar('System.Core.VersionId');
    $thisdata['xarVersion'] = xarConfigGetVar('System.Core.VersionNum');
    $thisdata['xarRelease'] = xarConfigGetVar('System.Core.VersionSub');
    $content='';
    // Propsinplace scenario, flush the property cache, so on upgrade all proptypes
    // are properly set in the database.
    $content .=  "<p><strong>Flushing the property cache</strong></p>";
    if(!xarModAPIFunc('dynamicdata','admin','importpropertytypes', array('flush' => true))) {
        $content .=  "<p>WARNING: Flushing property cache failed</p>";
    } else {
        $content .=  "<p>Success! Flushing property cache complete</p>";
    }


    $thisdata['content']=$content;
    $thisdata['phase'] = 3;
    $thisdata['phase_label'] = xarML('Step Three');

    return $thisdata;
}
function installer_admin_upgrade4()
{
    $content='';
    $thisdata['xarProduct'] = xarConfigGetVar('System.Core.VersionId');
    $thisdata['xarVersion'] = xarConfigGetVar('System.Core.VersionNum');
    $thisdata['xarRelease'] = xarConfigGetVar('System.Core.VersionSub');
    $thisdata['content']=$content;
    $thisdata['phase'] = 4;
    $thisdata['phase_label'] = xarML('Step Four');

    return $thisdata;
}
?>