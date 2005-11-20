<?php
/**
 * Database Abstraction Layer API Helpers
 * 
 * @package database
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage database
 * @author Marco Canini
*/

/**
 * Initializes the database connection.
 *
 * This function loads up the db abstraction layer  and starts the database
 * connection using the required parameters then it sets
 * the table prefixes and xartables up and returns true
 * 
 * @access protected
 * @global array xarDB_systemArgs
 * @global object dbconn database connection object
 * @global array xarTables database tables used by Xaraya
 * @param string args[databaseType] database type to use
 * @param string args[databaseHost] database hostname
 * @param string args[databaseName] database name
 * @param string args[userName] database username
 * @param string args[password] database password
 * @param bool args[persistent] flag to say we want persistent connections (optional)
 * @param string args[systemTablePrefix] system table prefix
 * @param string args[siteTablePrefix] site table prefix
 * @param bool   args[doConnect] on inialisation, also connect, defaults to true if not specified
 * @param integer whatElseIsGoingLoaded
 * @return bool true on success, false on failure
 * @todo <marco> move template tag table definition somewhere else?
 */
function xarDB_init($args, $whatElseIsGoingLoaded)
{
    if(!array_key_exists('doConnect',$args)) $args['doConnect'] = true;
    $GLOBALS['xarDB_systemArgs'] = $args;
    
    include_once 'creole/xarCreole.php';
    
    // Start the default connection
    $GLOBALS['xarDB_connections'] = array();
    
    if($args['doConnect']) {
        $dbconn =& xarDBNewConn();
    }

    $GLOBALS['xarDB_tables'] = array();

    $systemPrefix = $args['systemTablePrefix'];
    $sitePrefix   = $args['siteTablePrefix'];

    // BlockLayout Template Engine Tables
    // FIXME: this doesnt belong here
    $GLOBALS['xarDB_tables']['template_tags'] = $systemPrefix . '_template_tags';

    // All initialized register the shutdown function
    //register_shutdown_function('xarDB__shutdown_handler');

    return true;
}

/**
 * Shutdown handler for the DB subsystem
 *
 * This function is the shutdown handler for the 
 * DB subsystem. It runs on the end of a request
 *
 */
function xarDB__shutdown_handler()
{
    // Shutdown handler for the DB subsystem
    // Once the by reference handling of the dbconn is in, we can do 
    // a central close for the db connection here.
}

/**
 * Get a database connection
 *
 * @access public
 * @global array  xarDB_connections array of database connection objects
 * @return object database connection object
 */
function &xarDBGetConn($index=0)
{
    // we only want to return the first connection here
    // perhaps we'll add linked list capabilities to this soon
    return $GLOBALS['xarDB_connections'][$index];
}

/**
 * Initialise a new db connection
 *
 * Create a new connection based on the supplied parameters
 * 
 * @access public
 * @todo   do we need the global?
 */
function &xarDBNewConn($args = NULL)
{
    if (!isset($args)) {
        $args =  $GLOBALS['xarDB_systemArgs'];
    }
    // Get database parameters
    $dbType  = $args['databaseType'];
    $dbHost  = $args['databaseHost'];
    $dbName  = $args['databaseName'];
    $dbUname = $args['userName'];
    $dbPass  = $args['password'];
    $persistent = !empty($args['persistent']) ? true : false;

    $dsn = array('phptype' => $dbType,
                 'hostspec' => $dbHost,
                 'username' => $dbUname,
                 'password' => $dbPass,
                 'database' => $dbName);
    
    $conn = null;
    $conn = xarDB::getConnection($dsn,ResultSet::FETCHMODE_NUM);
    // Store the connection for global access.
    $GLOBALS['xarDB_connections'][] =& $conn;

    xarLogMessage("New connection created, now serving " . count($GLOBALS['xarDB_connections']) . " connections");
    return $conn;
}

/**
 * Get an array of database tables
 *
 * @access public
 * @global array xarDB_tables array of database tables
 * @return array array of database tables
 */
function &xarDBGetTables()
{
    return $GLOBALS['xarDB_tables'];
}

/**
 * Load the Table Maintenance API
 *
 * @access public
 * @return true
 * @todo <johnny> change to protected or private?
 * @todo <mrb> Insane function name
 * @tod  <mrb> This needs to be replaced by datadict functionality
 */
function xarDBLoadTableMaintenanceAPI()
{
    // Include Table Maintainance API file
    include_once 'includes/xarTableDDL.php';

    return true;
}

/**
 * Create a data dictionary object
 *
 * This function will include the appropriate classes and instantiate
 * a data dictionary object for the specified mode. The default mode
 * is 'READONLY', which just provides methods for reading the data
 * dictionary. Mode 'METADATA' will return the meta data object.
 * Mode 'ALTERTABLE' will provide methods for altering schemas
 * (creating, removing and changing tables, indexes, constraints, etc).
 * Mode 'ALTERDATABASE' will provide the highest level of commands
 * for creating, dropping and changing databases.
 *
 * NOTE: until the data dictionary is split into separate classes
 * all modes except METADATA will return the ALTERDATABASE object.
 *
 * @access public
 * @return data   dictionary object (specifics depend on mode)
 * @param  object $dbconn database connection object
 * @param  string $mode the mode in which the data dictionary will be used; default READONLY
 * @todo   fully implement the mode, by layering the classes into separate files of readonly and amend methods
 * @todo   xarMetaData class needs to accept the database connection object
 * @todo   make xarMetaData the base class for the data dictionary
 * @todo   move these comments off to some proper document
 */
function &xarDBNewDataDict(&$dbconn, $mode = 'READONLY')
{
    // Include the data dictionary source.
    // Depending on the mode, there may be one or more files to include.
    include_once 'includes/xarDataDict.php';

    // Decide which class to use.
    if ($mode == 'METADATA') {
        $class = 'xarMetaData';
    } else {
        // 'READONLY', 'ALTERTABLE', 'ALTERDATABASE' or other.
        $class = 'xarDataDict';
    }

    // Instantiate the object.
    $dict = new $class($dbconn);

    return $dict;
}

/**
 * Get the database host
 *
 * @access public
 * @return string
 */
function xarDBGetHost()
{
    return $GLOBALS['xarDB_systemArgs']['databaseHost'];
}

/**
 * Get the database type
 *
 * @access public
 * @return string
 */
function xarDBGetType()
{
    return $GLOBALS['xarDB_systemArgs']['databaseType'];
}

/**
 * Get the database name
 *
 * @access public
 * @return string
 */
function xarDBGetName()
{
    return $GLOBALS['xarDB_systemArgs']['databaseName'];
}

/**
 * Get the system table prefix
 *
 * @access public
 * @return string
 */
function xarDBGetSystemTablePrefix()
{
    return $GLOBALS['xarDB_systemArgs']['systemTablePrefix'];
}

/**
 * Get the site table prefix
 *
 * @access public
 * @return string
 * @todo change it back to return site table prefix
 *       when we decide how to store site information
 */
function xarDBGetSiteTablePrefix()
{
    //return $GLOBALS['xarDB_systemArgs']['siteTablePrefix'];
    return xarDBGetSystemTablePrefix();
}

/**
 * Import module tables in the array of known tables
 *
 * @access protected
 * @global xartable array
 */
function xarDB_importTables($tables)
{
    assert('is_array($tables)');
    $GLOBALS['xarDB_tables'] = array_merge($GLOBALS['xarDB_tables'], $tables);
}


?>
