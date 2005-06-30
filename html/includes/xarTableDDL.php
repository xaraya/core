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

/*
$sql = xarDBAlterTable($xartable['nascar_tracks'],
    array(
        'command'           => 'add',
        'field'             => 'xar_track_name',
        'type'              => 'integer',
        'unsigned'          => false,
        'null'              => false,
        'increment'         => true,
        'primary_key'       => true,
    )
);
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
            $sql = xarDB__mysqlAlterTable($tableName, $args);
            break;
        case 'postgres':
            $sql = xarDB__postgresqlAlterTable($tableName, $args);
            break;
        case 'oci8':
        case 'oci8po':
            $sql = xarDB__oracleAlterTable($tableName, $args);
            break;
        case 'sqlite':
            $sql = xarDB__sqliteAlterTable($tableName, $args);
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
            $sql = xarDB__mysqlCreateTable($tableName, $fields);
            break;
        case 'postgres':
            $sql = xarDB__postgresqlCreateTable($tableName, $fields);
            break;
        case 'oci8':
        case 'oci8po':
            $sql = xarDB__oracleCreateTable($tableName, $fields);
            break;
        case 'sqlite':
            $sql = xarDB__sqliteCreateTable($tableName, $fields);
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
        // Other DBs go here
        default:
            $msg = xarML('Unknown database type: \'#(1)\'.', $databaseType);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return;
    }
    return $sql;
}

// PRIVATE FUNCTIONS BELOW - do not call directly

/**
 * Mysql specific function to alter a table
 *
 * @access private
 * @param tableName the table to alter
 * @param args['command'] command to perform on the table
 * @param args['field'] name of column to modify
 * @param args['after_field']
 * @param args['new_name'] new name of table
 * @return string|false mysql specific sql to alter a table
 * @raise BAD_PARAM
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDB__mysqlAlterTable($tableName, $args)
{
    switch ($args['command']) {
        case 'add':
            if (empty($args['field'])) {
                $msg = xarML('Invalid args (field key must be set).');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }
       // TODO: adapt mysqlColumnDefinition to return field name too
            $sql = 'ALTER TABLE '.$tableName.' ADD '.$args['field'].' ';
            $coldef = xarDB__mysqlColumnDefinition($args['field'],$args);
            $sql .= $coldef['type'] . ' '
                . $coldef['unsigned'] . ' '
                . $coldef['null'] . ' '
                . $coldef['default'] . ' '
                . $coldef['auto_increment'] . ' ';

            if($coldef['primary_key']) {
                $sql.= 'PRIMARY KEY ';
            }
            //$sql .= join(' ', xarDB__mysqlColumnDefinition($args['field'], $args));
            if (!empty($args['first']) && $args['first'] == true) {
                $sql .= ' FIRST';
            } elseif (!empty($args['after_field'])) {
                $sql .= ' AFTER '.$args['after_field'];
            }

            // Add table options, if any
            // FIXME: when the callee was more sensible, we could simplify this
            if(array_key_exists('increment_start',$coldef)) {
                if($coldef['increment_start'] > 0) {
                    $sql.= 'AUTO_INCREMENT=' .$coldef['increment_start'] . ' ';
                }
            }
            break;
        case 'rename':
            if (empty($args['new_name'])) {
                $msg = xarML('Invalid args (new_name key must be set.)');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }
            $sql = 'ALTER TABLE '.$tableName.' RENAME TO '.$args['new_name'];
            break;
        case 'modify':

            // ************************* TO DO TO DO *************************
            // this modify case ONLY adds or drops NULL to a column.  All other functionality
            // per the below args needs to be added
            // 11.30.04 - mrjones - ajones@schwabfoundation.org
            // ************************* TO DO TO DO *************************


            // We need to account for all the possible args that are passed:
            // * @param args['type'] column type
            // * @param args['size'] size of column if varying data
            // * @param args['default'] default value of data
            // * @param args['null'] null or not null (true/false)
            // * @param args['unsigned'] allow unsigned data (true/false)
            // * @param args['increment'] auto incrementing files
            // * @param args['primary_key'] primary key

            // make sure we have the colunm we're altering
            if (empty($args['field'])) {
                $msg = xarML('Invalid args (field key must be set).');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }
            // check to make sure we have an action to perform on the colunm
            if (!empty($args['type']) || !empty($args['size']) || !empty($args['default']) || !empty($args['unsigned']) || !empty($args['increment']) || !empty($args['primary_key'])) {
                $msg = xarML('Modify does not currently support: type, size, default, unsigned, increment, or primary_key)');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }

            // check to make sure we have an action to perform on the colunm
            if (empty($args['null']) && $args['null']!=FALSE) {
                $msg = xarML('Invalid args (type,size,default,null, unsigned, increment, or primary_key must be set)');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }
            // prep the first part of the query
            $sql = 'ALTER TABLE `'.$tableName.'` MODIFY `'.$args['field'].'` ';

            //since we don't allow type to be passed, check the db for type and derive type from
            // the existing schema. Also b/c the fetch mode may or may not be set to NUM, set it to
            // ASSOC so we don't have to loop through the entire returned array looking for are our one
            // field and field type
            $dbconn =& xarDBGetConn();
            $GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_ASSOC;
            $tableInfoArray = $dbconn->metacolumns($tableName);
            $GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_NUM;
            if (!empty($tableInfoArray[strtoupper($args['field'])]->type)){
                $sql.=$tableInfoArray[strtoupper($args['field'])]->type;
            }
            if (!empty($tableInfoArray[strtoupper($args['field'])]->max_length) && $tableInfoArray[strtoupper($args['field'])]->max_length!="-1"){
                $sql.='('.$tableInfoArray[strtoupper($args['field'])]->max_length.')';
            }

            // see if the want to add null
            if ($args['null']==TRUE){
                $sql.=' NOT NULL ';
            }

            // break out of the case to return the modify sql
            break;
        default:
            $msg = xarML('Unknown command: \'#(1)\'.', $args['command']);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return;
    }

    return $sql;
}

/**
 * Postgres specific function to alter a table
 *
 * @access private
 * @param tableName the table to alter
 * @param args['command'] command to perform on the table
 * @param args['field'] name of column to modify
 * @param args['new_name'] new name of table
 * @return string|false postgres specific sql to alter a table
 * @raise BAD_PARAM
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDB__postgresqlAlterTable($tableName, $args)
{
    switch ($args['command']) {
        case 'add':
            if (empty($args['field'])) {
                $msg = xarML('Invalid args (field key must be set).');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }
            $sql = 'ALTER TABLE '.$tableName.' ADD '.$args['field'].' ';
            // Get column definitions
            $this_field = xarDB__postgresColumnDefinition($args['field'], $args);
            // Add column values if they exist
            // Note:  PostgreSQL does not support default or null values in ALTER TABLE
            $sqlDDL = "";
            if (array_key_exists("type", $this_field))
                $sqlDDL = $sqlDDL . ' ' . $this_field['type'];
            $sql .= $sqlDDL;
            break;
        case 'rename':
            if (empty($args['new_name'])) {
                $msg = xarML('Invalid args (new_name key must be set.)');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }
            $sql = 'ALTER TABLE '.$tableName.' RENAME TO '.$args['new_name'];
            break;
        case 'modify':

            // ************************* TO DO TO DO *************************
            // this modify case ONLY adds or drops NULL to a column.  All other functionality
            // per the below args needs to be added
            // 11.30.04 - mrjones - ajones@schwabfoundation.org
            // ************************* TO DO TO DO *************************


            // We need to account for all the possible args that are passed:
            // * @param args['type'] column type
            // * @param args['size'] size of column if varying data
            // * @param args['default'] default value of data
            // * @param args['null'] null or not null (true/false)
            // * @param args['unsigned'] allow unsigned data (true/false)
            // * @param args['increment'] auto incrementing files
            // * @param args['primary_key'] primary key

            // make sure we have the colunm we're altering
            if (empty($args['field'])) {
                $msg = xarML('Invalid args (field key must be set).');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }
            // check to make sure we have an action to perform on the colunm
            if (!empty($args['type']) || !empty($args['size']) || !empty($args['default']) || !empty($args['unsigned']) || !empty($args['increment']) || !empty($args['primary_key'])) {
                $msg = xarML('Modify does not currently support: type, size, default, unsigned, increment, or primary_key)');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return $msg;
            }

            // check to make sure we have an action to perform on the colunm
            if (empty($args['null']) && $args['null']!=FALSE) {
                $msg = xarML('Invalid args (type,size,default,null, unsigned, increment, or primary_key must be set)');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }

            // prep the first part of the query
            $sql = 'ALTER TABLE '.$tableName.' ALTER COLUMN '.$args['field'].' ';

            // see if the want to add or remove null
            if ($args['null']==FALSE){
                $sql.='DROP NOT NULL';
            }
            if ($args['null']==TRUE){
                $sql.='SET NOT NULL';
            }

            // break out of the case to return the modify sql
            break;
        default:
            $msg = xarML('Unknown command: \'#(1)\'.', $args['command']);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return;
    }
    return $sql;
}

/**
 * Oracle specific function to alter a table
 *
 * @access private
 * @param tableName the table to alter
 * @param args['command'] command to perform on the table
 * @param args['field'] name of column to modify
 * @param args['new_name'] new name of table
 * @return string|false oracle specific sql to alter a table
 * @raise BAD_PARAM
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDB__oracleAlterTable($tableName, $args)
{
    switch ($args['command']) {
        case 'add':
            if (empty($args['field'])) {
                $msg = xarML('Invalid args (field key must be set).');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }
            $sql = 'ALTER TABLE '.$tableName.' ADD '.$args['field'].' ';
            // Get column definitions
            $this_field = xarDB__oracleColumnDefinition($args['field'], $args);
            // Add column values if they exist
            // Note:  Oracle does not support null values in ALTER TABLE
            $sqlDDL = "";
            if (array_key_exists("type", $this_field))
                $sqlDDL = $sqlDDL . ' ' . $this_field['type'];
            if (array_key_exists("default", $this_field))
                $sqlDDL = $sqlDDL . ' ' . $this_field['default'];
            $sql .= $sqlDDL;
            break;
        case 'rename':
            if (empty($args['new_name'])) {
                $msg = xarML('Invalid args (new_name key must be set.)');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }
            $sql = 'ALTER TABLE '.$tableName.' RENAME TO '.$args['new_name'];
            break;
        case 'modify':

            // ************************* TO DO TO DO *************************
            // this modify case ONLY adds or drops NULL to a column.  All other functionality
            // per the below args needs to be added
            // 11.30.04 - mrjones - ajones@schwabfoundation.org
            // ************************* TO DO TO DO *************************


            // We need to account for all the possible args that are passed:
            // * @param args['type'] column type
            // * @param args['size'] size of column if varying data
            // * @param args['default'] default value of data
            // * @param args['null'] null or not null (true/false)
            // * @param args['unsigned'] allow unsigned data (true/false)
            // * @param args['increment'] auto incrementing files
            // * @param args['primary_key'] primary key

            // make sure we have the colunm we're altering
            if (empty($args['field'])) {
                $msg = xarML('Invalid args (field key must be set).');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }
            // check to make sure we have an action to perform on the colunm
            if (!empty($args['type']) || !empty($args['size']) || !empty($args['default']) || !empty($args['unsigned']) || !empty($args['increment']) || !empty($args['primary_key'])) {
                $msg = xarML('Modify does not currently support: type, size, default, unsigned, increment, or primary_key)');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }

            // check to make sure we have an action to perform on the colunm
            if (empty($args['null']) && $args['null']!=FALSE) {
                $msg = xarML('Invalid args (type,size,default,null, unsigned, increment, or primary_key must be set)');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }
            // prep the first part of the query
            $sql = 'ALTER TABLE '.$tableName.' MODIFY ('.$args['field'].' ';

            //since we don't allow type to be passed, check the db for type and derive type from
            // the existing schema. Also b/c the fetch mode may or may not be set to NUM, set it to
            // ASSOC so we don't have to loop through the entire returned array looking for are our one
            // field and field type
            $dbconn =& xarDBGetConn();
            $GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_ASSOC;
            $tableInfoArray = $dbconn->metacolumns($tableName);
            $GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_NUM;
            if (!empty($tableInfoArray[strtoupper($args['field'])]->type)){
                $sql.=$tableInfoArray[strtoupper($args['field'])]->type;
            }
            if (!empty($tableInfoArray[strtoupper($args['field'])]->max_length) && $tableInfoArray[strtoupper($args['field'])]->max_length!="-1"){
                $sql.='('.$tableInfoArray[strtoupper($args['field'])]->max_length.')';
            }

            // see if the want to add null
            if ($args['null']==FALSE){
                $sql.=' NULL ';
            }
            if ($args['null']==TRUE){
                $sql.=' NOT NULL ';
            }

            // add on closing paren
            $sql.=")";

            // break out of the case to return the modify sql
            break;
        default:
            $msg = xarML('Unknown command: \'#(1)\'.', $args['command']);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return;
    }
    return $sql;
}

function xarDB__sqliteAlterTable($tableName, $args) 
{
    switch ($args['command']) {
        case 'add':
            if (empty($args['field'])) {
                $msg = xarML('Invalid args (field key must be set).');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }
           
            $sql = 'ALTER TABLE '.$tableName.' ADD '.$args['field'].' ';
            $coldef = xarDB__sqliteColumnDefinition($args['field'],$args);
            $sql.= $coldef['type'] . ' '
                . $coldef['unsigned'] . ' '
                . $coldef['null'] . ' '
                . $coldef['default'] . ' '
                . $coldef['auto_increment'] . ' ';

            if($coldef['primary_key']) {
                $sql.= 'PRIMARY KEY ';
            }   

            break;
        case 'rename':
            if (empty($args['new_name'])) {
                $msg = xarML('Invalid args (new_name key must be set.)');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                    new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }
            $sql = 'ALTER TABLE '.$tableName.' RENAME TO '.$args['new_name'];
            break;
        default:
            $msg = xarML('Unknown command: \'#(1)\'.', $args['command']);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                    new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return;
        }

        return $sql;
}

/**
 * Generate the MySQL specific SQL to create a table
 *
 * @access private
 * @param tableName the physical table name
 * @param fields an array containing the fields to create
 * @return string|false the generated SQL statement, or false on failure
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDB__mysqlCreateTable($tableName, $fields)
{
    $sql_fields = array();
    $primary_key = array();
    $increment_start = false;

    while (list($field_name, $parameters) = each($fields)) {
        $parameters['command'] = 'create';
        $this_field = xarDB__mysqlColumnDefinition($field_name, $parameters);

        $sql_fields[] = $field_name .' '
                      . $this_field['type'] .' '
                      . $this_field['unsigned'] .' '
                      . $this_field['null'] .' '
                      . $this_field['default'] .' '
                      . $this_field['auto_increment'];
        if ($this_field['primary_key'] == true) {
            $primary_key[] = $field_name;
        }
        if (empty($this_field['increment_start'])) {
            $this_field['increment_start'] = false;
        }
        if ($this_field['increment_start'] != false) {
            $increment_start = $this_field['increment_start'];
        }
    }

    // judgej: I would question this; the function should only be
    // creating DDL to return, not executing it.
    // There are instances when we don't want to drop the table, but
    // look for the exception to know the table has been created.
    $dbconn =& xarDBGetConn();
    $query = 'DROP TABLE IF EXISTS ' . $tableName;
    // CHECKME: Do we want to use bind vars here?
    $result =& $dbconn->Execute($query);

    $sql = 'CREATE TABLE '.$tableName.' ('.implode(', ',$sql_fields);
    if (!empty($primary_key)) {
        $sql .= ', PRIMARY KEY ('.implode(',',$primary_key).')';
    }

    $sql .= ')';

    // Bug #744 - Check "increment_start" field so that MySQL increment field will start at the appropriate startid
    if ($increment_start) {
        $sql .= ' AUTO_INCREMENT=' . $increment_start;
    }

    return $sql;
}

/**
 * Mysql specific column type generation
 *
 * @access private
 * @param field_name
 * @param parameters
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDB__mysqlColumnDefinition($field_name, $parameters)
{
    $this_field = array();

    switch($parameters['type']) {

        case 'integer':
            if (empty($parameters['size'])) {
                $parameters['size'] = 'int';
            }
            switch ($parameters['size']) {
                case 'tiny':
                    $this_field['type'] = 'TINYINT';
                    break;
                case 'small':
                    $this_field['type'] = 'SMALLINT';
                    break;
                case 'medium':
                    $this_field['type'] = 'MEDIUMINT';
                    break;
                case 'big':
                    $this_field['type'] = 'BIGINT';
                    break;
                default:
                    $this_field['type'] = 'INTEGER';
            } // switch ($parameters['size'])
            break;

        case 'char':
            if (empty($parameters['size'])) {
                return false;
            } else {
                $this_field['type'] = 'CHAR('.$parameters['size'].')';
            }
            break;

        case 'varchar':
            if (empty($parameters['size'])) {
                return false;
            } else {
                $this_field['type'] = 'VARCHAR('.$parameters['size'].')';
            }
            break;

        case 'text':
            if (empty($parameters['size'])) {
                $parameters['size'] = 'text';
            }
            switch ($parameters['size']) {
                case 'tiny':
                    $this_field['type'] = 'TINYTEXT';
                    break;
                case 'medium':
                    $this_field['type'] = 'MEDIUMTEXT';
                    break;
                case 'long':
                    $this_field['type'] = 'LONGTEXT';
                    break;
                default:
                    $this_field['type'] = 'TEXT';
            }
            break;

        case 'blob':
            if (empty($parameters['size'])) {
                $parameters['size'] = 'blob';
            }
            switch ($parameters['size']) {
                case 'tiny':
                    $this_field['type'] = 'TINYBLOB';
                    break;
                case 'medium':
                    $this_field['type'] = 'MEDIUMBLOB';
                    break;
                case 'long':
                    $this_field['type'] = 'LONGBLOB';
                    break;
                default:
                    $this_field['type'] = 'BLOB';
            }
            break;

        case 'boolean':
            $this_field['type'] = "BOOL";
            break;

        case 'datetime':
            $this_field['type'] = "DATETIME";
            if (isset($parameters['default'])) {
                // Check if this is an array and convert back to string
                // array('year'=>2002,'month'=>04,'day'=>17,'hour'=>'12','minute'=>59,'second'=>0)
                if (is_array($parameters['default'])) {
                    $datetime_defaults = $parameters['default'];
                    $parameters['default'] = $datetime_defaults['year'].
                                         '-'.$datetime_defaults['month'].
                                         '-'.$datetime_defaults['day'].
                                         ' '.$datetime_defaults['hour'].
                                         ':'.$datetime_defaults['minute'].
                                         ':'.$datetime_defaults['second'];
                }
            }
            break;

        case 'date':
            $this_field['type'] = "DATE";
            if (isset($parameters['default'])) {
                // Check if this is an array and convert back to string
                // array('year'=>2002,'month'=>04,'day'=>17)
                if (is_array($parameters['default'])) {
                    $datetime_defaults = $parameters['default'];
                    $parameters['default'] = $datetime_defaults['year'].
                                         '-'.$datetime_defaults['month'].
                                         '-'.$datetime_defaults['day'];
                }
            }
            break;

        case 'float':
            if (empty($parameters['size'])) {
                $parameters['size'] = 'float';
            }
            switch ($parameters['size']) {
                case 'double':
                    $data_type = 'DOUBLE';
                    break;
                case 'decimal':
                    $data_type = 'DECIMAL';
                    break;
                default:
                    $data_type = 'FLOAT';
            }
            if (isset($parameters['width']) && isset($parameters['decimals'])) {
               $data_type .= '('.$parameters['width'].','.$parameters['decimals'].')';
            }
            $this_field['type'] = $data_type;
            break;
        // Added Time field via marsel@phatcom.net (David Taylor)
        case 'time':
            $this_field['type'] = "TIME";
            break;
        case 'timestamp':
            if (empty($parameters['size'])) {
                $parameters['size'] = 'timestamp';
            }
            switch ($parameters['size']) {
                case 'YY':
                    $this_field['type'] = 'TIMESTAMP(2)';
                    break;
                case 'YYYY':
                    $this_field['type'] = 'TIMESTAMP(4)';
                    break;
                case 'YYYYMM':
                    $this_field['type'] = 'TIMESTAMP(6)';
                    break;
                case 'YYYYMMDD':
                    $this_field['type'] = 'TIMESTAMP(8)';
                    break;
                case 'YYYYMMDDHH':
                    $this_field['type'] = 'TIMESTAMP(10)';
                    break;
                case 'YYYYMMDDHHMM':
                    $this_field['type'] = 'TIMESTAMP(12)';
                    break;
                case 'YYYYMMDDHHMMSS':
                    $this_field['type'] = 'TIMESTAMP(14)';
                    break;
                default:
                    $this_field['type'] = 'TIMESTAMP';
            }
            break;

        // undefined type
        default:
            return false;
    }

    // Test for UNSIGNED
    $this_field['unsigned'] = (isset($parameters['unsigned']) && $parameters['unsigned'] == true)
                            ? 'UNSIGNED'
                            : '';

    // Test for NO NULLS
    $this_field['null']    = (isset($parameters['null']) && $parameters['null'] == false)
                        ? 'NOT NULL'
                        : '';

    // Test for DEFAULTS
    $this_field['default'] = (isset($parameters['default']))
                           ? (($parameters['default'] == 'NULL')
                                    ? 'DEFAULT NULL'
                                    : "DEFAULT '".$parameters['default']."'")
                           : '';

    // Test for AUTO_INCREMENT
    $this_field['auto_increment'] = (isset($parameters['increment']) && $parameters['increment'] == true)
                                  ? 'AUTO_INCREMENT'
                                  : '';

    // Bug #744 - Check "increment_start" field so that MySQL increment field will start at the appropriate startid
    if (!empty($this_field['auto_increment'])) {
        if (isset($parameters['increment_start']))
            $this_field['increment_start'] = $parameters['increment_start'];
        else {
            // FIXME: <mrb> IMO the default auto_increment start = 1, why not use
            //        that and  simplify code a bit?
            $this_field['increment_start'] = 0;
        }
    }

    // Bug #408 - MySQL 4.1 Alpha bug fix reported by matrix9180@deskmod.com (Chad Ingram)
    if (!empty($this_field['auto_increment'])) {
        $this_field['default'] = '';
    }

    // Test for PRIMARY KEY
    $this_field['primary_key'] = (isset($parameters['primary_key']) && $parameters['primary_key'] == true)
                               ? true
                               : false;

    return $this_field;
}

/**
 * Generate the PostgreSQL specific SQL to create a table
 *
 * @access private
 * @param tableName the physical table name
 * @param fields an array containing the fields to create
 * @return string|false the generated SQL statement, or false on failure
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDB__postgresqlCreateTable($tableName, $fields)
{
// old code. need to review the sequence thingy
/*
    $sql_fields = array();
    $seq_sql = '';

    while (list($field_name, $parameters) = each($fields)) {
        $parameters['command'] = 'create';
        $this_field = xarDB__postgresColumnDefinition($field_name, $parameters);
        $sql_fields[] = implode(' ', $this_field);

        // Test for increment field
        if (isset($parameters['increment']) && $parameters['increment'] == true) {
            // TODO GM - Temporarily removed
            // $seq_sql = 'CREATE SEQUENCE seq'.$tableName;
        }
    }
    $sql = 'CREATE TABLE '.$tableName.' ('.implode(',', $sql_fields).')';
    if ($seq_sql != '') {
        $sql .= '; '.$seq_sql;
    }
    return $sql;
*/
// new code
    $sql_fields = array();
    $primary_key = array();


    while (list($field_name, $parameters) = each($fields)) {
        $parameters['command'] = 'create';
        $this_field = xarDB__postgresColumnDefinition($field_name, $parameters);

        // For some reason that is not obvious in the old code, fetching
        // the values from $this_field was causing an infinite loop -
        // now check to see if the key exists before assigning to $sql_fields
        $sqlDDL = $field_name;
        if (array_key_exists("type", $this_field))
            $sqlDDL = $sqlDDL . ' ' . $this_field['type'];

        // PosgreSQL doesn't handle unsigned
        //if (array_key_exists("unsigned", $this_field))
        //    $sqlDDL = $sqlDDL . ' ' . $this_field['unsigned'];

        if (array_key_exists("null", $this_field))
            $sqlDDL = $sqlDDL . ' ' . $this_field['null'];

        if (array_key_exists("default", $this_field))
            $sqlDDL = $sqlDDL . ' ' . $this_field['default'];

        // PosgreSQL doesn't handle auto_increment - this should be a sequence
        //if (array_key_exists("auto_increment", $this_field))
        //    $sqlDDL = $sqlDDL . ' ' . $this_field['auto_increment'];

        $sql_fields[] = $sqlDDL;

        // Check for primary key
        if (array_key_exists("primary_key", $this_field)) {
            if ($this_field['primary_key'] == true) {
                $primary_key[] = $field_name;
            }
        }
    }

    $sql = 'CREATE TABLE '.$tableName.' ('.implode(', ',$sql_fields);
    if (!empty($primary_key)) {
        $sql .= ', PRIMARY KEY ('.implode(',',$primary_key).')';
    }
    $sql .= ')';

    return $sql;
}

