<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Gary Mitchell
// Purpose of file: Table Maintenance API
// ----------------------------------------------------------------------

/* TODO:
 * Check functions!
 * Check FIXMEs
 * Document functions
 */

/*
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
        'field_name'        => 'xar_track_name',
        'new_field_name'    => 'xar_track_name1'
        'type'              => 'integer',
        'null'              => false,
        'increment'         => true,
        'primary_key'       => true,
    )
);  */
/**
 * Generate the SQL to create a database
 *
 * @access public
 * @param databaseName
 * @param databaseType
 * @returns string
 * @return sql statement for database creation
 * @raise BAD_PARAM
 */
function xarDBCreateDatabase($databaseName, $databaseType = NULL)
{
    // perform validations on input arguments
    if (empty($databaseName)) {
        $msg = xarML('Empty database_name.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }
    if (empty($databaseType)) {
        $databaseType = xarDBGetType();
    }

    switch($databaseType) {
        case 'mysql':
        case 'postgres':
        case 'oci8':
            $sql = 'CREATE DATABASE '.$databaseName;
            break;
        // Other DBs go here
        default:
            $msg = xarML('Unknown database type: \'#(1)\'.', $databaseType);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
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
 * @param args['field_name'] field to alter
 * @param args['new_field_name'] new field name
 * @param args['type'] field type
 * @param args['null'] null or not
 * @param args['increment'] auto incrementing files
 * @param args['primary_key'] primary key
 * @param databaseType the database type (optional)
 * @returns string
 * @return generated sql
 */
function xarDBAlterTable($tableName, $args, $databaseType = NULL)
{
    // perform validations on input arguments
    if (empty($tableName)) {
        $msg = xarML('Empty tableName.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }
    if (!is_array($args) || !is_array($args['command'])) {
        $msg = xarML('Invalid args (must be an array, command key must be set).');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    if (empty($databaseType)) {
        $databaseType = xarDBGetType();
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
            $sql = xarDB__oracleAlterTable($tableName, $args);
            break;
        // Other DBs go here
        default:
            $msg = xarML('Unknown database type: \'#(1)\'.', $databaseType);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
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
 * @returns string|false
 * @return the generated SQL statement, or false on failure
 */
function xarDBCreateTable($tableName, $fields, $databaseType="")
{
    // perform validations on input arguments
    if (empty($tableName)) {
        $msg = xarML('Empty tableName.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }
    if (!is_array($fields)) {
        $msg = xarML('Not array fields.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    if (empty($databaseType)) {
        $databaseType = xarDBGetType();
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
            $sql = xarDB__oracleCreateTable($tableName, $fields);
            break;
        // Other DBs go here
        default:
            $msg = xarML('Unknown database type: \'#(1)\'.', $databaseType);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
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
 * @returns data|false
 * @return the generated SQL statement, or false on failure
 */
function xarDBDropTable($tableName, $databaseType = NULL)
{
    // perform validations on input arguments
    if (empty($tableName)) {
        $msg = xarML('Empty tableName.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }
    if (empty($databaseType)) {
        $databaseType = xarDBGetType();
    }

    switch($databaseType) {
        case 'mysql':
        case 'postgres':
        case 'oci8':
            $sql = 'DROP TABLE '.$tableName;
            break;
        // Other DBs go here
        default:
            $msg = xarML('Unknown database type: \'#(1)\'.', $databaseType);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
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
 * @returns string|false
 * @return the generated SQL statement, or false on failure
 */
function xarDBCreateIndex($tableName, $index, $databaseType = NULL) {

    // perform validations on input arguments
    if (empty($tableName)) {
        $msg = xarML('Empty tableName.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }
    if (!is_array($index) || !is_array($index['fields']) || empty($index['name'])) {
        $msg = xarML('Invalid index (must be an array, fields key must be an array, name key must be set).');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
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
        case 'postgres':
        case 'oci8':
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
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
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
 * @param fields array of database index fields?
 * @param databaseType
 * @returns string|false
 * @return generated sql to drop an index
 * @raise BAD_PARAM
 */
function xarDBDropIndex($tableName, $fields, $databaseType = NULL)
{
    // perform validations on input arguments
    if (empty($tableName)) {
        $msg = xarML('Empty tableName.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }
    if (!is_array($index) || !is_array($index['fields']) || empty($index['name'])) {
        $msg = xarML('Invalid index (must be an array, fields key must be an array, name key must be set).');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }
    if (empty($databaseType)) {
        $databaseType = xarDBGetType();
    }

    // Select the correct database type
    switch($databaseType) {
        case 'mysql':
            $sql .= 'DROP INDEX '.$index['name'].' ON '.$tableName;
            break;
        case 'postgres':
        case 'oci8':
            $sql .= 'DROP INDEX '.$index['name'];
            break;
        // Other DBs go here
        default:
            $msg = xarML('Unknown database type: \'#(1)\'.', $databaseType);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
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
 * @param args['fields']
 * @param args['after_field']
 * @param args['new_name'] new name of table
 * @returns string|false
 * @return mysql specific sql to alter a table
 * @raise BAD_PARAM
 */
function xarDB__mysqlAlterTable($tableName, $args)
{
    switch ($args['command']) {
        case 'add':
            if (empty($args['field'])) {
                $msg = xarML('Invalid args (field key must be set).');
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }
            $sql = 'ALTER TABLE '.$tableName.' ADD ';
            $sql .= join(' ', xarDB__mysqlColumnDefinition($args['field'], $args));
            if ($args['first'] == true) {
                $sql .= ' FIRST';
            } elseif (!empty($args['after_field'])) {
                $sql .= ' AFTER '.$args['after_field'];
            }
            break;
        case 'rename':
            if (empty($args['new_name'])) {
                $msg = xarML('Invalid args (new_name key must be set.)');
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }
            $sql = 'ALTER TABLE '.$tableName.' RENAME TO '.$args['new_name'];
            break;
        default:
            $msg = xarML('Unknown command: \'#(1)\'.', $args['command']);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
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
 * @param args['fields'] fields to modify
 * @param args['new_name'] new name of table
 * @returns string|false
 * @return postgres specific sql to alter a table
 * @raise BAD_PARAM
 */
function xarDB_postgresqlAlterTable($tableName, $args)
{
    switch ($args['command']) {
        case 'add':
            if (empty($args['field'])) {
                $msg = xarML('Invalid args (field key must be set).');
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }
            $sql = 'ALTER TABLE '.$tableName.' ADD ';
            $sql .= join(' ', xarDB__postgresColumnDefinition($args['field'], $args));
            break;
        case 'rename':
            if (empty($args['new_name'])) {
                $msg = xarML('Invalid args (new_name key must be set.)');
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }
            $sql = 'ALTER TABLE '.$tableName.' RENAME TO '.$args['new_name'];
            break;
        default:
            $msg = xarML('Unknown command: \'#(1)\'.', $args['command']);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
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
 * @param args['fields'] fields to modify
 * @param args['new_name'] new name of table
 * @returns string|false
 * @return oracle specific sql to alter a table
 * @raise BAD_PARAM
 */
function xarDB_oracleAlterTable($tableName, $args)
{
    switch ($args['command']) {
        case 'add':
            if (empty($args['field'])) {
                $msg = xarML('Invalid args (field key must be set).');
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }
            $sql = 'ALTER TABLE '.$tableName.' ADD ';
            $sql .= join(' ', xarDB__oracleColumnDefinition($args['field'], $args));
            break;
        case 'rename':
            if (empty($args['new_name'])) {
                $msg = xarML('Invalid args (new_name key must be set.)');
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }
            $sql = 'ALTER TABLE '.$tableName.' RENAME TO '.$args['new_name'];
            break;
        default:
            $msg = xarML('Unknown command: \'#(1)\'.', $args['command']);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
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
 * @returns string|false
 * @return the generated SQL statement, or false on failure
 */
function xarDB__mysqlCreateTable($tableName, $fields)
{
    $sql_fields = array();

    while (list($field_name, $parameters) = each($fields)) {
        $parameters['command'] = 'create';
        $this_field = xarDB__mysqlColumnDefinition($field_name, $parameters);
        $sql_fields[] = implode(' ', $this_field);
    }
    $sql = 'CREATE TABLE '.$tableName.' ('.implode(',', $sql_fields).')';
    return $sql;
}

/**
 * Mysql specific column type generation
 *
 * @access private
 * @param field_name
 * @param parameters
 *
 */
function xarDB__mysqlColumnDefinition($field_name, $parameters)
{
    $this_field = array($field_name);

    switch($parameters['type']) {

        case 'integer':
            if (empty($parameters['size'])) {
                $parameters['size'] = 'int';
            }
            switch ($parameters['size']) {
                case 'tiny':
                    $this_field[] = 'TINYINT';
                    break;
                case 'small':
                    $this_field[] = 'SMALLINT';
                    break;
                case 'medium':
                    $this_field[] = 'MEDIUMINT';
                    break;
                case 'big':
                    $this_field[] = 'BIGINT';
                    break;
                default:
                    $this_field[] = 'INTEGER';
            } // switch ($parameters['size'])
            break;

        case 'char':
            if (empty($parameters['size'])) {
                return false;
            } else {
                $this_field[] = 'CHAR('.$parameters['size'].')';
            }
            break;

        case 'varchar':
            if (empty($parameters['size'])) {
                return false;
            } else {
                $this_field[] = 'VARCHAR('.$parameters['size'].')';
            }
            break;

        case 'text':
            if (empty($parameters['size'])) {
                $parameters['size'] = 'text';
            }
            switch ($parameters['size']) {
                case 'tiny':
                    $this_field[] = 'TINYTEXT';
                    break;
                case 'medium':
                    $this_field[] = 'MEDIUMTEXT';
                    break;
                case 'long':
                    $this_field[] = 'LONGTEXT';
                    break;
                default:
                    $this_field[] = 'TEXT';
            }
            break;

        case 'blob':
            if (empty($parameters['size'])) {
                $parameters['size'] = 'blob';
            }
            switch ($parameters['size']) {
                case 'tiny':
                    $this_field[] = 'TINYBLOB';
                    break;
                case 'medium':
                    $this_field[] = 'MEDIUMBLOB';
                    break;
                case 'long':
                    $this_field[] = 'LONGBLOB';
                    break;
                default:
                    $this_field[] = 'BLOB';
            }
            break;

        case 'boolean':
            $this_field[] = "BOOL";
            break;

        case 'datetime':
            $this_field[] = "DATETIME";
            // convert parameter array back to string for datetime
            // array('year'=>2002,'month'=>04,'day'=>17,'hour'=>'12','minute'=>59,'second'=>0)
            if (isset($parameters['default'])) {
                $datetime_defaults = $parameters['default'];
                $parameters['default'] = $datetime_defaults['year'].
                                         '-'.$datetime_defaults['month'].
                                         '-'.$datetime_defaults['day'].
                                         ' '.$datetime_defaults['hour'].
                                         ':'.$datetime_defaults['minute'].
                                         ':'.$datetime_defaults['second'];
            }
            break;

        case 'date':
            $this_field[] = "DATE";
            // convert parameter array back to string for datetime
            // array('year'=>2002,'month'=>04,'day'=>17)
            if (isset($parameters['default'])) {
                $datetime_defaults = $parameters['default'];
                $parameters['default'] = $datetime_defaults['year'].
                                         '-'.$datetime_defaults['month'].
                                         '-'.$datetime_defaults['day'];
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
               $data_type .= '('.$parameters['width'].','.$parameters['width'].')';
            }
            $this_field[] = $data_type;
            break;

        case 'timestamp':
            if (empty($parameters['size'])) {
                $parameters['size'] = 'timestamp';
            }
            switch ($parameters['size']) {
                case 'YY':
                    $this_field[] = 'TIMESTAMP(2)';
                    break;
                case 'YYYY':
                    $this_field[] = 'TIMESTAMP(4)';
                    break;
                case 'YYYYMM':
                    $this_field[] = 'TIMESTAMP(6)';
                    break;
                case 'YYYYMMDD':
                    $this_field[] = 'TIMESTAMP(8)';
                    break;
                case 'YYYYMMDDHH':
                    $this_field[] = 'TIMESTAMP(10)';
                    break;
                case 'YYYYMMDDHHMM':
                    $this_field[] = 'TIMESTAMP(12)';
                    break;
                case 'YYYYMMDDHHMMSS':
                    $this_field[] = 'TIMESTAMP(14)';
                    break;
                default:
                    $this_field[] = 'TIMESTAMP';
            }
            break;

        // undefined type
        default:
            return false;
    }

    // Test for UNSIGNED
    if (isset($parameters['unsigned']) && $parameters['unsigned'] == true) {
       $this_field[] = 'UNSIGNED';
    }

    // Test for NO NULLS
    if (isset($parameters['null']) && $parameters['null'] == false) {
       $this_field[] = 'NOT NULL';
    }

    // Test for DEFAULTS
    if (isset($parameters['default'])) {
        if ($parameters['default'] == 'NULL') {
            $this_field[] = 'DEFAULT NULL';
        } else {
            $this_field[] = "DEFAULT '".$parameters['default']."'";
        }
    }

    // Test for AUTO_INCREMENT
    if (isset($parameters['increment']) && $parameters['increment'] == true) {
        $this_field[] = "AUTO_INCREMENT";
    }

    // Test for PRIMARY KEY
    if (isset($parameters['primary_key']) && $parameters['primary_key'] == true) {
        $this_field[] = "PRIMARY KEY";
    }
    return $this_field;
}

/**
 * Generate the PostgreSQL specific SQL to create a table
 *
 * @access private
 * @param tableName the physical table name
 * @param fields an array containing the fields to create
 * @returns string|false
 * @return the generated SQL statement, or false on failure
 */
function xarDB__postgresqlCreateTable($tableName, $fields)
{
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
}

/**
 * Postgres specific column type generation
 *
 * @access private
 * @param field_name
 * @param parameters
 *
 */
function xarDB__postgresColumnDefinition($field_name, $parameters)
{
    $this_field = array($field_name);

    switch($parameters['type']) {
        case 'integer':
            if (isset($parameters['size'])) {
                switch ($parameters['size']) {
                    case 'tiny':
                        $this_field[] = 'SMALLINT';
                        break;
                    case 'small':
                        $this_field[] = 'SMALLINT';
                        break;
                    case 'big':
                        $this_field[] = 'BIGINT';
                        break;
                    default:
                        $this_field[] = 'INTEGER';
                }
            } else {
                $this_field[] = 'INTEGER';
            }
            break;

        case 'char':
            if (empty($parameters['size'])) {
                return false;
            } else {
                $this_field[] = 'CHAR('.$parameters['size'].')';
            }
            if (isset($parameters['default'])) {
                $parameters['default'] = "'".$parameters['default']."'";
            }
            break;

        case 'varchar':
            if (empty($parameters['size'])) {
                return false;
            } else {
                $this_field[] = 'VARCHAR('.$parameters['size'].')';
            }
            if (isset($parameters['default'])) {
                $parameters['default'] = "'".$parameters['default']."'";
            }
            break;

        case 'text':
            $this_field[] = 'TEXT';
            break;

        case 'blob':
            $this_field[] = 'BYTEA';
            break;

        case 'boolean':
            $this_field[] = 'BOOLEAN';
            break;

        case 'timestamp':
        case 'datetime':
            $this_field[] = 'TIMESTAMP WITH TIME ZONE';
            // convert parameter array back to string for datetime
            // array('year'=>2002,'month'=>04,'day'=>17,'hour'=>'12','minute'=>59,'second'=>0)
            if (isset($parameters['default'])) {
                $datetime_defaults = $parameters['default'];
                $parameters['default'] = $datetime_defaults['year'].
                                         '-'.$datetime_defaults['month'].
                                         '-'.$datetime_defaults['day'].
                                         ' '.$datetime_defaults['hour'].
                                         ':'.$datetime_defaults['minute'].
                                         ':'.$datetime_defaults['second'];
                if (isset($datetime_defaults['timezone'])) {  // optional parm
                    // FIXME: <marco> Gary, are you sure of this assigment?
                    $parameters['default'] = $datetime_defaults['timezone'];
                }
            // only for timestamps - default them to the current time
            } elseif ($parameters['type'] == 'timestamp') {
                $parameters['default'] = 'CURRENT_TIMESTAMP';
            }
            break;

        case 'date':
            $this_field[] = "DATE";
            // convert parameter array back to string for datetime
            // array('year'=>2002,'month'=>04,'day'=>17)
            if (isset($parameters['default'])) {
                $datetime_defaults = $parameters['default'];
                $parameters['default'] = $datetime_defaults['year'].
                                         '-'.$datetime_defaults['month'].
                                         '-'.$datetime_defaults['day'];
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
            $this_field[] = $data_type;
            break;

        // undefined type
        default:
            return false;
    }

    // Test for defaults - must come immediately after datatype for PostgreSQL
    // Note that postgres does not support defaults in a alter table add
    if (isset($parameters['default'])) {
        if ($parameters['command'] == 'add') return false;
        if ($parameters['default'] == 'NULL') {
            $this_field[] = 'DEFAULT NULL';
        } else {
            $this_field[] = "DEFAULT ".$parameters['default']."";
        }
    }

    // UNSIGNED - postgres does not unsigned integers so skip this test

    // Test for NO NULLS - postgres does not support No Nulls on an alter table add
    if (isset($parameters['null']) && $parameters['null'] == false) {
        if ($parameters['command'] == 'add') return false;
        $this_field[] = 'NOT NULL';
    }

    // Test for PRIMARY KEY
    if (isset($parameters['primary_key']) && $parameters['primary_key'] == true) {
        $this_field[] = 'PRIMARY KEY';
    }

    return $this_field;
}

/**
 * Generate the Oracle specific SQL to create a table
 *
 * @access private
 * @param tableName the physical table name
 * @param fields an array containing the fields to create
 * @returns string|false
 * @return the generated SQL statement, or false on failure
 */
function xarDB__oracleCreateTable($tableName, $fields)
{
    $sql_fields = array();
    $seq_sql = '';

    while (list($field_name, $parameters) = each($fields)) {
        $parameters['command'] = 'create';
        $this_field = xarDB__oracleColumnDefinition($field_name, $parameters);
        $sql_fields[] = implode(' ', $this_field);

    }
    $sql = 'CREATE TABLE '.$tableName.' ('.implode(',', $sql_fields).')';
    if ($seq_sql != '') {
        $sql .= '; '.$seq_sql;
    }
    return $sql;
}

/**
 * Oracle specific column type generation
 *
 * @access private
 * @param field_name
 * @param parameters
 *
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
                        $this_field[] = 'NUMBER(8)';
                        break;
                    case 'small':
                        $this_field[] = 'NUMBER(8)';
                        break;
                    case 'big':
                        $this_field[] = 'NUMBER(8)';
                        break;
                    default:
                        $this_field[] = 'NUMBER(8)';
                }
            } else {
                $this_field[] = 'NUMBER(8)';
            }
            break;

        case 'char':
            if (empty($parameters['size'])) {
                return false;
            } else {
                $this_field[] = 'CHAR('.$parameters['size'].')';
            }
            if (isset($parameters['default'])) {
                $parameters['default'] = "'".$parameters['default']."'";
            }
            break;

        case 'varchar':
            if (empty($parameters['size'])) {
                return false;
            } else {
                $this_field[] = 'VARCHAR2('.$parameters['size'].')';
            }
            if (isset($parameters['default'])) {
                $parameters['default'] = "'".$parameters['default']."'";
            }
            break;

        case 'text':
            $this_field[] = 'CLOB';
            break;

        case 'blob':
            $this_field[] = 'BLOB';
            break;

        case 'boolean':
            $this_field[] = 'NUMBER(1)';
            break;

        case 'timestamp':
        case 'datetime':
            $this_field[] = 'TIMESTAMP WITH TIME ZONE';
            // convert parameter array back to string for datetime
            // array('year'=>2002,'month'=>04,'day'=>17,'hour'=>'12','minute'=>59,'second'=>0)
            if (isset($parameters['default'])) {
                $datetime_defaults = $parameters['default'];
                $parameters['default'] = $datetime_defaults['year'].
                                         '-'.$datetime_defaults['month'].
                                         '-'.$datetime_defaults['day'].
                                         ' '.$datetime_defaults['hour'].
                                         ':'.$datetime_defaults['minute'].
                                         ':'.$datetime_defaults['second'];
                if (isset($datetime_defaults['timezone'])) {  // optional parm
                    // FIXME: <marco> Gary, are you sure of this assigment?
                    $parameters['default'] = $datetime_defaults['timezone'];
                }
            // only for timestamps - default them to the current time
            } elseif ($parameters['type'] == 'timestamp') {
                $parameters['default'] = 'CURRENT_TIMESTAMP';
            }
            break;

        case 'date':
            $this_field[] = "DATE";
            // convert parameter array back to string for datetime
            // array('year'=>2002,'month'=>04,'day'=>17)
            if (isset($parameters['default'])) {
                $datetime_defaults = $parameters['default'];
                $parameters['default'] = $datetime_defaults['year'].
                                         '-'.$datetime_defaults['month'].
                                         '-'.$datetime_defaults['day'];
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
            $this_field[] = $data_type;
            break;

        // undefined type
        default:
            return false;
    }

    // Test for defaults - must come immediately after datatype for PostgreSQL
    if (isset($parameters['default'])) {
        if ($parameters['default'] == 'NULL') {
            $this_field[] = 'DEFAULT NULL';
        } else {
            $this_field[] = "DEFAULT ".$parameters['default']."";
        }
    }

    // Test for NO NULLS - oracle does not support No Nulls on an alter table add
    if (isset($parameters['null']) && $parameters['null'] == false) {
        if ($parameters['command'] == 'add') return false;
        $this_field[] = 'NOT NULL';
    }

    // Test for PRIMARY KEY
    if (isset($parameters['primary_key']) && $parameters['primary_key'] == true) {
        $this_field[] = 'PRIMARY KEY';
    }

    return $this_field;
}

?>
