<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Marco Canini
// Purpose of file: ADODB Database Abstraction Layer API
// ----------------------------------------------------------------------

/**
 * Initialise the database connection.
 * <br>
 * This function loads up ADODB  and starts the database
 * connection using the required parameters then it sets
 * the table prefixes and xartables up and returns true
 * <br>
 * @access private
 * @param args[databaseType] database type to use
 * @param args[databaseHost] database hostname
 * @param args[databaseName] database name
 * @param args[userName] database username
 * @param args[password] database password
 * @param args[systemTablePrefix] system table prefix
 * @param args[siteTablePrefix] site table prefix
 * @returns bool
 * @return true on success, false on failure
 */
function xarDB_init($args, $whatElseIsGoingLoaded)
{
    global $xarDB_systemArgs;
    $xarDB_systemArgs = $args;

    // Get database parameters
    $dbtype = $args['databaseType'];
    $dbhost = $args['databaseHost'];
    $dbname = $args['databaseName'];
    $dbuname = $args['userName'];
    $dbpass = $args['password'];

    // Decode username and password if necessary
    if (1 == xarCore_getSystemVar('DB.Encoded')) {
        $dbuname = base64_decode($dbuname);
        $dbpass  = base64_decode($dbpass);
    }
    // ADODB configuration
    if (!defined('ADODB_DIR')) {
        define('ADODB_DIR', 'xaradodb');
    }

    include_once 'xaradodb/adodb.inc.php';

		// ADODB-to-Xaraya error-to-exception bridge
		// FIXME: This creates breakage in the tree, until furhter notice commented out
		//define('ADODB_ERROR_HANDLER', 'xarDB__adodbErrorHandler');

    // Database connection is a global (for now)
    global $dbconn;

    // Start connection
    $dbconn = ADONewConnection($dbtype);
    $dbh = $dbconn->Connect($dbhost, $dbuname, $dbpass, $dbname);
    if (!$dbh) {
        xarCore_die("xarDB_init: Failed to connect to $dbtype://$dbuname@$dbhost/$dbname, error message: " . $dbconn->ErrorMsg());
    }
    global $ADODB_FETCH_MODE;
    $ADODB_FETCH_MODE = ADODB_FETCH_NUM;

    // force oracle to a consistent date format for comparison methods later on
    if (strcmp($dbtype, 'oci8') == 0) {
        $dbconn->Execute("alter session set NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
    }

    // Initialise xartables
    // FIXME: <marco> Can we get rid of globale $prefix? $xartable should become $xarDB_tables
    global $xartable, $prefix;
    $prefix = $args['systemTablePrefix'];
    $xartable = array();

    $systemPrefix = $args['systemTablePrefix'];
    $sitePrefix   = $args['siteTablePrefix'];
    // TODO: <marco> for now i'm leaving all the tables to use the system prefix
    //       which of them could be site prefixed?

    // BlockLayout Template Engine Tables
    $xartable['template_tags']         = $systemPrefix . '_template_tags';

    return true;
}

/**
 * Get a list of database connections
 *
 * @access public
 * @param none
 * @return array array of database connections
 * @returns
 */
function xarDBGetConn()
{
    global $dbconn;

    return array($dbconn);
}

/**
 * Get a list of database tables
 *
 * @access public
 * @param none
 * @return array array of database tables
 */
function xarDBGetTables()
{
    global $xartable;

    return $xartable;
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
    global $xarDB_systemArgs;

    return $xarDB_systemArgs['databaseHost'];
}

/**
 * Get the database type
 *
 * @access public
 * @return string database type
 */
function xarDBGetType()
{
    global $xarDB_systemArgs;

    return $xarDB_systemArgs['databaseType'];
}

/**
 * Get the database name
 *
 * @access public
 * @return string database name
 */
function xarDBGetName()
{
    global $xarDB_systemArgs;

    return $xarDB_systemArgs['databaseName'];
}

/**
 * Get the system table prefix
 *
 * @access public
 * @return string database name
 */
function xarDBGetSystemTablePrefix()
{
    global $xarDB_systemArgs;

    return $xarDB_systemArgs['systemTablePrefix'];
}

/**
 * Get the site table prefix
 *
 * @access public
 * @return string database name
 */
function xarDBGetSiteTablePrefix()
{
    global $xarDB_systemArgs;

    return $xarDB_systemArgs['siteTablePrefix'];
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
    global $xartable;

    $xartable = array_merge($xartable, $tables);
}

// PRIVATE FUNCTIONS

function xarDB__adodbErrorHandler($dbms, $fn, $errno, $errmsg, $p1=false, $p2=false)
{
    // I need to complete it.
    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR', new SystemException($errmsg));
}
?>
