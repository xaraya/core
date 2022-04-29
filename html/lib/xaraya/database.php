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
     * @param string args[databasePort] database port
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
    class xarDB     extends xarDB_PDO {}
    class ResultSet extends PDOResultSet {}

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
     * @param string args[databasePort] database port
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

class xarDatabase extends xarObject
{
    public static function init(array $args = array())
    {
        if (empty($args)) {
            $args = self::getConfig();
        }
        return self::connect($args);
    }

    protected static function getConfig()
    {
        // Decode encoded DB parameters
        // These need to be there
        $userName = xarSystemVars::get(sys::CONFIG, 'DB.UserName');
        $password = xarSystemVars::get(sys::CONFIG, 'DB.Password');
        $persistent = null;
        try {
            $persistent = xarSystemVars::get(sys::CONFIG, 'DB.Persistent');
        } catch(VariableNotFoundException $e) {
            $persistent = null;
        }
        try {
            if (xarSystemVars::get(sys::CONFIG, 'DB.Encoded') == '1') {
                $userName = base64_decode($userName);
                $password  = base64_decode($password);
            }
        } catch(VariableNotFoundException $e) {
            // doesnt matter, we assume not encoded
        }

        // Hive off the port if there is one added as part of the host
        $host = xarSystemVars::get(sys::CONFIG, 'DB.Host');
        $host_parts = explode(':', $host);
        $host = $host_parts[0];
        $port = isset($host_parts[1]) ? $host_parts[1] : '';

        // Optionals dealt with, do the rest inline
        $systemArgs = array('userName'        => $userName,
                            'password'        => $password,
                            'databaseHost'    => $host,
                            'databasePort'    => $port,
                            'databaseType'    => xarSystemVars::get(sys::CONFIG, 'DB.Type'),
                            'databaseName'    => xarSystemVars::get(sys::CONFIG, 'DB.Name'),
                            'databaseCharset' => xarSystemVars::get(sys::CONFIG, 'DB.Charset'),
                            'persistent'      => $persistent,
                            'prefix'          => xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix'));
        return $systemArgs;
    }

    protected static function connect(array $systemArgs = array())
    {
        $host = $systemArgs['databaseHost'];
        // Connect to the database
        // Cater to different notations in the special case of localhost
        $localhosts = array('localhost', '127.0.0.1');
        if (in_array($host, $localhosts)) {
            $connected = false;
            foreach ($localhosts as $local) {
                $systemArgs['databaseHost'] = $local;
                try {
                    return xarDB_init($systemArgs);
                    $connected = true;
                } catch (Exception $e) {}
                if ($connected) break;
            }
            if (!$connected) {
                throw new Exception("Connection error: a database connection could not be established");
            }
        } else {
            try {
                return xarDB_init($systemArgs);
            } catch (Exception $e) {
                // Catch the error here rather than in the subsystem, because we might be connecting to different databases
                // and want to cater to possible errors in each
                throw new Exception("Connection error: a database connection could not be established");
            }
        }
    }
}