/**
 * Postgres specific column type generation
 *
 * @access private
 * @param field_name
 * @param parameters
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDB__postgresColumnDefinition($field_name, $parameters)
{
    $this_field = array();

    switch($parameters['type']) {
        case 'integer':
            if (isset($parameters['size'])) {
                switch ($parameters['size']) {
                    case 'tiny':
                        $this_field['type'] = 'SMALLINT';
                        break;
                    case 'small':
                        $this_field['type'] = 'SMALLINT';
                        break;
                    case 'big':
                        $this_field['type'] = 'BIGINT';
                        break;
                    default:
                        $this_field['type'] = 'INTEGER';
                }
            } else {
                $this_field['type'] = 'INTEGER';
            }
            break;

        case 'char':
            if (empty($parameters['size'])) {
                return false;
            } else {
                $this_field['type'] = 'CHAR('.$parameters['size'].')';
            }
            if (isset($parameters['default'])) {
                $parameters['default'] = "'".$parameters['default']."'";
            }
            break;

        case 'varchar':
            if (empty($parameters['size'])) {
                return false;
            } else {
                $this_field['type'] = 'VARCHAR('.$parameters['size'].')';
            }
            if (isset($parameters['default'])) {
                $parameters['default'] = "'".$parameters['default']."'";
            }
            break;

        case 'text':
            $this_field['type'] = 'TEXT';
            break;

        case 'blob':
            $this_field['type'] = 'BYTEA';
            break;

        case 'boolean':
            $this_field['type'] = 'BOOLEAN';
            break;

        case 'timestamp':
        case 'datetime':
            // Note - after PostgreSQL 7.3, writing just timestamp is
            // equivalent to 'timestamp without time zone'
            $this_field['type'] = 'TIMESTAMP';

            if (isset($parameters['default'])) {
                $invalidDate = false;

                // Check if this is an array and convert back to string
                // array('year'=>2002,'month'=>04,'day'=>17)
                if (is_array($parameters['default'])) {
                    $datetime_defaults = $parameters['default'];
                    $parameters['default'] = $datetime_defaults['year'].
                                         '-'.$datetime_defaults['month'].
                                         '-'.$datetime_defaults['day'].
                                         ' '.$datetime_defaults['hour'].
                                         ':'.$datetime_defaults['minute'].
                                         ':'.$datetime_defaults['second'];

                    // Check if optional timezone parm and add after type
                    if (isset($datetime_defaults['timezone'])) {
                        $this_field['type'] .= " WITH TIME ZONE";
                    }
                } else {
                    // PostgreSQL doesn't allow a default value of
                    // '00-00-00 00:00:00 as this it is not a valid timestamp
                    if ($parameters['default'] == '0000-00-00 00:00:00' ||
                        $parameters['default'] == '00-00-00 00:00:00') {
                        // Set to current timestamp
                        $parameters['default'] = 'CURRENT_TIMESTAMP';
                        $invalidDate = true;
                    }
                }

                if (!$invalidDate) {
                    // Timestamp literal value must be placed in quotes
                    $parameters['default'] = "'" . $parameters['default'] . "'";
                    // the programmer should take care by using DBTimeStamp, which auto-quotes
                }

            } else {
                // Set to current timestamp
                $parameters['default'] = 'CURRENT_TIMESTAMP';
            }

            break;

        case 'date':
            $this_field['type'] = "DATE";

            if (isset($parameters['default'])) {
                $invalidDate = false;

                // Check if this is an array and convert back to string
                // array('year'=>2002,'month'=>04,'day'=>17)
                if (is_array($parameters['default'])) {
                    $datetime_defaults = $parameters['default'];
                    $parameters['default'] = $datetime_defaults['year'].
                                         '-'.$datetime_defaults['month'].
                                         '-'.$datetime_defaults['day'];
                } else {
                    // PostgreSQL doesn't allow a default value of
                    // '00-00-00 as this it is not a valid date
                    if ($parameters['default'] == '0000-00-00' ||
                        $parameters['default'] == '00-00-00') {
                        // Change to current date
                        $parameters['default'] = 'CURRENT_DATE';
                        $invalidDate = true;
                    }
                }

                if (!$invalidDate) {
                    // Timestamp literal value must be placed in quotes
                    $parameters['default'] = "'" . $parameters['default'] . "'";
                }

            } else {
                // Set to current date
                $parameters['default'] = 'CURRENT_DATE';
            }
            break;

        case 'float':
            if (empty($parameters['size'])) {
                $parameters['size'] = 'float';
            }
            switch ($parameters['size']) {
                case 'double':
                        $data_type = 'DOUBLE PRECISION';
                        break;

                case 'decimal':
                    $data_type = 'NUMERIC';
                    if (isset($parameters['width']) && isset($parameters['decimals'])) {
                        $data_type .= '('.$parameters['width'].','.$parameters['width'].')';
                    }
                    break;

                default:
                    $data_type = 'REAL';
            }
            $this_field['type'] = $data_type;
            break;

        // undefined type
        default:
            return false;
    }

    // Test for defaults - must come immediately after datatype for PostgreSQL
    // Note that postgres does not support defaults in a alter table add
    if (isset($parameters['default'])) {
        if ($parameters['command'] != 'add') {
            if ($parameters['default'] == 'NULL') {
                $this_field['default'] = 'DEFAULT NULL';
            } else {
                $this_field['default'] = "DEFAULT ".$parameters['default']."";
            }
        }
    } else {
        $this_field['default'] = '';
    }

    // UNSIGNED - postgres does not unsigned integers so skip this test

    // Test for NO NULLS - postgres does not support No Nulls on an alter table add
    if (isset($parameters['null']) && $parameters['null'] == false) {
        if ($parameters['command'] != 'add') {
            $this_field['null'] = 'NOT NULL';
        }
    }

    // Test for PRIMARY KEY
    if (isset($parameters['primary_key']) && $parameters['primary_key'] == true) {
        $this_field['primary_key'] = 'PRIMARY KEY';
    }

    return $this_field;
}

/**
 * Generate the Oracle specific SQL to create a table
 *
 * @access private
 * @param tableName the physical table name
 * @param fields an array containing the fields to create
 * @return string|false the generated SQL statement, or false on failure
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDB__oracleCreateTable($tableName, $fields)
{
    $sql_fields = array();
    $primary_key = array();

    while (list($field_name, $parameters) = each($fields)) {
        $parameters['command'] = 'create';
        $this_field = xarDB__oracleColumnDefinition($field_name, $parameters);

        $sqlDDL = $field_name;
        if (array_key_exists("type", $this_field))
            $sqlDDL = $sqlDDL . ' ' . $this_field['type'];

        // Oracle doesn't handle unsigned
        //if (array_key_exists("unsigned", $this_field))
        //    $sqlDDL = $sqlDDL . ' ' . $this_field['unsigned'];

        // Order of default and null clause matter
        if (array_key_exists("default", $this_field))
            $sqlDDL = $sqlDDL . ' ' . $this_field['default'];

        if (array_key_exists("null", $this_field))
            $sqlDDL = $sqlDDL . ' ' . $this_field['null'];

        // Oracle doesn't handle auto_increment - this should be a sequence
        //if (array_key_exists("auto_increment", $this_field))
        //    $sqlDDL = $sqlDDL . ' ' . $this_field['auto_increment'];

        $sql_fields[] = $sqlDDL;

        // Check for primary key
        if (array_key_exists("primary_key", $this_field)) {
            if ($this_field['primary_key'] == true) {
                $primary_key[] = $field_name;
            }
        }
    }

    $sql = 'CREATE TABLE '.$tableName.' ('.implode(', ',$sql_fields);
    if (!empty($primary_key)) {
        $sql .= ', PRIMARY KEY ('.implode(',',$primary_key).')';
    }
    $sql .= ')';

    return $sql;
}

/**
 * Oracle specific column type generation
 *
 * @access private
 * @param field_name
 * @param parameters
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDB__oracleColumnDefinition($field_name, $parameters)
{
    $this_field = array($field_name);

    switch($parameters['type']) {
        case 'integer':
            // TODO Get correct Sizes
            if (isset($parameters['size'])) {
                switch ($parameters['size']) {
                    case 'tiny':
                        $this_field['type'] = 'NUMBER(3)';
                        break;
                    case 'small':
                        $this_field['type'] = 'NUMBER(5)';
                        break;
                    case 'big':
                        $this_field['type'] = 'NUMBER(20)';
                        break;
                    default:
                        $this_field['type'] = 'NUMBER(11)';
                }
            } else {
                $this_field['type'] = 'NUMBER(11)';
            }
            break;

        case 'char':
            if (empty($parameters['size'])) {
                return false;
            } else {
                $this_field['type'] = 'CHAR('.$parameters['size'].')';
            }
            if (isset($parameters['default'])) {
                $parameters['default'] = "'".$parameters['default']."'";
            }
            break;

        case 'varchar':
            if (empty($parameters['size'])) {
                return false;
            } else {
                $this_field['type'] = 'VARCHAR2('.$parameters['size'].')';
            }
            if (isset($parameters['default'])) {
                $parameters['default'] = "'".$parameters['default']."'";
            }
            break;

        case 'text':
            $this_field['type'] = 'CLOB';
            break;

        case 'blob':
            $this_field['type'] = 'BLOB';
            break;

        case 'boolean':
            $this_field['type'] = 'NUMBER(1)';
            break;

        case 'timestamp':
        case 'datetime':
            $this_field['type'] = 'TIMESTAMP';

            if (isset($parameters['default'])) {
                $invalidDate = false;

                // Check if this is an array and convert back to string
                // array('year'=>2002,'month'=>04,'day'=>17)
                if (is_array($parameters['default'])) {
                    $datetime_defaults = $parameters['default'];
                    $parameters['default'] = $datetime_defaults['year'].
                                         '-'.$datetime_defaults['month'].
                                         '-'.$datetime_defaults['day'].
                                         ' '.$datetime_defaults['hour'].
                                         ':'.$datetime_defaults['minute'].
                                         ':'.$datetime_defaults['second'];

                } else {
                    // Oracle doesn't allow a default value of
                    // '00-00-00 00:00:00 as this it is not a valid timestamp
                    if ($parameters['default'] == '0000-00-00 00:00:00' ||
                        $parameters['default'] == '00-00-00 00:00:00') {
                        // Change to current timestamp
                        $parameters['default'] = 'NOW()';
                        $invalidDate = true;
                    }
                }

                if (!$invalidDate) {
                    // Timestamp literal value must be placed in quotes
                    $parameters['default'] = "'" . $parameters['default'] . "'";
                }

            } else {
                // Default timestamp to the current time
                $parameters['default'] = 'NOW()';
            }
            break;

        case 'date':
            $this_field['type'] = "DATE";

            if (isset($parameters['default'])) {
                $invalidDate = false;

                // Check if this is an array and convert back to string
                // array('year'=>2002,'month'=>04,'day'=>17)
                if (is_array($parameters['default'])) {
                    $datetime_defaults = $parameters['default'];
                    $parameters['default'] = $datetime_defaults['year'].
                                         '-'.$datetime_defaults['month'].
                                         '-'.$datetime_defaults['day'];
                } else {
                    // Oracle doesn't allow a default value of
                    // '00-00-00' as this it is not a valid date
                    // Optionally, a date may have a time value in Oracle
                    if (stristr('0000-00-00', $parameters['default']) ||
                        stristr('00-00-00', $parameters['default'])) {
                        // Default date to the current time
                        $parameters['default'] = ' NOW()';
                        $invalidDate = true;
                    }
                }

                if (!$invalidDate) {
                    // Timestamp literal value must be placed in quotes
                    $parameters['default'] = "'" . $parameters['default'] . "'";
                }

            } else {
                // Default date to the current time
                $parameters['default'] = ' NOW()';
            }
            break;

        case 'float':
            if (empty($parameters['size'])) {
                $parameters['size'] = 'float';
            }
            switch ($parameters['size']) {
                case 'double':
                        $data_type = 'DOUBLE PRECISION';
                        break;

                case 'decimal':
                    if (isset($parameters['width']) && isset($parameters['decimals'])) {
                        $data_type = 'NUMBER('.$parameters['width'].','.$parameters['width'].')';
                    } else {
                        $data_type = 'REAL';
                    }
                    break;

                default:
                    $data_type = 'REAL';
            }
            $this_field['type'] = $data_type;
            break;

        // undefined type
        default:
            return false;
    }

    // Test for defaults - must come immediately after datatype for Oracle
    if (isset($parameters['default'])) {
        if ($parameters['default'] == 'NULL') {
            $this_field['default'] = 'DEFAULT NULL';
        } else {
            $this_field['default'] = "DEFAULT ".$parameters['default']."";
        }
    }

    // Test for NO NULLS - Oracle does not support No Nulls on an alter table add
    if (isset($parameters['null']) && $parameters['null'] == false) {
        if ($parameters['command'] != 'add') {
            // Since Oracle doesn't distinguish between empty strings and NULLs,
            // and Xaraya does make that distinction, we need to remove NOT NULL
            // for Oracle when dealing with char/varchar/text fields !
            if ($parameters['type'] != 'char' &&
                $parameters['type'] != 'varchar' &&
                $parameters['type'] != 'text') {

                $this_field['null'] = 'NOT NULL';
            }
        }
    }

    // Test for PRIMARY KEY
    if (isset($parameters['primary_key']) && $parameters['primary_key'] == true) {
        $this_field['primary_key'] = 'PRIMARY KEY';
    }

    return $this_field;
}

/**
* Generate the SQLite specific SQL to create a table
 *
 * @access private
 * @param tableName the physical table name
 * @param fields an array containing the fields to create
 * @return string|false the generated SQL statement, or false on failure
 */
