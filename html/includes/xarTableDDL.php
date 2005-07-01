<?php
/**
 * File: $Id$
 *
 * Table Maintenance API
 *
 * NOTE: THIS SUBSYSTEM IS SCHEDULED FOR DEPRECATION. EXISTING CODE
 * DEPENDS ON IT, THAT IS WHY IT IS HERE. IF YOU ARE WRITING NEW CODE
 * USE THE METHODS IN xarDataDict.php. BOTH SUBSYSTEMS ARE NOT 100% FINISHED
 * BUT THIS ONE WILL BE ABANDONED, YOU MIGHT AS WELL WRITE YOUR CODE TO USE
 * THE MAINTAINED SUBSYSTEM.
 
 * @package database
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage table_api
 * @author Gary Mitchell
 * @todo Check functions!
 *       Check FIXMEs
 *       Document functions
 */

/**
 * Public Functions:
 *
 * xarDBCreateDatabase($databaseName, $databaseType = NULL)
 * xarDBCreateTable($tableName, $fields, $databaseType = NULL)
 * xarDBDropTable($tableName, $databaseType = NULL)
 * xarDBAlterTable($tableName, $args, $databaseType = NULL)
 * xarDBCreateIndex($tableName, $index, $databaseType = NULL)
 * xarDBDropIndex($tableName, $databaseType = NULL)
 *
 */

/**
 * Generate the SQL to create a database
 *
 * @access public
 * @param databaseName
 * @param databaseType
 * @return string sql statement for database creation
 * @raise BAD_PARAM
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDBCreateDatabase($databaseName, $databaseType = NULL)
{
    // perform validations on input arguments
    if (empty($databaseName)) {
        $msg = xarML('Empty database_name.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }
    if (empty($databaseType)) {
        $databaseType = xarDBGetType();
    }

    switch($databaseType) {
        case 'mysql':
        case 'oci8':
        case 'oci8po':
            $sql = 'CREATE DATABASE '.$databaseName;
            break;
        case 'postgres':
            $sql = 'CREATE DATABASE "'.$databaseName .'"';
            break;
         case 'sqlite':
            // No such thing, its created automatically when it doesnt exist
            $sql ='';
            break;
        case 'datadict':
            include_once('includes/tableddl/datadict.php');
            $sql = xarDB__datadictCreateDatabase($databaseName);
            break;
        // Other DBs go here
        default:
            $msg = xarML('Unknown database type: \'#(1)\'.', $databaseType);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return;
    }
    return $sql;

}

/**
 * Generate the SQL to create a table
 *
 * @access public
 * @param tableName the physical table name
 * @param fields an array containing the fields to create
 * @param databaseType database type (optional)
 * @return string|false the generated SQL statement, or false on failure
 */
