<?php
/**
 * File: $Id$
 *
 * Database Insert Tool
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

// Get new database connection
$dbconn =& xarDBNewConn();

// Get a data dictionary object with item create methods.
$datadict =& xarDBNewDataDict($dbconn);

// Fetch variables
if (!xarVarFetch('rows','int:0:', $rows, 0)) return;
if (!xarVarFetch('table','str:1:', $table, '')) return;
if (!xarVarFetch('debug','checkbox', $debug, 1)) return;

if (empty($table)) {
    print("No table was selected.  Go back and select a table.");
    exit();
} else {
    $columns = $datadict->getColumns($table);

    // Keys array
    $keys = array();

    // Generate INSERT statement based on datatype
    for ($ins = 0; $ins < $rows; $ins++) {
        $names = array();
        $values = array();

        // Loop through each column datatype
        foreach($columns as $column) {
            // Verify that key count exists
            if (!isset($keys[$column->name])) {
                $keys[$column->name] = 1;
            }

            // Switch by column type
            switch ($column->type) {
                case 'smallint':
                case 'integer':
                case 'int2':
                case 'int4':
                case 'int8':
                    if (isset($column->primary_key)) {
                        $names[] = $column->name;
                        $values[] = $keys[$column->name];
                        // Increment keys count
                        $keys[$column->name] = $keys[$column->name] + 1;
                    } else {
                        $names[] = $column->name;
                        if ($column->type == 'smallint' || $column->type == 'int2') {
                            $values[] = rand(1,32767);
                        } else {
                            $values[] = rand();
                        }
                    }
                break;

                case 'float8':
                break;

                case 'numeric':
                break;
        
                case 'bytea':
                break;

                case 'date':
                case 'timestamp':
                break;

                case 'varchar':
                break;

                case 'text':
                    $names[] = $column->name;
                    $values[] = "'".$column->name."_".$keys[$column->name]."'";
                    // Increment keys count
                    $keys[$column->name] = $keys[$column->name] + 1;
                break;
            }
        }
    
        // Create INSERT statement
        $insertSQL = "INSERT INTO $table (".implode(',',$names).") values (".implode(',',$values).");<br />";
    
        if ($debug) {
            print $insertSQL;
        } else {
            // Execute INSERT statement
            $result =& $dbconn->Execute($query);

            // Check for an error
            if (!$result) {
                print("ERROR: $insertSQL");
            } 
        }
    }
}

?>