function xarDB__sqliteCreateTable($tableName, $fields)
{
    $sql_fields = array();
    $primary_key = array();
    $increment_start = false;
    
    while (list($field_name, $parameters) = each($fields)) {
        $parameters['command'] = 'create';
        $this_field = xarDB__sqliteColumnDefinition($field_name, $parameters);
        
        $sql_fields[] = $field_name .' '
            . $this_field['type'] .' '
            . $this_field['unsigned'] .' '
            . $this_field['null'] .' '
            . $this_field['default'] .' '
        . $this_field['auto_increment'];
        
        if ($this_field['primary_key'] == true) {
            $primary_key[] = $field_name;
        }
        if (empty($this_field['increment_start'])) {
            $this_field['increment_start'] = false;
        }
        if ($this_field['increment_start'] != false) {
            $increment_start = $this_field['increment_start'];
        }
    }
    
    $sql = 'CREATE TABLE '.$tableName.' ('.implode(', ',$sql_fields);
                                         
    if (!empty($primary_key)) {
        $sql .= ', PRIMARY KEY ('.implode(',',$primary_key).')';
    }
    $sql .= ')';

    return $sql;
}

/**
 * SQLite specific column type generation
 *
 * Note that SQLite only cares about INTEGER PRIMARY KEY 
 * all other specs are not needed. We left them in here, so the SQL generated
 * is at least more clear. 
 *
 * @access private
 * @param field_name
 * @param parameters
 *
 */
