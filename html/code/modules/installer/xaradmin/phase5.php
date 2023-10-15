<?php
/**
 * Installer
 *
 * @package modules\installer\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/200.html
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
 * @param string dbHost
 * @param string dbName
 * @param string dbUname
 * @param string dbPass
 * @param string dbPrefix
 * @param string dbType
 * @param bool createDb
 * @return array<mixed>|string|void data for the template display
 */
function installer_admin_phase5()
{
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVar::fetch('install_language','str::',$install_language, 'en_US.utf-8', xarVar::NOT_REQUIRED);
    xarVar::setCached('installer','installing', true);

    // Get arguments
    if (!xarVar::fetch('install_database_host','pre:trim:passthru:str',$dbHost)) return;
    if (!xarVar::fetch('install_database_name','pre:trim:passthru:str',$dbName,'',xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('install_database_username','pre:trim:passthru:str',$dbUname,'',xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('install_database_password','pre:trim:passthru:str',$dbPass,'',xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('install_database_prefix','pre:trim:passthru:str',$dbPrefix,'xar',xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('install_database_charset','pre:trim:passthru:str',$dbCharset,'utf8',xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('install_database_type','str:1:',$dbType)) return;
    if (!xarVar::fetch('install_create_database','checkbox',$createDB,false,xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('confirmDB','bool',$confirmDB,false,xarVar::NOT_REQUIRED)) return;

    if ($dbName == '') {
        return xarTpl::module('installer','admin','errors',array('layout' => 'no_database'));
    }

    // allow only a-z 0-9 and _ in table prefix
    if (!preg_match('/^\w*$/',$dbPrefix)) {
        return xarTpl::module('installer','admin','errors',array('layout' => 'bad_character'));
    }
    
    // Check versions
    $version_ok = false;
    
    // Cater to SQLite before trying to connect
    $dbPathName = $dbName;
    if (in_array($dbType, array('sqlite', 'sqlite3'))) {
        switch ($dbType) {
            case 'sqlite':
                $version_ok = version_compare(PHP_VERSION,'5.4.0','lt');
            break;
            case 'sqlite3':
                $version_ok = version_compare(PHP_VERSION,'5.4.0','ge');
            break;
        }
        if ($version_ok) {
            // Create the database in the directory we want, otherwise it will be created below
            if (!is_dir(sys::varpath() . '/sqlite')) {
                mkdir(sys::varpath() . '/sqlite', 0755);
            }
            try {
                $dbpath = sys::varpath() . '/sqlite/';
                $db = new SQLite3($dbpath . $dbName);
                $dbPathName = $dbpath . $dbName;
            } catch(Exception $e){
                 echo $e->getMessage(); 
                 exit;
            }
        } else {
            $data['layout'] = 'bad_version';
            return xarTpl::module('installer','admin','check_database',$data);
        }
    }   

    // Save config data
    $config_args = array('dbHost'    => $dbHost,
                         'dbName'    => $dbPathName,
                         'dbUname'   => $dbUname,
                         'dbPass'    => $dbPass,
                         'dbPrefix'  => $dbPrefix,
                         'dbType'    => $dbType,
                         'dbCharset' => $dbCharset);
    //  Write the config
    xarInstallAPIFunc('modifyconfig', $config_args);
    $dbPort = '';

    $init_args =  array('userName'           => $dbUname,
                        'password'           => $dbPass,
                        'databaseHost'       => $dbHost,
                        'databasePort'       => $dbPort,
                        'databaseType'       => $dbType,
                        'databaseName'       => $dbPathName,
                        'databaseCharset'    => $dbCharset,
                        'prefix'             => $dbPrefix,
                        'doConnect'          => false);

    sys::import('xaraya.database');
    xarDatabase::init($init_args);

    // Not all Database Servers support selecting the specific db *after* connecting
    // so let's try connecting with the dbname first, and then without if that fails
    $dbExists = false;
    try {
      $dbconn = xarDB::newConn($init_args);
      $dbExists = true;
    } catch(Exception $e) {
      // Couldn't connect to the specified dbName
      // Let's try without db name
      try {
        $init_args['databaseName'] ='';
        $dbconn = xarDB::newConn($init_args);
      } catch(Exception $e) {
        // It failed without dbname too
        return xarTpl::module('installer','admin','errors',array('layout' => 'no_connection', 'message' => $e->getMessage()));
      }
    }

    // Check versions
    // Check other database types
    switch ($dbType) {
        case 'mysql':
            // @fixme no longer available
            $tokens = explode('.',mysql_get_server_info());
            $data['version'] = $tokens[0] ."." . $tokens[1] . ".0";
            $data['required_version'] = MYSQL_REQUIRED_VERSION;
            $version_ok = version_compare($data['version'],$data['required_version'],'ge');
        break;
        case 'mysqli':
            $source = $dbconn->getResource();
            // @checkme does resource have this property?
            $tokens = explode('.', $source->server_info);
            $data['version'] = $tokens[0] ."." . $tokens[1] . ".0";
            $data['required_version'] = MYSQL_REQUIRED_VERSION;
            $version_ok = version_compare($data['version'],$data['required_version'],'ge');
        break;
    }
    
    if (!$version_ok) {
        $data['layout'] = 'bad_version';
        return xarTpl::module('installer','admin','check_database',$data);
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
        if(!$dbconn->Execute(xarTableDDL::createDatabase($dbName,$dbType,$dbCharset))) {
          //if (!xarInstallAPIFunc('createdb', $config_args)) {
          $msg = xarML('Could not create database (#(1)). Check if you already have a database by that name and remove it.', $dbName);
          throw new Exception($msg);
        }
        
        // Re-init with the new values and connect
        $systemArgs = array('userName'           => $dbUname,
                            'password'           => $dbPass,
                            'databaseHost'       => $dbHost,
                            'databaseType'       => $dbType,
                            'databaseName'       => $dbName,
                            'databaseCharset'    => $dbCharset,
                            'prefix'             => $dbPrefix,
                            'doConnect'          => true);
        // Connect to database
        xarDatabase::init($systemArgs);
        
        // CHECKME: Need to solve this at the level ofg connections, not run a query
        $q = "use $dbName";
        $dbconn->execute($q);
    } else {
        $removetables = true;
    }

    // Drop all the tables that have this prefix
    // TODO: in the future need to replace this with a check further down the road
    // for which modules are already installed

    if (isset($removetables) && $removetables) {
        $dbconn = xarDB::getConn();
        $dbinfo = $dbconn->getDatabaseInfo();
        try {
            $dbconn->begin();
            if (!empty($dbinfo->getTables())) {
                foreach ($dbinfo->getTables() as $tbl) {
                    $table = $tbl->getName();
                    if (strpos($table, '_') && (substr($table, 0, strpos($table, '_')) == $dbPrefix)) {
                        // we have the same prefix.
                        try {
                            $sql = xarTableDDL::dropTable($table, $dbType);
                            $dbconn->Execute($sql);
                        } catch (SQLException $dropfail) {
                            // retry with drop view
                            // TODO: this should be transparent in the API
                            $ddl = "DROP VIEW $table";
                            $dbconn->Execute($ddl);
                        }
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
    // and xarSecurity::check functions until we've finished the installation process
    sys::import('xaraya.security');
    sys::import('xaraya.modules');
    sys::import('xaraya.hooks');
    sys::import('xaraya.blocks');
    // load events so register functions work 
    sys::import('xaraya.events');

    // 1. Load base and modules module
    $modules = array('base','modules');
    foreach ($modules as $module) {
        // @todo it's over for sqlite here because we're missing a specific .xsl transform in tableddl
        if (!xarInstallAPIFunc('initialise', array('directory' => $module,'initfunc'  => 'init'))) return;
    }

    // 2. Create some variables we'll need in installing modules 
    sys::import('xaraya.variables');
    $a = array();
    xarVar::init($a);
    xarConfigVars::set(null, 'System.ModuleAliases',array());
    xarConfigVars::set(null, 'Site.MLS.DefaultLocale', $install_language);
    xarConfigVars::set(null, 'Site.BL.DocType', 'xhtml1-strict');
    // Display query strings for debugging?
    xarConfigVars::set(null, 'Site.BL.ShowQueries', false);
    
    // 3. Load the definitions of all the modules in the modules table
    $prefix = xarDB::getPrefix();
    $modulesTable = $prefix .'_modules';
    $tables =& xarDB::getTables();

    $newModSql   = "INSERT INTO $modulesTable
                    (name, regid, directory,
                     version, class, category, admin_capable, user_capable, state)
                    VALUES (?,?,?,?,?,?,?,?,?)";
    $newStmt     = $dbconn->prepareStatement($newModSql);

    $modules = array('authsystem','roles','privileges','installer','blocks','themes','dynamicdata','mail','categories');
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
                              !empty($modversion['admin']) ? 1 : 0,
                              !empty($modversion['user'])  ? 1 : 0,
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
    $modules = array('privileges','roles','blocks','authsystem','themes','dynamicdata','mail','categories');
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
