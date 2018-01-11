<?php
/**
 * Creole Database Abstraction Layer API Helpers
 *
 * @package core
 * @subpackage database
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marco Canini
**/

$middleware = xarSystemVars::get(sys::CONFIG, 'DB.Middleware');

if ($middleware == 'Creole') {

    // Import our db abstraction layer
    // Theoretically any adodb like layer could come in here.
    sys::import('xaraya.creole');
    class xarDB extends xarDB_Creole {}

    /**
     * Initializes the database connection.
     *
     * This function loads up the db abstraction layer  and starts the database
     * connection using the required parameters then it sets
     * the table prefixes and xartables up and returns true
     *
     * 
     * @param string args[databaseType] database type to use
     * @param string args[databaseHost] database hostname
     * @param string args[databaseName] database name
     * @param string args[userName] database username
     * @param string args[password] database password
     * @param bool args[persistent] flag to say we want persistent connections (optional)
     * @param string args[systemTablePrefix] system table prefix
     * @param string args[siteTablePrefix] site table prefix
     * @param bool   args[doConnect] on inialisation, also connect, defaults to true if not specified
     * @return boolean true
     * @todo <marco> move template tag table definition somewhere else?
    **/
    function xarDB_init(array &$args)
    {
        xarDB::setPrefix($args['prefix']);

        // Register postgres driver, since Creole uses a slightly different alias
        // We do this here so we can remove customisation from creole lib.
        xarDB::registerDriver('postgres','creole.drivers.pgsql.PgSQLConnection');

        if(!isset($args['doConnect']) or $args['doConnect']) {
            try {
                xarDB::newConn($args);
            } catch (Exception $e) {
                throw $e;
            }
        }
        return true;
    }
    
} elseif ($middleware == 'PDO') {
    /**
     * PDO Database Abstraction Layer API Helper
     *
     * @package core
     * @subpackage database
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://www.xaraya.info
     *
     * @author Marc Lutolf
    **/

    // Import our db abstraction layer
    // Theoretically any adodb like layer could come in here.
    sys::import('xaraya.pdo');
    class xarDB extends xarDB_PDO {}

    /**
     * Initializes the database connection.
     *
     * This function loads up the db abstraction layer  and starts the database
     * connection using the required parameters then it sets
     * the table prefixes and xartables up and returns true
     *
     * 
     * @param string args[databaseType] database type to use
     * @param string args[databaseHost] database hostname
     * @param string args[databaseName] database name
     * @param string args[userName] database username
     * @param string args[password] database password
     * @param bool args[persistent] flag to say we want persistent connections (optional)
     * @param string args[systemTablePrefix] system table prefix
     * @param string args[siteTablePrefix] site table prefix
     * @param bool   args[doConnect] on inialisation, also connect, defaults to true if not specified
     * @return boolean true
     * @todo <marco> move template tag table definition somewhere else?
    **/
    function xarDB_init(array &$args)
    {
        xarDB::setPrefix($args['prefix']);

        // Register postgres driver, since Creole uses a slightly different alias
        // We do this here so we can remove customisation from creole lib.
    //    xarDB::registerDriver('postgres','creole.drivers.pgsql.PgSQLConnection');

        if(!isset($args['doConnect']) or $args['doConnect']) {
            try {
                xarDB::newConn($args);
            } catch (Exception $e) {
                throw $e;
            }
        }
        return true;
    }
} else {
    die("Invalid middleware definition: " . $middleware); 
}
?>