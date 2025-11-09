<?php

/**
 * @package modules\installer
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Installer\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Installer\AdminGui;
use Exception;
use SQLException;
use SQLite3;
use xarClassMap;
use xarDB;
use xarDatabase;
use xarInst;
use xarInstall;
use xarTableDDL;
use sys;

sys::import('xaraya.modules.method');

/**
 * installer admin phase5 function
 * @extends MethodClass<AdminGui>
 */
class Phase5Method extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Phase 5: Pre-Boot, Modify Configuration
     * @access private
     * @param string dbHost
     * @param string dbName
     * @param string dbUname
     * @param string dbPass
     * @param string dbPrefix
     * @param string dbType
     * @param bool createDb
     * @return array|string|void data for the template display
     * @see AdminGui::phase5()
     */
    public function __invoke(array $args = [])
    {
        if (!file_exists('install.php')) {
            throw new Exception('Already installed');
        }
        $this->var()->find('install_language', $install_language, 'str::', 'en_US.utf-8');
        $this->mem()->set('installer', 'installing', true);

        // Get the database connection configuration from the configuration file
        sys::import('xaraya.database');
        $init_args = xarDatabase::getConfig();

        //    $this->var()->find('install_create_database', $createDB, 'checkbox', false);
        //    $this->var()->find('confirmDB', $confirmDB, 'bool', false);

        //---------------------------------------------------------------------------
        // Some sanity checks
        // We need a database name
        if ($init_args['databaseName'] == '') {
            return $this->tpl()->module('installer', 'admin', 'errors', ['layout' => 'no_database']);
        }

        // Allow only a-z 0-9 and _ in the table prefix
        if (!preg_match('/^\w*$/', $init_args['prefix'])) {
            return $this->tpl()->module('installer', 'admin', 'errors', ['layout' => 'bad_character']);
        }
        //---------------------------------------------------------------------------
        // Cater to SQLite before trying to connect
        // Create the database if it doesn't exist
        if (in_array($init_args['databaseType'], ['sqlite3', 'pdosqlite'])) {

            $location = $init_args['location'];
            if ($location == ':memory:') {
                // The database is stored in memory
                $dbpath = $location;
            } else {
                // The database is stored in storage
                // Make sure we have a directory, e.g. var/sqlite
                if (!is_dir($location)) {
                    mkdir($location, 0o755);
                }
                $dbpath = $location . $init_args['databaseName'];
            }

            // Check whether the database already exists
            if (file_exists($dbpath)) {
                // We already have a database with this name
                return $this->tpl()->module('installer', 'admin', 'errors', ['layout' => 'database_exists', 'database_name' => $dbpath]);
            } else {
                // No prior database, so let's create it
                try {
                    $db = new SQLite3($dbpath);
                } catch (Exception $e) {
                    echo $e->getMessage();
                    $this->exit();
                    return;
                }
            }
        }

        //---------------------------------------------------------------------------
        // Initialise xarDatabase and xarDB
        // We are not yet trying to connect.
        $init_args['doConnect'] = false;
        xarDatabase::init($init_args);

        //---------------------------------------------------------------------------
        // Create a connection and check if a database already exists
        $dbExists = false;
        switch ($init_args['databaseType']) {
            case 'sqlite3':
            case 'pdosqlite':
                $dbconn = xarDB::newConn($init_args);
                $dbExists = true;
                break;
            case 'mysqli':
            case 'pdomysqli':
                // Not all Database Servers support selecting the specific database *after* connecting
                // so let's try connecting with the database name first, and then without if that fails
                try {
                    $init_args['doConnect'] = true;

                    // Try to connect
                    $dbconn = xarDB::newConn($init_args);

                    // Found a database
                    $dbExists = true;
                } catch (Exception $e) {
                    // Couldn't connect to the specified dbName
                    // Let's try without db name
                    try {
                        // Set some temporary values
                        $name = $init_args['databaseName'];
                        $init_args['databaseName'] = '';

                        // Try to connect
                        $dbconn = xarDB::newConn($init_args);

                        // Restore the previous values
                        $init_args['databaseName'] = $name;
                    } catch (Exception $e) {
                        // It failed without dbname, too
                        return $this->tpl()->module('installer', 'admin', 'errors', ['layout' => 'no_connection', 'message' => $e->getMessage()]);
                    }
                }
                if ($dbExists) {
                    // We already have a database with this name
                    return $this->tpl()->module('installer', 'admin', 'errors', ['layout' => 'database_exists', 'database_name' => $init_args['databaseName']]);
                }
                break;
            case 'pgsql':
            case 'pdopgsql':
                // Postgres needs to connect to a database, so we'll take one of the available default dbs
                try {
                    $init_args['doConnect'] = true;

                    // Try to connect
                    $dbconn = xarDB::newConn($init_args);
                    // Found a database
                    $dbExists = true;
                } catch (Exception $e) {
                    // Couldn't connect to the specified dbName
                    // Let's try 'postgres' (guaranteed to exist)
                    try {
                        // Set some temporary values
                        $name = $init_args['databaseName'];
                        $user = $init_args['userName'];

                        // Try to connect
                        $init_args['databaseName'] = 'postgres';
                        $init_args['userName'] = '';
                        $dbconn = xarDB::newConn($init_args);

                        // Restore the previous values
                        $init_args['databaseName'] = $name;
                        $init_args['userName'] = $user;
                    } catch (Exception $e) {
                        // It failed with the default dbname, too
                        return $this->tpl()->module('installer', 'admin', 'errors', ['layout' => 'no_connection', 'message' => $e->getMessage()]);
                    }
                }
                if ($dbExists) {
                    // We already have a database with this name
                    return $this->tpl()->module('installer', 'admin', 'errors', ['layout' => 'database_exists', 'database_name' => $init_args['databaseName']]);
                }
                break;
            default:
                throw new Exception($this->ml("Unknown database type: '#(1)'", $init_args['databaseType']));
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
                $data['version'] = $tokens[0] . "." . $tokens[1] . ".0";
                $data['required_version'] = xarInst::MYSQL_REQUIRED_VERSION;
                $version_ok = version_compare($data['version'], $data['required_version'], 'ge');
                break;
            default:
                // Other dbs are OK by definition
                // SQLite3, for instance
                $version_ok = true;
                break;
        }

        if (!$version_ok) {
            return $this->tpl()->module('installer', 'admin', 'errors', ['layout' => 'bad version']);
        }

        //---------------------------------------------------------------------------
        // Try creating the database if it doesn't exist
        // We already did sqlite3 and pdosqlite
        sys::import('xaraya.tableddl');

        if (!$dbExists) {

            //		Hold on to this for now
            //        $data['confirmDB']  = true;

            // Let's pass all input variables thru the function argument or none, as all are stored in the system.config.php
            // Now we are passing all, let's see if we gain consistency by loading config.php already in this phase?
            // Probably there is already a core function that can make that for us...
            // the config.system.php is lazy loaded in $this->sysConfig()->getVar($name), which means we cant reload the values
            // in this phase... Not a big deal 'though.
            //        if ($dbExists) {
            //            if (!$dbconn->Execute('DROP DATABASE ' . $dbName)) return;
            //        }

            if (!$dbconn->Execute(xarTableDDL::createDatabase(
                $init_args['databaseName'],
                $init_args['databaseType'],
                $init_args['databaseCharset']
            ))) {
                //if (!xarInstall::apiFunc('createdb', $config_args)) {
                return $this->tpl()->module('installer', 'admin', 'errors', ['layout' => 'cannot_create', 'database_name' => $init_args['databaseName']]);
            }

            // Now that we have a database and a full set of $init_args, remove the connection we created above
            // and replace it with a new proper one.
            // From here on $this->db()->getConn() will always get this new one
            xarDB::removeConn();
            $dbconn = xarDB::newConn($init_args);

            // We just created an empty database. There are no tables yet.
            $removetables = false;
        } else {
            $removetables = true;
        }

        // Since for now we don't allow overwriting, just set the following line to false
        // TODO: review later
        $removetables = false;

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
                        if (strpos($table, '_') && (substr($table, 0, strpos($table, '_')) == $init_args['prefix'])) {
                            // we have the same prefix.
                            try {
                                $sql = xarTableDDL::dropTable($table, $init_args['databaseType']);
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
        $modules = ['base','modules'];
        foreach ($modules as $module) {
            try {
                xarInstall::apiFunc('initialise', ['directory' => $module,'initfunc'  => 'init']);
            } catch (Exception $e) {
                return $this->tpl()->module('installer', 'admin', 'errors', ['layout' => 'general_exception', 'message' => $e->getMessage()]);
            }
        }

        // 2. Create some variables we'll need in installing modules
        sys::import('xaraya.variables');
        $a = [];
        $this->var()->init($a);
        $this->config()->setVar('System.ModuleAliases', []);
        $this->config()->setVar('Site.MLS.DefaultLocale', $install_language);
        $this->config()->setVar('Site.BL.DocType', 'xhtml1-strict');
        // Display query strings for debugging?
        $this->config()->setVar('Site.BL.ShowQueries', false);

        // 3. Load the definitions of all the modules in the modules table
        $prefix = $this->db()->getPrefix();
        $modulesTable = $prefix . '_modules';
        $tables = $this->db()->getTables();

        $newModSql   = "INSERT INTO $modulesTable
                        (name, regid, directory,
                         version, class, category, admin_capable, user_capable, state)
                        VALUES (?,?,?,?,?,?,?,?,?)";
        $newStmt     = $dbconn->prepareStatement($newModSql);

        sys::import('xaraya.classmap');
        $modules = ['authsystem','roles','privileges','installer','blocks','themes','dynamicdata','mail','categories'];
        // Series of updates, begin transaction
        try {
            $dbconn->begin();
            foreach ($modules as $index => $modName) {
                // Insert module
                $modversion = [];
                $bindvars = [];
                $result = xarClassMap::findVersion($modName);
                if (!empty($result) && class_exists($result['classname'])) {
                    $versionCall = new $result['classname']();
                    $modversion = $versionCall();
                } else {
                    // NOTE: We can not use the sys::import here, since the variable scope is important.
                    include sys::code() . "modules/$modName/xarversion.php";
                }
                if (empty($modversion)) {
                    throw new \ConfigurationException($modname, 'Invalid version.php or xarversion.php file for module #(1)', $this->getContext());
                }
                $bindvars = [$modName,
                    $modversion['id'],       // regid, from version.php
                    $modName,
                    $modversion['version'],
                    $modversion['class'],
                    $modversion['category'],
                    !empty($modversion['admin']) ? 1 : 0,
                    !empty($modversion['user']) ? 1 : 0,
                    3]; // chris: shouldn't this be a class constant?
                $result = $newStmt->executeUpdate($bindvars);
                $newModId = $dbconn->getLastId($tables['modules']);
            }
            $dbconn->commit();
        } catch (Exception $e) {
            $dbconn->rollback();
            throw $e;
        }

        // 4. Initialize all the modules we haven't yet
        $modules = ['privileges','roles','blocks','authsystem','themes','dynamicdata','mail','categories'];
        foreach ($modules as $module) {
            $result = xarClassMap::findTables($module);
            if (!empty($result) && class_exists($result['classname'])) {
                $tablesCall = new $result['classname']();
                // pass along the DB prefix to $tablesCall
                $this->db()->importTables($tablesCall($prefix));
            } elseif (file_exists("code/modules/$module/xartables.php")) {
                include_once sys::code() . "modules/$module/xartables.php";
                $tablefunc = $module . '_xartables';
                // pass along the DB prefix to $tablefunc
                if (function_exists($tablefunc)) {
                    $this->db()->importTables($tablefunc($prefix));
                }
            }
            if (!xarInstall::apiFunc('initialise', ['directory' => $module, 'initfunc'  => 'init'])) {
                return;
            }
        }

        if (!xarInstall::apiFunc('initialise', ['directory' => 'authsystem', 'initfunc' => 'activate'])) {
            return;
        }
        if (!xarInstall::apiFunc('initialise', ['directory' => 'privileges', 'initfunc' => 'activate'])) {
            return;
        }
        if (!xarInstall::apiFunc('initialise', ['directory' => 'mail', 'initfunc' => 'activate'])) {
            return;
        }
        // todo: activate blocks here *after* all other core modules
        // block activation takes care of registering all block types for core modules
        //if (!xarInstall::apiFunc('initialise', array('directory'=>'blocks', 'initfunc'=>'activate'))) return;

        // create the default masks and privilege instances
        if (!xarInstall::apiFunc('initialise', ['directory' => 'privileges', 'initfunc' => 'initializeSetup'])) {
            return;
        }

        // TODO: is this is correct place for a default value for a modvar?
        $this->mod('base')->setVar('AlternatePageTemplate', 'homepage');

        // If we are here, the base system has completed
        // We can now pass control to xaraya.

        // Set the allowed locales to our "C" locale and the one used during installation
        // TODO: make this a bit more friendly.
        $necessaryLocale = ['en_US.utf-8'];
        $install_locale  = [$install_language];
        $allowed_locales = array_merge($necessaryLocale, $install_locale);

        $this->config()->setVar('Site.MLS.AllowedLocales', $allowed_locales);
        $data['language'] = $install_language;

        $data['phase'] = 5;
        $data['phase_label'] = $this->ml('Step Five');

        return $data;
    }
}
