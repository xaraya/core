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
 * the table prefixes and pntables up and returns true
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
function pnDB_init($args)
{
    global $pnDB_systemArgs;
    $pnDB_systemArgs = $args;

    // Get database parameters
    $dbtype = $args['databaseType'];
    $dbhost = $args['databaseHost'];
    $dbname = $args['databaseName'];
    $dbuname = $args['userName'];
    $dbpass = $args['password'];
    
    // Decode username and password if necessary
    if (1 == pnCore_getSystemVar('DB.Encoded')) {
        $dbuname = base64_decode($dbuname);
        $dbpass  = base64_decode($dbpass);
    }
    // ADODB configuration
    if (!defined('ADODB_DIR')) {
        define('ADODB_DIR', 'pnadodb');
    }
    
    include_once 'pnadodb/adodb.inc.php';

    // Database connection is a global (for now)
    global $dbconn;

    // Start connection
    $dbconn = ADONewConnection($dbtype);
    $dbh = $dbconn->Connect($dbhost, $dbuname, $dbpass, $dbname);
    if (!$dbh) {
        $dbpass = '';
        die("pnDB_init: Failed to connect to $dbtype://$dbuname:$dbpass@$dbhost/$dbname, error message: " . $dbconn->ErrorMsg());
    }
    global $ADODB_FETCH_MODE;
    $ADODB_FETCH_MODE = ADODB_FETCH_NUM;

    // force oracle to a consistent date format for comparison methods later on
    if (strcmp($dbtype, 'oci8') == 0) {
        $dbconn->Execute("alter session set NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
    }

    // Initialise pntables
    global $pntable, $prefix;
    $prefix = $args['systemTablePrefix'];
    $pntable = array();

    $systemPrefix = $args['systemTablePrefix'];
    $sitePrefix   = $args['siteTablePrefix'];
    // TODO: <marco> for now i'm leaving all the tables to use the system prefix
    //       which of them could be site prefixed?

    // Core tables
    

    // User System and Security Service Tables
    $pntable['realms']                = $systemPrefix . '_realms';
    $pntable['users']                 = $systemPrefix . '_users';
    $pntable['user_data']             = $systemPrefix . '_user_data';
    $pntable['user_perms']            = $systemPrefix . '_user_perms';
    $pntable['user_property']         = $systemPrefix . '_user_property';
    $pntable['groups']                = $systemPrefix . '_groups';
    $pntable['group_perms']           = $systemPrefix . '_group_perms';
    $pntable['group_membership']      = $systemPrefix . '_group_membership';

    // Session Support Tables
    $pntable['session_info']          = $systemPrefix . '_session_info';

    // Blocks Support Tables
    $pntable['blocks']                = $systemPrefix . '_blocks';
    $pntable['block_instances']       = $systemPrefix . '_block_instances';
    $pntable['block_groups']          = $systemPrefix . '_block_groups';
    $pntable['block_group_instances'] = $systemPrefix . '_block_group_instances';
    $pntable['block_types']           = $systemPrefix . '_block_types';

    // BlockLayout Template Engine Tables
    $pntable['template_tags']         = $systemPrefix . '_template_tags';

    // FIXME: <marco> I think that those tables are not part of core, and should go into
    //        their proper module
    $pntable['admin_menu']            = $systemPrefix . '_admin_menu';
        // FIXME: <marco> I don't need this in MLS, should we drop it?
    $pntable['languages']             = $systemPrefix . '_languages';
        // FIXME: <marco> Paul do we need it?
    $pntable['userblocks']            = $systemPrefix . '_userblocks';

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
function pnDBGetConn()
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
function pnDBGetTables()
{
    global $pntable;

    return $pntable;
}

/**
 * Load the Table Maintenance API
 *
 * @access public
 * @return true
 */
function pnDBLoadTableMaintenanceAPI()
{
    // Include Table Maintainance API file
    include_once 'includes/pnTableDDL.php';

    return true;
}

/**
 * Get the database host
 *
 * @access public
 * @returns string
 * @return database host
 */
function pnDBGetHost()
{
    global $pnDB_systemArgs;

    return $pnDB_systemArgs['databaseHost'];
}

/**
 * Get the database type
 *
 * @access public
 * @return string database type
 */
function pnDBGetType()
{
    global $pnDB_systemArgs;

    return $pnDB_systemArgs['databaseType'];
}

/**
 * Get the database name
 *
 * @access public
 * @return string database name
 */
function pnDBGetName()
{
    global $pnDB_systemArgs;

    return $pnDB_systemArgs['databaseName'];
}

/**
 * Get the system table prefix
 *
 * @access public
 * @return string database name
 */
function pnDBGetSystemTablePrefix()
{
    global $pnDB_systemArgs;

    return $pnDB_systemArgs['systemTablePrefix'];
}

/**
 * Get the site table prefix
 *
 * @access public
 * @return string database name
 */
function pnDBGetSiteTablePrefix()
{
    global $pnDB_systemArgs;

    return $pnDB_systemArgs['siteTablePrefix'];
}

// PROTECTED FUNCTIONS

/**
 * Import module tables in the array of known tables
 *
 * @access private
 * @return array
 */
function pnDB_importTables($tables)
{
    global $pntable;
    
    $pntable = array_merge($pntable, $tables);
}

?>
