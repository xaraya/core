<?php
/**
 * Installer
 *
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

/* Do not allow this script to run if the install script has been removed.
 * This assumes the install.php and index.php are in the same directory.
 * @author Paul Rosania
 * @author Marcel van der Boom <marcel@hsdev.com>
 * @return mixed data array for the template display or output display string if invalid data submitted
 */

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
 * @return array data for the template display
 */
function installer_admin_phase5()
{
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    xarVarSetCached('installer','installing', true);

    // Get arguments
    if (!xarVarFetch('install_database_host','pre:trim:passthru:str',$dbHost)) return;
    if (!xarVarFetch('install_database_name','pre:trim:passthru:str',$dbName,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_username','pre:trim:passthru:str',$dbUname,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_password','pre:trim:passthru:str',$dbPass,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_prefix','pre:trim:passthru:str',$dbPrefix,'xar',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_charset','pre:trim:passthru:str',$dbCharset,'utf8',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_type','str:1:',$dbType)) return;
    if (!xarVarFetch('install_create_database','checkbox',$createDB,false,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('confirmDB','bool',$confirmDB,false,XARVAR_NOT_REQUIRED)) return;

    if ($dbName == '') {
        return xarTpl::module('installer','admin','errors',array('layout' => 'no_database'));
    }

    // allow only a-z 0-9 and _ in table prefix
    if (!preg_match('/^\w*$/',$dbPrefix)) {
        return xarTpl::module('installer','admin','errors',array('layout' => 'bad_character'));
    }
    // Save config data
    $config_args = array('dbHost'    => $dbHost,
                         'dbName'    => $dbName,
                         'dbUname'   => $dbUname,
                         'dbPass'    => $dbPass,
                         'dbPrefix'  => $dbPrefix,
                         'dbType'    => $dbType,
                         'dbCharset' => $dbCharset);
    //  Write the config
    xarInstallAPIFunc('modifyconfig', $config_args);

    $init_args =  array('userName'           => $dbUname,
                        'password'           => $dbPass,
                        'databaseHost'       => $dbHost,
                        'databaseType'       => $dbType,
                        'databaseName'       => $dbName,
                        'databaseCharset'    => $dbCharset,
                        'prefix'             => $dbPrefix,
                        'doConnect'          => false);

    sys::import('xaraya.database');
    xarDB_Init($init_args);

    // Not all Database Servers support selecting the specific db *after* connecting
    // so let's try connecting with the dbname first, and then without if that fails
    $dbExists = false;
    try {
      $dbconn = xarDBNewConn($init_args);
      $dbExists = true;
    } catch(Exception $e) {
      // Couldn't connect to the specified dbName
      // Let's try without db name
      try {
        $init_args['databaseName'] ='';
        $dbconn = xarDBNewConn($init_args);
      } catch(Exception $e) {
        // It failed without dbname too
        return xarTpl::module('installer','admin','errors',array('layout' => 'no_connection', 'message' => $e->getMessage()));
      }
    }

    if ($dbType == 'mysql') {
        $tokens = explode('.',mysql_get_server_info());
        $data['version'] = $tokens[0] ."." . $tokens[1] . ".0";
        $data['required_version'] = MYSQL_REQUIRED_VERSION;
        $mysql_version_ok = version_compare($data['version'],$data['required_version'],'ge');
        if (!$mysql_version_ok) {
            $data['layout'] = 'bad_version';
            return xarTpl::module('installer','admin','check_database',$data);
        }
    }

    if ($dbType == 'mysqli') {
        $tokens = explode('.',mysqli_get_server_info($dbconn->getResource()));
        $data['version'] = $tokens[0] ."." . $tokens[1] . ".0";
        $data['required_version'] = MYSQL_REQUIRED_VERSION;
        $mysql_version_ok = version_compare($data['version'],$data['required_version'],'ge');
        if (!$mysql_version_ok) {
            $data['layout'] = 'bad_version';
            return xarTpl::module('installer','admin','check_database',$data);
        }
    }

    if (!$createDB && !$dbExists) {
        $data['dbName'] = $dbName;
        $data['layout'] = 'not_found';
        return xarTpl::module('installer','admin','check_database',$data);
    }

    $data['confirmDB']  = $confirmDB;
    if ($dbExists && !$confirmDB) {
        $data['dbHost']     = $dbHost;
        $data['dbName']     = $dbName;
        $data['dbUname']    = $dbUname;
        $data['dbPass']     = $dbPass;
        $data['dbPrefix']   = $dbPrefix;
        $data['dbType']     = $dbType;
        $data['dbCharset']  = $dbCharset;
        $data['install_create_database']      = $createDB;
        $data['language']    = $install_language;
        // Gots to ask confirmation
        return $data;
    }

    sys::import('xaraya.tableddl');
    // Create the database if necessary
    if ($createDB) {
        $data['confirmDB']  = true;
        //Let's pass all input variables thru the function argument or none, as all are stored in the system.config.php
        //Now we are passing all, let's see if we gain consistency by loading config.php already in this phase?
        //Probably there is already a core function that can make that for us...
        //the config.system.php is lazy loaded in xarSystemVars::get(sys::CONFIG, $name), which means we cant reload the values
        // in this phase... Not a big deal 'though.
        if ($dbExists) {
            if (!$dbconn->Execute('DROP DATABASE ' . $dbName)) return;
        }
        if(!$dbconn->Execute(xarDBCreateDatabase($dbName,$dbType,$dbCharset))) {
          //if (!xarInstallAPIFunc('createdb', $config_args)) {
          $msg = xarML('Could not create database (#(1)). Check if you already have a database by that name and remove it.', $dbName);
          throw new Exception($msg);
        }
    } else {
        $removetables = true;
    }

    // Re-init with the new values and connect
    $systemArgs = array('userName'           => $dbUname,
                        'password'           => $dbPass,
                        'databaseHost'       => $dbHost,
                        'databaseType'       => $dbType,
                        'databaseName'       => $dbName,
                        'databaseCharset'    => $dbCharset,
                        'prefix'             => $dbPrefix);
    // Connect to database
    xarDB_init($systemArgs);

    // drop all the tables that have this prefix
    //TODO: in the future need to replace this with a check further down the road
    // for which modules are already installed

    if (isset($removetables) && $removetables) {
        $dbconn = xarDB::getConn();
        $dbinfo = $dbconn->getDatabaseInfo();
        try {
            $dbconn->begin();
            foreach($dbinfo->getTables() as $tbl) {
                $table = $tbl->getName();
                if(strpos($table,'_') && (substr($table,0,strpos($table,'_')) == $dbPrefix)) {
                    // we have the same prefix.
                    try {
                        $sql = xarDBDropTable($table,$dbType);
                        $dbconn->Execute($sql);
                    } catch(SQLException $dropfail) {
                        // retry with drop view
                        // TODO: this should be transparent in the API
                        $ddl = "DROP VIEW $table";
                        $dbconn->Execute($ddl);
                    }
                }
            }
            $dbconn->commit();
        } catch (Exception $e) {
            // All other exceptions but the ones we already handled
            $dbconn->rollback();
            throw $e;
        }
    }
    // install the security stuff here, but disable the registerMask and
    // and xarSecurityCheck functions until we've finished the installation process
    sys::import('xaraya.security');
    sys::import('xaraya.modules');
    sys::import('xaraya.hooks');
    sys::import('xaraya.blocks');
    // load events so register functions work 
    sys::import('xaraya.events');

    // 1. Load base and modules module
    $modules = array('base','modules');
    foreach ($modules as $module) {
        if (!xarInstallAPIFunc('initialise', array('directory' => $module,'initfunc'  => 'init'))) return;
    }

    // 2. Create some variables we'll need in installing modules 
    sys::import('xaraya.variables');
    $a = array();
    xarVar_init($a);
    xarConfigVars::set(null, 'System.ModuleAliases',array());
    xarConfigVars::set(null, 'Site.MLS.DefaultLocale', $install_language);
    
    // 3. Load the definitions of all the modules in the modules table
    $prefix = xarDB::getPrefix();
    $modulesTable = $prefix .'_modules';
    $tables =& xarDB::getTables();

    $newModSql   = "INSERT INTO $modulesTable
                    (name, regid, directory,
                     version, class, category, admin_capable, user_capable, state)
                    VALUES (?,?,?,?,?,?,?,?,?)";
    $newStmt     = $dbconn->prepareStatement($newModSql);

    $modules = array('authsystem','roles','privileges','installer','blocks','themes','dynamicdata','mail');
    // Series of updates, begin transaction
    try {
        $dbconn->begin();
        foreach($modules as $index => $modName) {
            // Insert module
            $modversion=array();$bindvars = array();
            // NOTE: We can not use the sys::import here, since the variable scope is important.
            include_once sys::code() . "modules/$modName/xarversion.php";
            $bindvars = array($modName,
                              $modversion['id'],       // regid, from xarversion
                              $modName,
                              $modversion['version'],
                              $modversion['class'],
                              $modversion['category'],
                              isset($modversion['admin']) ? $modversion['admin']:false,
                              isset($modversion['user'])  ? $modversion['user']:false,
                              3); // chris: shouldn't this be a class constant?
            $result = $newStmt->executeUpdate($bindvars);
            $newModId = $dbconn->getLastId($tables['modules']);
        }
        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }

    // 4. Initialize all the modules we haven't yet
    $modules = array('privileges','roles','blocks','authsystem','themes','dynamicdata','mail');
    foreach ($modules as $module) {
        try {
            sys::import('modules.' . $module . '.xartables');
            $tablefunc = $module . '_xartables';
            if (function_exists($tablefunc)) xarDB::importTables($tablefunc());
        } catch (Exception $e) {}
        if (!xarInstallAPIFunc('initialise', array('directory' => $module, 'initfunc'  => 'init'))) return;
    }

    if (!xarInstallAPIFunc('initialise', array('directory'=>'authsystem', 'initfunc'=>'activate'))) return;
    if (!xarInstallAPIFunc('initialise', array('directory'=>'privileges', 'initfunc'=>'activate'))) return;
    if (!xarInstallAPIFunc('initialise', array('directory'=>'mail', 'initfunc'=>'activate'))) return;
    // todo: activate blocks here *after* all other core modules
    // block activation takes care of registering all block types for core modules
    //if (!xarInstallAPIFunc('initialise', array('directory'=>'blocks', 'initfunc'=>'activate'))) return;
    
    // create the default masks and privilege instances
    sys::import('modules.privileges.xarsetup');
    initializeSetup();

    // TODO: is this is correct place for a default value for a modvar?
    xarModVars::set('base', 'AlternatePageTemplate', 'homepage');

    // If we are here, the base system has completed
    // We can now pass control to xaraya.

    // Set the allowed locales to our "C" locale and the one used during installation
    // TODO: make this a bit more friendly.
    $necessaryLocale = array('en_US.utf-8');
    $install_locale  = array($install_language);
    $allowed_locales = array_merge($necessaryLocale, $install_locale);

    xarConfigVars::set(null, 'Site.MLS.AllowedLocales',$allowed_locales);    $data['language'] = $install_language;

    $data['phase'] = 5;
    $data['phase_label'] = xarML('Step Five');

    return $data;
}

?>
