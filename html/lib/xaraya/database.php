<?php
/**
 * Creole Database Abstraction Layer API Helpers
 * @todo review how xarDB is defined here + fix ResultSet mess + stop extending Creole for xarDB_Creole class
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
    
class xarDB
{
	private static $mw;   				// We store the applicable middleware class here
	
	public const FETCHMODE_ASSOC = 2;   // Index result set by field name.
	public const FETCHMODE_NUM   = 3;   // Index result set numerically.

	public static function getInstance()
	{
		$middleware_name = xarSystemVars::get(sys::CONFIG, 'DB.Middleware');
		sys::import('xaraya.database.' . strtolower($middleware_name));
		$class = 'xarDB_' . $middleware_name;
		$middleware_class = new $class();
		self::$mw = $middleware_class;
	}

	public static function getHost() 		  { return self::$mw::getHost(); }
	public static function getType() 		  { return self::$mw::getType(); }
	public static function getName() 		  { return self::$mw::getName(); }
	public static function getPrefix() 		  { return self::$mw::getPrefix(); }
	public static function setPrefix($prefix) { self::$mw::setPrefix($prefix); }

	/**
	 * Get an array of database tables
	 *
	 * @return array<mixed> array of database tables
	 * @todo we should figure something out so we dont have to do the getTables stuff, it should be transparent
	 */
	public static function getTables()        { return self::$mw::getTables(); }

	/**
	 * Import an array of database tables into the array of loaded tables Xaraya knows about
	 *
	 * @return void
	 */
	public static function importTables(array $tables=array()) { return self::$mw::importTables($tables); }

	public static function configure($dsn, $flags = array(PDO::CASE_LOWER)) { return self::$mw::configure($dsn, $flag); }
	
	/**
	 * Get a database connection
	 *
	 * @return Connection database connection object
	 */
	public static function getConn($index = 0) 		   { return self::$mw::getConn($index); }

	/**
	 * Initialise a new db connection
	 *
	 * Create a new connection based on the supplied parameters
	 *
	 * @return Connection
	 */
	public static function newConn(array $args = null) { return self::$mw::newConn($args); }
	public static function hasConn($index = 0) 		   { return self::$mw::hasConn($index); }
	public static function getConnIndex() 		       { return self::$mw::getConnIndex(); }
	public static function isIndexExternal($index = 0) { return self::$mw::isIndexExternal($index); }
	
	public static function getConnection($dsn, $flags) { return self::$mw::getConnection($dsn, $flag); }
	
	/**
	 * Get the middleware -> ddl type map
	 *
	 * @return array<mixed>
	 */
	public static function getTypeMap() { return self::$mw::getTypeMap(); }
}

xarDB::getInstance();

function xarDB_init(array &$args)
{
	xarDB::setPrefix($args['prefix']);

	// Register postgres driver, since Creole uses a slightly different alias
	// We do this here so we can remove customisation from creole lib.
	// @deprecated 2.4.0 postgres hasn't been supported for a long time now
	// Creole::registerDriver('postgres','creole.drivers.pgsql.PgSQLConnection');

	// If doConnect is null we connect. Not very intuitive
	$args['doConnect'] = $args['doConnect'] ?? true;
	if($args['doConnect']) {
		try {
			xarDB::newConn($args);
		} catch (Exception $e) {
			throw $e;
		}
	}
	return true;
}
    
class xarDatabase extends xarObject
{
    public static function init(array $args = array())
    {
        if (empty($args)) {
            // If no $args were passed then get then from the configuration file.
            $args = self::getConfig();
        }
        return self::connect($args);
    }

    public static function getConfig()
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
        $systemArgs = array('databaseHost'    => $host,
                            'databasePort'    => $port,
                            'databaseType'    => xarSystemVars::get(sys::CONFIG, 'DB.Type'),
                            'databaseName'    => xarSystemVars::get(sys::CONFIG, 'DB.Name'),
        					'userName'        => $userName,
                            'password'        => $password,
                            'prefix'          => xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix'),
                            'databaseCharset' => xarSystemVars::get(sys::CONFIG, 'DB.Charset'),
                            'persistent'      => $persistent);
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
                } catch (Exception $e) {}
                if ($connected) break;
            }
            if (!$connected) {
            var_dump($e->getMessage());
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
