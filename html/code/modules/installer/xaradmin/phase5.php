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

    // Get the database connection configuration from the configuration file
    sys::import('xaraya.database');
    $init_args = xarDatabase::getConfig();
    
//    if (!xarVar::fetch('install_create_database',     'checkbox',$createDB,false,xarVar::NOT_REQUIRED)) return;
//    if (!xarVar::fetch('confirmDB','bool',$confirmDB,false,xarVar::NOT_REQUIRED)) return;

//---------------------------------------------------------------------------
    // Some sanity checks
    // We need a database name
    if ($init_args['databaseName'] == '') {
        return xarTpl::module('installer','admin','errors',array('layout' => 'no_database'));
    }

    // Allow only a-z 0-9 and _ in the table prefix
    if (!preg_match('/^\w*$/',$init_args['prefix'])) {
        return xarTpl::module('installer','admin','errors',array('layout' => 'bad_character'));
    }
//---------------------------------------------------------------------------
    // Cater to SQLite before trying to connect
    // Create the database if it doesn't exist
    if (in_array($init_args['databaseType'], array('sqlite3', 'pdosqlite'))) {
		// Make sure we have a directory var/sqlite
		if (!is_dir(sys::varpath() . '/sqlite')) {
			mkdir(sys::varpath() . '/sqlite', 0755);
		}
		
		$dbpath = sys::varpath() . '/sqlite/' . $init_args['databaseName'];
		if (file_exists($dbpath)) {
			// We already have a database with this name
        	return xarTpl::module('installer','admin','errors',array('layout' => 'database_exists', 'database_name' => $dbpath));
		} else {
			try {
				$db = new SQLite3($dbpath);
				// For SQLite the database name is the path to the database
				$init_args['databaseName'] = $dbpath;
			} catch(Exception $e){
				 echo $e->getMessage(); 
				 exit;
			}
		}
    }   

//---------------------------------------------------------------------------
    // Initialise xarDatabase and xarDB
    // We are not yet trying to connect.
    $init_args['doConnect'] = false;

    xarDatabase::init($init_args);

//---------------------------------------------------------------------------
    // Not all Database Servers support selecting the specific database *after* connecting
    // so let's try connecting with the database name first, and then without if that fails
	$dbExists = false;
    switch ($init_args['databaseType']) {
        case 'sqlite3':
        case 'pdosqlite':
			// Ignore sqlite. We already did that above
			// But we do want to get a connection to use below
			$dbconn = xarDB::newConn($init_args);
		break;
        case 'mysqli':
        case 'pdomysqli':
		case 'pgsql':
		case 'pdopgsql':
			try {
			  $init_args['doConnect'] = true;
			  $dbconn = xarDB::newConn($init_args);
			  $dbExists = true;
			} catch(Exception $e) {
			  // Couldn't connect to the specified dbName
			  // Let's try without db name
			  try {
				$name = $init_args['databaseName'];
				$init_args['databaseName'] ='';
				$dbconn = xarDB::newConn($init_args);
				$init_args['databaseName'] =$name;
			  } catch(Exception $e) {
				// It failed without dbname too
				return xarTpl::module('installer','admin','errors',array('layout' => 'no_connection', 'message' => $e->getMessage()));
			  }
			}
        break;
        default:
		throw new Exception(xarML("Unknown database type: '#(1)'", $init_args['databaseType']));
	}

	if ($dbExists) {
		// We already have a database with this name
        return xarTpl::module('installer','admin','errors',array('layout' => 'database_exists', 'database_name' => $init_args['databaseName']));
	}

//---------------------------------------------------------------------------
    // Check versions
    // We already made sure that there is a database in the case of sqlite3 above. Check other database types
    // CHECKME: we already did this in phase 4, no?
    switch ($init_args['databaseType']) {
        case 'mysqli':
        case 'pdomysqli':
            $source = $dbconn->getResource();
            // @checkme does resource have this property?
            $tokens = explode('.', $source->server_info);
            $data['version'] = $tokens[0] ."." . $tokens[1] . ".0";
            $data['required_version'] = xarInst::MYSQL_REQUIRED_VERSION;
            $version_ok = version_compare($data['version'],$data['required_version'],'ge');
        break;
        default:
        	// Other dbs are OK by definition
        	// SQLite3, for instance
        	$version_ok = true;
        break;
    }
    
    if (!$version_ok) {
        return xarTpl::module('installer','admin','errors',array('layout' => 'bad version'));
    }

//---------------------------------------------------------------------------
	// Try creating the database if it doesn't exist
	// We already did SQLite3
    sys::import('xaraya.tableddl');
    // Try and create the database
    if (!$dbExists) {
    
//		Hold on to this for now
//        $data['confirmDB']  = true;

        // Let's pass all input variables thru the function argument or none, as all are stored in the system.config.php
        // Now we are passing all, let's see if we gain consistency by loading config.php already in this phase?
        // Probably there is already a core function that can make that for us...
        // the config.system.php is lazy loaded in xarSystemVars::get(sys::CONFIG, $name), which means we cant reload the values
        // in this phase... Not a big deal 'though.
//        if ($dbExists) {
//            if (!$dbconn->Execute('DROP DATABASE ' . $dbName)) return;
//        }

        if(!$dbconn->Execute(xarTableDDL::createDatabase($init_args['databaseName'],
        												 $init_args['databaseType'],
        												 $init_args['databaseCharset']))) {
          //if (!xarInstallAPIFunc('createdb', $config_args)) {
        	return xarTpl::module('installer','admin','errors',array('layout' => 'cannot_create', 'database_name' => $init_args['databaseName']));
        }

		// Open a new connection where the new database is referenced
		// TODO: We should close the old one, but we have no good close method defined
		$dbconn = xarDB::newConn($init_args);
      
		// We just created an empty database. There are no tables yet.
        $removetables = false;
    } else {
        $removetables = true;
	}

    // If this is not a new database we need to
    // drop all the tables that have the prefix we are working with
    // TODO: in the future need to replace this with a check further down the road
    // for which modules are already installed

    if ($removetables) {
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
    
    // Install the security stuff here, but disable the registerMask and
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
        try {
        	xarInstallAPIFunc('initialise', array('directory' => $module,'initfunc'  => 'init'));
        } catch (Exception $e) {
        	return xarTpl::module('installer','admin','errors',array('layout' => 'general_exception', 'message' => $e->getMessage()));        	
        }
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
    $tables = xarDB::getTables();

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
