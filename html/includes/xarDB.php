<?php
/**
 * File: $Id$
 * 
 * ADODB Database Abstraction Layer API Helpers
 * 
 * @package database
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage adodb
 * @author Marco Canini
*/

/**
 * Initializes the database connection.
 *
 * This function loads up ADODB  and starts the database
 * connection using the required parameters then it sets
 * the table prefixes and xartables up and returns true
 * <br>
 * @access protected
 * @global array xarDB_systemArgs
 * @global object dbconn database connection object
 * @global integer ADODB_FETCH_MODE array fectching by associative or numeric keyed arrays
 * @global array xarTables database tables used by Xaraya
 * @param string args[databaseType] database type to use
 * @param string args[databaseHost] database hostname
 * @param string args[databaseName] database name
 * @param string args[userName] database username
 * @param string args[password] database password
 * @param string args[systemTablePrefix] system table prefix
 * @param string args[siteTablePrefix] site table prefix
 * @param integer whatElseIsGoingLoaded
 * @return bool true on success, false on failure
 * @todo <marco> move template tag table definition somewhere else?
 * @todo <marcel> do we want to check to make sure ADODB_DIR is defined as xaradodb?
 */
function xarDB_init($args, $whatElseIsGoingLoaded)
{
    $GLOBALS['xarDB_systemArgs'] = $args;

    // Get database parameters
    $dbType  = $args['databaseType'];
    $dbHost  = $args['databaseHost'];
    $dbName  = $args['databaseName'];
    $dbUname = $args['userName'];
    $dbPass  = $args['password'];

    // ADODB configuration
    // FIXME: do we need a check if the constant is defined whether it has the
    //        right value?
    if (!defined('ADODB_DIR')) {
        define('ADODB_DIR', 'xaradodb');
    }

    include_once ADODB_DIR .'/adodb.inc.php';

    // ADODB-to-Xaraya error-to-exception bridge
    if (!defined('ADODB_ERROR_HANDLER')) {
        define('ADODB_ERROR_HANDLER', 'xarDB__adodbErrorHandler');
    }

    // Start connection
    $dbconn = ADONewConnection($dbType);
    if (!$dbconn->Connect($dbHost, $dbUname, $dbPass, $dbName)) {
        xarCore_die("xarDB_init: Failed to connect to $dbType://$dbUname@$dbHost/$dbName, error message: " . $dbconn->ErrorMsg());
    }
    $GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_NUM;

    // force oracle to a consistent date format for comparison methods later on
    if (strcmp($dbType, 'oci8') == 0) {
        $dbconn->Execute("ALTER session SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
    }

    $GLOBALS['xarDB_connections'] = array($dbconn);
    $GLOBALS['xarDB_tables'] = array();

    $systemPrefix = $args['systemTablePrefix'];
    $sitePrefix   = $args['siteTablePrefix'];

    // BlockLayout Template Engine Tables
    $GLOBALS['xarDB_tables']['template_tags'] = $systemPrefix . '_template_tags';

    return true;
}

/**
 * Get a list of database connections
 *
 * @access public
 * @global array xarDB_connections array of database connection objects
 * @return array array of database connection objects
 */
function xarDBGetConn()
{
    return $GLOBALS['xarDB_connections'];
}

/**
 * Get an array of database tables
 *
 * @access public
 * @global array xarDB_tables array of database tables
 * @return array array of database tables
 */
function xarDBGetTables()
{
    return $GLOBALS['xarDB_tables'];
}

/**
 * Load the Table Maintenance API
 *
 * Include 'includes/xarTableDDL.php'using include_once()
 * and return true
 *
 * @access public
 * @return true
 * @todo <johnny> change to protected or private?
 */
function xarDBLoadTableMaintenanceAPI()
{
    // Include Table Maintainance API file
    include_once 'includes/xarTableDDL.php';

    return true;
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

/**
 * ADODB error handler bridge
 *
 * @access private
 * @param string databaseName
 * @param string funcName
 * @param integer errNo
 * @param string errMsg
 * @param bool param1
 * @param bool param2
 * @raise DATABASE_ERROR
 * @todo <marco> complete it
 */
function xarDB__adodbErrorHandler($databaseName, $funcName, $errNo, $errMsg, $param1 = false, $param2 = false)
{
    if ($funcName == 'EXECUTE') {
        $msg = xarML('Database error while executing: \'#(1)\'; error description is: \'#(2)\'.', $param1, $errMsg);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR_QUERY', new SystemException("ErrorNo: ".$errNo.", Message:".$msg));
    } else {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR', $errMsg);
    }
}
?>
