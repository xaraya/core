<?php
/**
 * File: $Id: s.xarDB.php 1.26 03/01/21 13:54:43+00:00 johnny@falling.local.lan $
 * 
 * ADODB Database Abstraction Layer API Helpers
 * 
 * @package database
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.org
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
 * @global xarDB_systemArgs array
 * @global dbconn object database connection object
 * @global ADODB_FETCH_MODE integer array fectching by associative or numeric keyed arrays
 * @global xarTable array database tables used by Xaraya
 * @global prefix array  database tables used by Xaraya
 * @param args[databaseType] string database type to use
 * @param args[databaseHost] string database hostname
 * @param args[databaseName] string database name
 * @param args[userName] string database username
 * @param args[password] string database password
 * @param args[systemTablePrefix] string system table prefix
 * @param args[siteTablePrefix] string site table prefix
 * @param whatElseIsGoingLoaded 
 * @return bool true on success, false on failure
 * @todo <marco> Can we get rid of global $prefix? $xartable should become $xarDB_tables
 * @todo <marco> move template tag table definition somewhere else?
 * @todo <marcel> do we want to check to make sure ADODB_DIR is defined as xaradodb?
 */
function xarDB_init($args, $whatElseIsGoingLoaded)
{
    $GLOBALS['xarDB_systemArgs'] = $args;

    // Get database parameters
    $dbtype = $args['databaseType'];
    $dbhost = $args['databaseHost'];
    $dbname = $args['databaseName'];
    $dbuname = $args['userName'];
    $dbpass = $args['password'];

    // ADODB configuration
    // FIXME: do we need a check if the constant is defined whether it has the 
    //        right value?
    if (!defined('ADODB_DIR')) {
        define('ADODB_DIR', 'xaradodb');
    }

    include_once 'xaradodb/adodb.inc.php';

    // ADODB-to-Xaraya error-to-exception bridge
    if (!defined('ADODB_ERROR_HANDLER')) {
        define('ADODB_ERROR_HANDLER', 'xarDB__adodbErrorHandler');
    }

    // Start connection
    $dbconn = ADONewConnection($dbtype);
    if (!$dbconn->Connect($dbhost, $dbuname, $dbpass, $dbname)) {
        xarCore_die("xarDB_init: Failed to connect to $dbtype://$dbuname@$dbhost/$dbname, error message: " . $dbconn->ErrorMsg());
    }
    $GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_NUM;

    // force oracle to a consistent date format for comparison methods later on
    if (strcmp($dbtype, 'oci8') == 0) {
        $dbconn->Execute("ALTER session SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
    }

    $GLOBALS['xarDB_connections'] = array($dbconn);
    $GLOBALS['xarDB_tables'] = array();

    $systemPrefix = $args['systemTablePrefix'];
    $sitePrefix   = $args['siteTablePrefix'];

    // BlockLayout Template Engine Tables
    $GLOBALS['xarDB_tables']['template_tags']         = $systemPrefix . '_template_tags';

    return true;
}

/**
 * Get a list of database connections
 *
 * @access public
 * @global xarDB_connections array of database connection objects
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
 * @global xarDB_tables array of database tables
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
 */
function xarDBGetSiteTablePrefix()
{
    return $GLOBALS['xarDB_systemArgs']['siteTablePrefix'];
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
 * @param databaseName string
 * @param funcName string
 * @param errNo integer
 * @param errMsg string
 * @param param1 bool
 * @param param2 bool
 * @raise DATABASE_ERROR
 * @todo <marco> complete it
 */
function xarDB__adodbErrorHandler($databaseName, $funcName, $errNo, $errMsg, $param1 = false, $param2 = false)
{
    if ($funcName == 'EXECUTE') {
        $msg = xarMLByKey('DATABASE_ERROR_QUERY', $param1, $errMsg);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR_QUERY', new SystemException($msg));
    } else {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR', $errMsg);
    }
}
?>
