<?php
/**
 * File: $Id$
 *
 * ADODB Database Abstraction Layer API
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage DB
 * @link xarDB.php
 * @author Marco Canini <m.canini@libero.it>
 */

/**
 * Initializes the database connection.
 *
 * This function loads up ADODB  and starts the database
 * connection using the required parameters then it sets
 * the table prefixes and xartables up and returns true
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access private
 * @param args[databaseType] database type to use
 * @param args[databaseHost] database hostname
 * @param args[databaseName] database name
 * @param args[userName] database username
 * @param args[password] database password
 * @param args[systemTablePrefix] system table prefix
 * @param args[siteTablePrefix] site table prefix
 * @return bool true
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
    define('ADODB_DIR', 'xaradodb');

    include_once 'xaradodb/adodb.inc.php';

	// ADODB-to-Xaraya error-to-exception bridge
	define('ADODB_ERROR_HANDLER', 'xarDB__adodbErrorHandler');

    // Start connection
    $dbconn = ADONewConnection($dbtype);
    if (!$dbconn->Connect($dbhost, $dbuname, $dbpass, $dbname)) {
        xarCore_die("xarDB_init: Failed to connect to $dbtype://$dbuname@$dbhost/$dbname, error message: " . $dbconn->ErrorMsg());
    }
    $GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_NUM;

    // force oracle to a consistent date format for comparison methods later on
    if (strcmp($dbtype, 'oci8') == 0) {
        $dbconn->Execute("alter session set NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
    }

    $GLOBALS['xarDB_connections'] = array($dbconn);
    $GLOBALS['xarDB_tables'] = array();

    $systemPrefix = $args['systemTablePrefix'];
    $sitePrefix   = $args['siteTablePrefix'];

    // BlockLayout Template Engine Tables
    $xartable['template_tags']         = $systemPrefix . '_template_tags';

    return true;
}

/**
 * Gets an array of database connections
 *
 * @author Jim McDonald
 * @access public
 * @return array array of database connections
 */
function xarDBGetConn()
{
    return $GLOBALS['xarDB_connections'];
}

/**
 * Gets an array of database table names
 *
 * @access public
 * @param none
 * @return array array of database tables
 */
function xarDBGetTables()
{
    return $GLOBALS['xarDB_tables'];
}

/**
 * Load the Table Maintenance API
 *
 * @access public
 * @return true
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
 * @returns string
 * @return database host
 */
function xarDBGetHost()
{
    return $GLOBALS['xarDB_systemArgs']['databaseHost'];
}

/**
 * Get the database type
 *
 * @access public
 * @return string database type
 */
function xarDBGetType()
{
    return $GLOBALS['xarDB_systemArgs']['databaseType'];
}

/**
 * Get the database name
 *
 * @access public
 * @return string database name
 */
function xarDBGetName()
{
    return $GLOBALS['xarDB_systemArgs']['databaseName'];
}

/**
 * Get the system table prefix
 *
 * @access public
 * @return string database name
 */
function xarDBGetSystemTablePrefix()
{
    return $GLOBALS['xarDB_systemArgs']['systemTablePrefix'];
}

/**
 * Get the site table prefix
 *
 * @access public
 * @return string database name
 */
function xarDBGetSiteTablePrefix()
{
    return $GLOBALS['xarDB_systemArgs']['siteTablePrefix'];
}

// PROTECTED FUNCTIONS

/**
 * Import module tables in the array of known tables
 *
 * @access private
 * @return array
 */
function xarDB_importTables($tables)
{
    global $xarDB_tables;
    assert('is_array($tables)');
    $xarDB_tables = array_merge($xarDB_tables, $tables);
}

// PRIVATE FUNCTIONS

function xarDB__adodbErrorHandler($databaseName, $funcName, $errNo, $errMsg, $param1=false, $param2=false)
{
    if ($funcName == 'EXECUTE') {
        $msg = xarMLByKey('DATABASE_ERROR_QUERY', $param1, $errMsg);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR_QUERY', new SystemException($msg));
    } else {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR', $errMsg);
    }
}
?>