function xarDB__sqliteColumnDefinition($field_name, $parameters) 
{
    $this_field = array();

    switch($parameters['type']) {
        case 'integer':
            if (empty($parameters['size']))  $parameters['size'] = 'int';
            // Let's always use integer instead of int, so when it gets set as primary key, we get the GenId behaviour for free
            $this_field['type'] = 'INTEGER'; 
            break;
        case 'char':
            if (empty($parameters['size'])) return false;
            $this_field['type'] = 'CHAR('.$parameters['size'].')';
            break;
        case 'varchar':
            if (empty($parameters['size'])) return false;
            $this_field['type'] = 'VARCHAR('.$parameters['size'].')';
            break;
        case 'text':
            if (empty($parameters['size'])) $parameters['size'] = 'text';
            $this_field['type'] = 'TEXT';
            break;
        case 'blob':
            if (empty($parameters['size'])) $parameters['size'] = 'blob';
            $this_field['type'] = 'BLOB';
            break;
        case 'boolean':
            $this_field['type'] = "BOOL";
            break;
        case 'datetime':
            $this_field['type'] = "DATETIME";
            if (isset($parameters['default'])) {
                // Check if this is an array and convert back to string
                // array('year'=>2002,'month'=>04,'day'=>17,'hour'=>'12','minute'=>59,'second'=>0)
                if (is_array($parameters['default'])) {
                    $datetime_defaults = $parameters['default'];
                    $parameters['default'] = $datetime_defaults['year'].
                                         '-'.$datetime_defaults['month'].
                                         '-'.$datetime_defaults['day'].
                                         ' '.$datetime_defaults['hour'].
                                         ':'.$datetime_defaults['minute'].
                                         ':'.$datetime_defaults['second'];
                }
            }
            break;
        case 'date':
            $this_field['type'] = "DATE";
            if (isset($parameters['default'])) {
                // Check if this is an array and convert back to string
                // array('year'=>2002,'month'=>04,'day'=>17)
                if (is_array($parameters['default'])) {
                    $datetime_defaults = $parameters['default'];
                    $parameters['default'] = $datetime_defaults['year'].
                                         '-'.$datetime_defaults['month'].
                                         '-'.$datetime_defaults['day'];
                }
            }
            break;
        case 'float':
            if (empty($parameters['size'])) $parameters['size'] = 'float';
            switch ($parameters['size']) {
                case 'double':
                    $data_type = 'DOUBLE';
                    break;
                case 'decimal':
                    $data_type = 'DECIMAL';
                    break;
                default:
                    $data_type = 'FLOAT';
            }
            if (isset($parameters['width']) && isset($parameters['decimals'])) {
               $data_type .= '('.$parameters['width'].','.$parameters['decimals'].')';
            }
            $this_field['type'] = $data_type;
            break;
       case 'time':
            $this_field['type'] = "TIME";
            break;
        case 'timestamp':
            if (empty($parameters['size'])) $parameters['size'] = 'timestamp';
            switch ($parameters['size']) {
                case 'YY':
                    $this_field['type'] = 'TIMESTAMP(2)';
                    break;
                case 'YYYY':
                    $this_field['type'] = 'TIMESTAMP(4)';
                    break;
                case 'YYYYMM':
                    $this_field['type'] = 'TIMESTAMP(6)';
                    break;
                case 'YYYYMMDD':
                    $this_field['type'] = 'TIMESTAMP(8)';
                    break;
                case 'YYYYMMDDHH':
                    $this_field['type'] = 'TIMESTAMP(10)';
                    break;
                case 'YYYYMMDDHHMM':
                    $this_field['type'] = 'TIMESTAMP(12)';
                    break;
                case 'YYYYMMDDHHMMSS':
                    $this_field['type'] = 'TIMESTAMP(14)';
                    break;
                default:
                    $this_field['type'] = 'TIMESTAMP';
            }
            break;
        default:
            return false;
    }

    // Test for UNSIGNED
    $this_field['unsigned'] = (isset($parameters['unsigned']) && $parameters['unsigned'] == true)
                            ? 'UNSIGNED'
                            : '';

    // Test for NO NULLS
    $this_field['null']    = (isset($parameters['null']) && $parameters['null'] == false)
                        ? 'NOT NULL'
                        : '';

    // Test for DEFAULTS
    $this_field['default'] = (isset($parameters['default']))
                           ? (($parameters['default'] == 'NULL')
                                    ? 'DEFAULT NULL'
                                    : "DEFAULT '".$parameters['default']."'")
                           : '';

    // Test for AUTO_INCREMENT
    $this_field['auto_increment'] = (isset($parameters['increment']) && $parameters['increment'] == true)
                                  ? ''
                                  : '';

    // Test for PRIMARY KEY
    $this_field['primary_key'] = (isset($parameters['primary_key']) && $parameters['primary_key'] == true)
                               ? true
                               : false;

    return $this_field;
}

?>
