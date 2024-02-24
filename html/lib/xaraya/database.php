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
    
switch (xarSystemVars::get(sys::CONFIG, 'DB.Middleware')){
	case 'Creole':
		// As per creole.ResultSet.php
		define('FETCHMODE_ASSOC', 1);
		define('FETCHMODE_NUM',   2);
//		define('FETCHMODE_BOTH',  3);
	break;
	case 'PDO':
		define('FETCHMODE_ASSOC', PDO::FETCH_ASSOC);
		define('FETCHMODE_NUM',   PDO::FETCH_NUM);
//		define('FETCHMODE_BOTH',  PDO::FETCH_BOTH);
	break;
	default:
	break;
}

class xarDB
{
	private static $mw;   				// We store the applicable middleware class here
	
	// Get fetch modes associaiated with the middleware
	public const FETCHMODE_ASSOC = FETCHMODE_ASSOC;   // Index result set by field name.
	public const FETCHMODE_NUM   = FETCHMODE_NUM;     // Index result set numerically.

    // Instead of the globals, we save our db info here.
    private static $firstDSN      = null;
    private static $firstFlags    = null;
    private static $connectionMap = array();
    private static $dsnMap        = array();
    private static $flagMap       = array();
    private static $tables        = array();
    private static $prefix        = '';


	public static function getInstance()
	{
		$middleware_name = xarSystemVars::get(sys::CONFIG, 'DB.Middleware');
		sys::import('xaraya.database.' . strtolower($middleware_name));
		$class = 'xarDB_' . $middleware_name;
		$middleware_class = new $class();
		self::$mw = $middleware_class;
	}

    // Not all database types have more than one driver
    public static function getDrivers()
    {
        $map = self::mw::$DriverMap ?? array();
        return $map;
    }
    public static function getPrefix()
    {
        return self::$prefix;
    }
    public static function setPrefix($prefix)
    {
        self::$prefix =  $prefix;
    }

    public static function getHost()
    {
        return self::$firstDSN['hostspec'];
    }
    public static function getType()
    {
        return self::$firstDSN['phptype'];
    }
    public static function getName()
    {
        return self::$firstDSN['database'];
    }

    /**
     * Get an array of database tables
     *
     * @return array<mixed> array of database tables
     * @todo we should figure something out so we dont have to do the getTables stuff, it should be transparent
     */
    public static function &getTables()
    {
        return self::$tables;
    }

	/**
	 * Import an array of database tables into the array of loaded tables Xaraya knows about
	 *
	 * @return void
	 */
    public static function importTables(array $tables = array())
    {
        self::$tables = array_merge(self::$tables, $tables);
    }

	public static function configure($dsn, $flags = array(PDO::CASE_LOWER)) { return self::$mw::configure($dsn, $flag); }
	
    /**
     * Initialise a new db connection
     * Create a new connection based on the supplied parameters
     *
     * @return Connection
     */
    public static function newConn(array $args = null)
    {
        // Minimum for sqlite3 is ['databaseType' => 'sqlite3', 'databaseName' => $filepath] // or ':memory:'
        switch ($args['databaseType']) {
        	case 'sqlite3':
        	case 'pdosqlite':
				$args['phptype']       = $args['databaseType'];
				$args['database']    ??= xarSystemVars::get(sys::CONFIG, 'DB.Host');
				$args['hostspec']    ??= '';
				$args['port']        ??= '';
				$args['username']    ??= '';
				$args['password']    ??= '';
				$args['encoding']    ??= '';
				$dsn = $args;
			break;
			case 'mysqli':
			case 'pdomysqli':
				// Hive off the port if there is one added as part of the host
				$host = xarSystemVars::get(sys::CONFIG, 'DB.Host');
				$host_parts = explode(':', $host);
				$host = $host_parts[0];
				$port = isset($host_parts[1]) ? $host_parts[1] : '';
		
				// Get database parameters
				$dsn = array('phptype'   => $args['databaseType'],
							 'hostspec'  => $host,
							 'port'      => $port,
							 'username'  => $args['userName'],
							 'password'  => $args['password'],
							 'database'  => $args['databaseName'],
							 'encoding'  => $args['databaseCharset']);
			break;
			case 'pgsql':
			case 'pdopgsql':
				// Hive off the port if there is one added as part of the host
				$host = xarSystemVars::get(sys::CONFIG, 'DB.Host');
				$host_parts = explode(':', $host);
				$host = $host_parts[0];
				$port = isset($host_parts[1]) ? $host_parts[1] : '';
		
				// Get database parameters
				$dsn = array('phptype'   => $args['databaseType'],
							 'hostspec'  => $host,
							 'port'      => $port,
							 'username'  => $args['userName'],
							 'password'  => $args['password'],
							 'database'  => $args['databaseName'],
							 'encoding'  => $args['databaseCharset']);
			break;
			default:
			throw new Exception(xarML("Unknown database type: '#(1)'", $args['databaseType']));
        }

		// Get the flags
		// We send the $args to the middleware and get back the flags the way the middleware wants them
		// Creole wants an integer while PDO wants an array
		// Not all flags sent will necessarily be supported
		$flags = self::$mw::getFlags($args);

        // Now get the connection from the connectionMap or the middleware. 
        // If it is new it will be added to the connectionMap
        try {
            $conn = self::getConnection($dsn, $flags); // cached on dsn hash, so no worries
        } catch (Exception $e) {
            throw $e;
        }
		$count = count(self::$connectionMap);
        xarLog::message("New connection created, now serving " . $count . " connections", xarLog::LEVEL_NOTICE);
        return $conn;
    }
    
