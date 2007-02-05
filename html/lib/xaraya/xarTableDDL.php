<?php
/**
 * Table Maintenance API
 *
 * NOTE: THIS SUBSYSTEM IS SCHEDULED FOR DEPRECATION. EXISTING CODE
 * DEPENDS ON IT, THAT IS WHY IT IS HERE. IF YOU ARE WRITING NEW CODE
 * USE THE METHODS IN xarDataDict.php. BOTH SUBSYSTEMS ARE NOT 100% FINISHED
 * BUT THIS ONE WILL BE ABANDONED, YOU MIGHT AS WELL WRITE YOUR CODE TO USE
 * THE MAINTAINED SUBSYSTEM.
 
 * @package database
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage table_api
 * @author Gary Mitchell
 * @todo Check functions!
 * @todo Check FIXMEs
 * @todo Document functions
**/

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
 * @throws EmptyParameterException, BadParameterException
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDBCreateDatabase($databaseName, $databaseType = NULL)
{
    // perform validations on input arguments
    if (empty($databaseName)) throw new EmptyParameterException('databaseName');
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
        case 'mssql':
        case 'datadict':
            sys::import('tableddl.datadict');
            $sql = xarDB__datadictCreateDatabase($databaseName);
            break;
        // Other DBs go here
        default:
            throw new BadParameterException($databaseType,'Unknown database type: "#(1)"');
    }
    return $sql;

}

/**
 * Generate the SQL to create a table
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
 * @throws EmptyParameterException, BadParameterException
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDBCreateTable($tableName, $fields, $databaseType="")
{
    // perform validations on input arguments
    if (empty($tableName)) throw new EmptyParameterException('tableName');
    if (!is_array($fields)) throw new BadParameterException('fields','The #(1) parameter is not an array');
    if (empty($databaseType)) {
        $databaseType = xarDBGetType();
    }

    // Select the correct database type
    switch($databaseType) {
        case 'mysql':
            sys::import('tableddl.mysql');
            $sql = xarDB__mysqlCreateTable($tableName, $fields);
            break;
        case 'postgres':
            sys::import('tableddl.postgres');
            $sql = xarDB__postgresqlCreateTable($tableName, $fields);
            break;
        case 'oci8':
        case 'oci8po':
            sys::import('tableddl.oracle');
            $sql = xarDB__oracleCreateTable($tableName, $fields);
            break;
        case 'sqlite':
            sys::import('tableddl.sqlite');
            $sql = xarDB__sqliteCreateTable($tableName, $fields);
            break;
        case 'mssql':
        case 'datadict':
            sys::import('tableddl.datadict');
            $sql = xarDB__datadictCreateTable($tableName, $fields);
            break;
        // Other DBs go here
        default:
            throw new BadParameterException($databaseType,'Unknown database type: "#(1)"');
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
 * @throws EmptyParameterException, BadParameterException
 * @return string generated sql
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDBAlterTable($tableName, $args, $databaseType = NULL)
{
    // perform validations on input arguments
    if (empty($tableName)) throw new EmptyParameterException('tableName');
    if (!is_array($args) || !isset($args['command'])) {
        throw new BadParameterException('args','Invalid parameter "args", it must be an array, and the "command" key must be set');
    }

    if (empty($databaseType)) {
        $databaseType = xarDBGetType();
    }

    // Select the correct database type
    switch($databaseType) {
        case 'mysql':
            sys::import('tableddl.mysql');
            $sql = xarDB__mysqlAlterTable($tableName, $args);
            break;
        case 'postgres':
            sys::import('tableddl.postgres');
            $sql = xarDB__postgresqlAlterTable($tableName, $args);
            break;
        case 'oci8':
        case 'oci8po':
            sys::import('tableddl.oracle');
            $sql = xarDB__oracleAlterTable($tableName, $args);
            break;
        case 'sqlite':
            sys::import('tableddl.sqlite');
            $sql = xarDB__sqliteAlterTable($tableName, $args);
            break;
        case 'mssql':
        case 'datadict':
            sys::import('tableddl.datadict');
            $sql = xarDB__datadictAlterTable($tableName, $args);
            break;
        // Other DBs go here
        default:
            throw new BadParameterException($databaseType,'Unknown database type: "#(1)"');
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
    if (empty($tableName)) throw new EmptyParameterException('tableName');
    if (empty($databaseType)) {
        $databaseType = xarDBGetType();
    }

    switch($databaseType) {
        case 'postgres':
        case 'mysql':
        case 'oci8':
        case 'oci8po':
        case 'sqlite':
            $sql = 'DROP TABLE '.$tableName;
            break;
        case 'mssql':
        case 'datadict':
            sys::import('tableddl.datadict');
            $sql = xarDB__datadictDropTable($tableName);
            break;
        // Other DBs go here
        default:
            throw new BadParameterException($databaseType,'Unknown database type: "#(1)"');
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
 * @throws EmptyParameterException, BadParameterException
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDBCreateIndex($tableName, $index, $databaseType = NULL)
{

    // perform validations on input arguments
    if (empty($tableName)) throw new EmptyParameterException('tableName');
    if (!is_array($index) || !is_array($index['fields']) || empty($index['name'])) {
        throw new BadParameterException('index','The parameter "#(1)" must be an array, the "fields" key inside it must be an array and the "name" key must be set).');
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

        case 'mssql':
        case 'datadict':
            sys::import('tableddl.datadict');
            $sql = xarDB__datadictCreateIndex($tableName, $index);
            break;

        // Other DBs go here
        default:
            throw new BadParameterException($databaseType,'Unknown database type: "#(1)"');
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
 * @throws EmptyParameterException, BadParameterException
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDBDropIndex($tableName, $index, $databaseType = NULL)
{
    // perform validations on input arguments
    if (empty($tableName)) throw new EmptyParameterException('tableName');
    if (!is_array($index) ||  empty($index['name'])) {
        throw new BadParameterException('index','The parameter "#(1)" must be an array, the "fields" key inside it must be an array and the "name" key must be set).');
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
        case 'mssql':
        case 'datadict':
            sys::import('tableddl.datadict');
            $sql = xarDB__datadictDropIndex($tableName, $index);
            break;
        // Other DBs go here
        default:
            throw new BadParameterException($databaseType,'Unknown database type: "#(1)"');
    }
    return $sql;
}

?>
