<?php
/**
 * File: $Id$
 *
 * Database Performance using ADOdb Performance Monitor
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2004 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage database
 * @author Richard Cave <rcave@xaraya.com>
 */

// initialize the Xaraya core
include 'includes/xarCore.php';
xarCoreInit(XARCORE_SYSTEM_ALL);

// Fetch variables
if(!xarVarFetch('pollsecs','isset', $pollsecs, 5, XARVAR_NOT_REQUIRED)) {return;}
if(!xarVarFetch('logsql','isset', $logsql, 0, XARVAR_NOT_REQUIRED)) {return;}

session_start(); // session variables required for monitoring

// Get new database connection
$dbconn =& xarDBNewConn();


// Create logging table if it doesn't exist.
// For some reason LogSQL() is not creating the table...
if ($logsql) {
    // Load table maintenance API
    xarDBLoadTableMaintenanceAPI();

    // Create the table DDL
    $logsql = 'adodb_logsql';
    $fields = array(
        'created' => array('type'=>'timestamp','null'=>FALSE),
        'sql0'    => array('type'=>'varchar','size'=>250,'null'=>FALSE),
        'sql1'    => array('type'=>'text','null'=>FALSE),
        'params'  => array('type'=>'text','null'=>FALSE),
        'tracer'  => array('type'=>'text','null'=>FALSE),
        'timer'   => array('type'=>'float','size'=>'decimal','null'=>FALSE)
        );

    $query = xarDBCreateTable($logsql, $fields);
    if (empty($query)) return; // throw back

    // Pass the Table Create DDL to adodb to create the table
    $dbconn->Execute($query);

    // Check for an error with the database
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                    new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }
}

// Create new performance monitor
$perf =& NewPerfMonitor($dbconn);

// Poll monitor and display
$perf->UI($pollsecs);

?>