    /**
     * Get a database connection
     *
     * @return object database connection object
     */
     
     // Curently it always drops to the third option below
     
    public static function &getConn($index = 0)
    {
        // Get connection on demand
        // By default we get the latest connection created, 
        // that is the one that the current value of self::$firstDSN gives us
        if (($index < 0) && isset(self::$firstDSN) && isset(self::$firstFlags)) {
            $conn =  self::getConnection(self::$firstDSN, self::$firstFlags);
        	return $conn;
        }

        // An index value was passed. Go for that connection instead and reset dsn and flags.
        if (count(self::$connectionMap) <= $index && isset(self::$connectionMap[$index])) {
        	$conn = self::$connectionMap[$index];
			self::$firstDSN = $conn->getDSN();
			self::$firstFlags = $conn->getFlags();
        	return $conn;
        }

        // No luck so far. Just get the latest connection and reset dsn and flags.
        if (!empty(self::$connectionMap)) {
			$conn = end(self::$connectionMap);

			self::$firstDSN = end(self::$dsnMap);
			self::$firstFlags = end(self::$flagMap);
			return $conn;
		}
		
        // No luck. This happens e.g. early in the installation before we have a database to connect to
        throw new Exception(xarMLS::translate('No connection available'));
    }

    // CHECKME: what is this used for?
	public static function isIndexExternal($index = 0) { return self::$mw::isIndexExternal($index); }
	
    // CHECKME: what is this used for?
    public static function hasConn($index = 0)
    {
        // Does the connection at $index exist
        if (isset(self::$connectionsMap[$index])) {
            return true;
        }
        return false;
    }

    public static function getConnIndex()
    {
        // The number of connections in the connectionMap
		$count = count(self::$connectionsMap) - 1;
		return $count;
    }

	/**
	 * Get the middleware -> ddl type map
	 *
	 * @return array<mixed>
	 */
	public static function getTypeMap() { return self::$mw::getTypeMap(); }

	/**
	 * Get a connection from the connectionMap or create a new one from the middleware
	 *
	 * @return connection object
	 */
    public static function getConnection(Array $dsn, $flags)
    {
    	// I see no reason to assume we'll always have dsn as an array
/*        if (is_array($dsn)) {
            $dsninfo = $dsn;
        } else {
            $dsninfo = self::parseDSN($dsn);
        }
*/
        // sort $dsn by keys so the serialized result is always the same
        // for identical connection parameters, no matter what their order is
        ksort($dsn);

        $connectionMapKey = crc32(serialize($dsn + array('compat_flags' => ($flags))));

        // see if we already have a connection with these parameters cached
        if(isset(self::$connectionMap[$connectionMapKey])) {
            // persistent connections will be used if a non-persistent one was requested and is available
            // but a persistent connection will be created if a non-persistent one is present

            // TODO: impliment auto close of non persistent and replacing the
            // non persistent with the persistent object so as we dont have
            // both links open for no reason

            if(isset(self::$connectionMap[$connectionMapKey][1])) { // is persistent
                // a persistent connection with these parameters is already there,
                // so we return it, no matter what was specified as persistent flag
                $connection = self::$connectionMap[$connectionMapKey][1];
            } else {
                // we don't have a persistent connection, and since the persistent
                // flag wasn't set either, we just return the non-persistent connection
                $connection = self::$connectionMap[$connectionMapKey][0];
            }

            // if we're here, a non-persistent connection was already there, but
            // the user wants a persistent one, so it will be created

            if ($connection->isConnected()) {
                return $connection;
            }
        }

		// If we got here then we need a connection that is not in the connectionMap
		// Lets let the middleware create it 
		$connection = self::$mw::getConnection($dsn, $flags);

// CHECKME        self::$connectionMap[$connectionMapKey][(int)$persistent] = $connection;
// Creole makes the entry to the connection map an array by adding whether persistent or not
        
        // Add this new connection to the connection map
        self::$connectionMap[$connectionMapKey] = $connection;
        // Add dsn and flags to their respective maps
        self::$dsnMap[] = $dsn;
        self::$flagMap[] = $dsn;
        // Set the values for the latest dsn and flags
//        self::setFirstDSN($dsn);
//        self::setFirstFlags($flags);
		self::$firstDSN = $dsn;
		self::$firstFlags = $flags;

        return $connection;
    }
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