function xarDBCreateTable($tableName, $fields, $databaseType="")
{
    // perform validations on input arguments
    if (empty($tableName)) {
        $msg = xarML('Empty tableName.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }
    if (!is_array($fields)) {
        $msg = xarML('Not array fields.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    if (empty($databaseType)) {
        $databaseType = xarDBGetType();
    }

    // save table definition
    $systemPrefix = xarDBGetSystemTablePrefix();
    $metaTable = $systemPrefix . '_tables';
    if ($tableName != $metaTable) {
        $dbconn =& xarDBGetConn();
        while (list($field_name, $parameters) = each($fields)) {
            $nextId = $dbconn->GenId($metaTable);
            $query = "INSERT INTO $metaTable (
                      xar_tableid, xar_table, xar_field,  xar_type,
                      xar_size,  xar_default, xar_null, xar_unsigned,
                      xar_increment, xar_primary_key)
                    VALUES (?,?,?,?,?,?,?,?,?,?)";
            if (!isset($parameters['default'])) {
                $defaultval = '';
            } elseif (is_string($parameters['default'])) {
                $defaultval = $parameters['default'];
            } else {
                $defaultval = serialize($parameters['default']);
            }
            $bindvars = array($nextId,$tableName,$field_name,
                              (empty($parameters['type']) ? '' : $parameters['type']),
                              (empty($parameters['size']) ? '' : $parameters['size']),
                              $defaultval,
                              (empty($parameters['null']) ? '0' : '1'),
                              (empty($parameters['unsigned']) ? '0' : '1'),
                              (empty($parameters['increment']) ? '0' : '1'),
                              (empty($parameters['primary_key']) ? '0' : '1'));
                  //    xar_width,
                  //    xar_decimals,
            $result =& $dbconn->Execute($query,$bindvars);
        }
    }

    // Select the correct database type
    switch($databaseType) {
        case 'mysql':
            include_once('includes/tableddl/mysql.php');
            $sql = xarDB__mysqlCreateTable($tableName, $fields);
            break;
        case 'postgres':
            include_once('includes/tableddl/postgres.php');
            $sql = xarDB__postgresqlCreateTable($tableName, $fields);
            break;
        case 'oci8':
        case 'oci8po':
            include_once('includes/tableddl/oracle.php');
            $sql = xarDB__oracleCreateTable($tableName, $fields);
            break;
        case 'sqlite':
            include_once('includes/tableddl/sqlite.php');
            $sql = xarDB__sqliteCreateTable($tableName, $fields);
            break;
        case 'datadict':
            include_once('includes/tableddl/datadict.php');
            $sql = xarDB__datadictCreateTable($tableName, $fields);
            break;
        // Other DBs go here
        default:
            $msg = xarML('Unknown database type: \'#(1)\'.', $databaseType);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return;
    }
    return $sql;
}

/**
 * Alter database table
 *
 * @access public
 * @param tableName the table to alter
 * @param args['command'] command to perform on table(add,modify,drop,rename)
 * @param args['field'] name of column to alter
 * @param args['type'] column type
 * @param args['size'] size of column if varying data
 * @param args['default'] default value of data
 * @param args['null'] null or not null (true/false)
 * @param args['unsigned'] allow unsigned data (true/false)
 * @param args['increment'] auto incrementing files
 * @param args['primary_key'] primary key
 * @param databaseType the database type (optional)
 * @return string generated sql
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDBAlterTable($tableName, $args, $databaseType = NULL)
{
    // perform validations on input arguments
    if (empty($tableName)) {
        $msg = xarML('Empty tableName.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }
    if (!is_array($args) || !isset($args['command'])) {
        $msg = xarML('Invalid args (must be an array, command key must be set).');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    if (empty($databaseType)) {
        $databaseType = xarDBGetType();
    }

    // save table definition
    if (isset($args['command']) && $args['command'] == 'add') {
        $systemPrefix = xarDBGetSystemTablePrefix();
        $metaTable = $systemPrefix . '_tables';

        $dbconn =& xarDBGetConn();
        $nextId = $dbconn->GenId($metaTable);
        $query = "INSERT INTO $metaTable (
                      xar_tableid, xar_table, xar_field, xar_type,
                      xar_size,  xar_default, xar_null,  xar_unsigned,
                      xar_increment, xar_primary_key)
                    VALUES (?,?,?,?,?,?,?,?,?,?)";
        if (!isset($parameters['default'])) {
            $defaultval = '';
        } elseif (is_string($parameters['default'])) {
            $defaultval = $parameters['default'];
        } else {
            $defaultval = serialize($parameters['default']);
        }
        $bindvars = array($nextId,$tableName,$args['field'],
                          (empty($args['type']) ? '' : $args['type']),
                          (empty($args['size']) ? '' : $args['size']),
                          $defaultval,
                          (empty($args['null']) ? '0' : '1'),
                          (empty($args['unsigned']) ? '0' : '1'),
                          (empty($args['increment']) ? '0' : '1'),
                          (empty($args['primary_key']) ? '0' : '1'));
                  //    xar_width,
                  //    xar_decimals,
        $result =& $dbconn->Execute($query,$bindvars);

    } elseif (isset($args['command']) && $args['command'] == 'rename') {

        $systemPrefix = xarDBGetSystemTablePrefix();
        $metaTable = $systemPrefix . '_tables';

        $dbconn =& xarDBGetConn();
        $nextId = $dbconn->GenId($metaTable);
        $query = "UPDATE $metaTable SET xar_table = ? WHERE xar_table = ?";
        $bindvars = array((string) $args['new_name'], (string) $tableName);
        $result =& $dbconn->Execute($query,$bindvars);
    }

    // Select the correct database type
    switch($databaseType) {
        case 'mysql':
            include_once('includes/tableddl/mysql.php');
            $sql = xarDB__mysqlAlterTable($tableName, $args);
            break;
        case 'postgres':
            include_once('includes/tableddl/postgres.php');
            $sql = xarDB__postgresqlAlterTable($tableName, $args);
            break;
        case 'oci8':
        case 'oci8po':
            include_once('includes/tableddl/oracle.php');
            $sql = xarDB__oracleAlterTable($tableName, $args);
            break;
        case 'sqlite':
            include_once('includes/tableddl/sqlite.php');
            $sql = xarDB__sqliteAlterTable($tableName, $args);
            break;
        case 'datadict':
            include_once('includes/tableddl/datadict.php');
            $sql = xarDB__datadictAlterTable($tableName, $args);
            break;
        // Other DBs go here
        default:
            $msg = xarML('Unknown database type: \'#(1)\'.', $databaseType);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return;
    }
    return $sql;
}

/**
 * Generate the SQL to delete a table
 *
 * @access public
 * @param tableName the physical table name
 * @param index an array containing the index name, type and fields array
 * @return data|false the generated SQL statement, or false on failure
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDBDropTable($tableName, $databaseType = NULL)
{
    // perform validations on input arguments
    if (empty($tableName)) {
        $msg = xarML('Empty tableName.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }
    if (empty($databaseType)) {
        $databaseType = xarDBGetType();
    }

    // remove table definition
    $systemPrefix = xarDBGetSystemTablePrefix();
    $metaTable = $systemPrefix . '_tables';
    if ($tableName != $metaTable) {
        $dbconn =& xarDBGetConn();
        $query = "DELETE FROM $metaTable WHERE xar_table=?";
        $result =& $dbconn->Execute($query,array($tableName));
    }

    switch($databaseType) {
        case 'mysql':
        case 'postgres':
        case 'oci8':
        case 'oci8po':
        case 'sqlite':
            $sql = 'DROP TABLE '.$tableName;
            break;
        case 'datadict':
            include_once('includes/tableddl/datadict.php');
            $sql = xarDB__datadictDropTable($tableName);
            break;
        // Other DBs go here
        default:
            $msg = xarML('Unknown database type: \'#(1)\'.', $databaseType);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return;
    }
    return $sql;

}

/**
 * Generate the SQL to create a table index
 *
 * @param tableName the physical table name
 * @param index an array containing the index name, type and fields array
 * @param databaseType is an optional parameter to specify the database type
 * @return string|false the generated SQL statement, or false on failure
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDBCreateIndex($tableName, $index, $databaseType = NULL)
{

    // perform validations on input arguments
    if (empty($tableName)) {
        $msg = xarML('Empty tableName.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }
    if (!is_array($index) || !is_array($index['fields']) || empty($index['name'])) {
        $msg = xarML('Invalid index (must be an array, fields key must be an array, name key must be set).');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }
    // default for unique
    if (!isset($index['unique'])) {
        $index['unique'] = false;
    }

    if (empty($databaseType)) {
        $databaseType = xarDBGetType();
    }

    // Select the correct database type
    switch($databaseType) {
        case 'mysql':
            if ($index['unique'] == true) {
                $sql = 'ALTER TABLE '.$tableName.' ADD UNIQUE '.$index['name'];
            } else {
                $sql = 'ALTER TABLE '.$tableName.' ADD INDEX '.$index['name'];
            }
            $sql .= ' ('.join(',', $index['fields']).')';
            break;
        case 'postgres':
        case 'oci8':
        case 'oci8po':
        case 'sqlite':
            if ($index['unique'] == true) {
                $sql = 'CREATE UNIQUE INDEX '.$index['name'].' ON '.$tableName;
            } else {
                $sql = 'CREATE INDEX '.$index['name'].' ON '.$tableName;
            }
            $sql .= ' ('.join(',', $index['fields']).')';
            break;

        case 'datadict':
            include_once('includes/tableddl/datadict.php');
            $sql = xarDB__datadictCreateIndex($tableName, $index);
            break;

        // Other DBs go here
        default:
            $msg = xarML('Unknown database type: \'#(1)\'.', $databaseType);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return;
    }
    return $sql;
}
/**
 * Generate the SQL to drop an index
 *
 * @access public
 * @param tableName
 * @param name a db index name
 * @param databaseType
 * @return string|false generated sql to drop an index
 * @raise BAD_PARAM
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDBDropIndex($tableName, $index, $databaseType = NULL)
{
    // perform validations on input arguments
    if (empty($tableName)) {
        $msg = xarML('Empty tableName.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }
    if (!is_array($index) ||  empty($index['name'])) {
        $msg = xarML('Invalid index (must be an array, fields key must be an array, name key must be set).');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }
    if (empty($databaseType)) {
        $databaseType = xarDBGetType();
    }

    // Select the correct database type
    switch($databaseType) {
        case 'mysql':
            $sql = 'ALTER TABLE '.$tableName.' DROP INDEX '.$index['name'];
            break;
        case 'postgres':
        case 'oci8':
        case 'oci8po':
        case 'sqlite':
            $sql = 'DROP INDEX '.$index['name'];
            break;
        case 'datadict':
            include_once('includes/tableddl/datadict.php');
            $sql = xarDB__datadictDropIndex($tableName, $index);
            break;
        // Other DBs go here
        default:
            $msg = xarML('Unknown database type: \'#(1)\'.', $databaseType);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return;
    }
    return $sql;
}

?>
