<?php
// $Id$
// ----------------------------------------------------------------------
// PostNuke Content Management System
// Copyright (C) 2001 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
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

    // ADODB configuration
    define('ADODB_DIR', 'pnadodb');
    include 'pnadodb/adodb.inc.php';

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
 * loads the Table Maintenance API sub-system
 *
 * @access public
 * @return true
 */
function pnDBLoadTableMaintenanceAPI()
{
    include_once 'includes/pnTableDDL.php';

    return true;
}

/**
 * Gets the database host
 *
 * @access public
 * @return database host
 */
function pnDBGetHost()
{
    global $pnDB_systemArgs;;

    return $pnDB_systemArgs['databaseHost'];
}

/**
 * Gets the database type
 *
 * @access public
 * @return database type
 */
function pnDBGetType()
{
    global $pnDB_systemArgs;;

    return $pnDB_systemArgs['databaseType'];
}

/**
 * Gets the database name
 *
 * @access public
 * @return database name
 */
function pnDBGetName()
{
    global $pnDB_systemArgs;;

    return $pnDB_systemArgs['databaseName'];
}

/**
 * Gets the system table prefix
 *
 * @access public
 * @return database name
 */
function pnDBGetSystemTablePrefix()
{
    global $pnDB_systemArgs;;

    return $pnDB_systemArgs['systemTablePrefix'];
}

/**
 * Gets the site table prefix
 *
 * @access public
 * @return database name
 */
function pnDBGetSiteTablePrefix()
{
    global $pnDB_systemArgs;;

    return $pnDB_systemArgs['siteTablePrefix'];
}

// PROTECTED FUNCTIONS

/**
 * Imports module tables in the array of known tables
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
