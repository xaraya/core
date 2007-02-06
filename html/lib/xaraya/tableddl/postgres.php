<?php
/**
 * Table Maintenance API for PostgreSQL
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

// PRIVATE FUNCTIONS BELOW - do not call directly

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
    $sql_fields = array();
    $primary_key = array();
    $epilogue ='';

    while (list($field_name, $parameters) = each($fields)) {
        $parameters['command'] = 'create';
        $this_field = xarDB__postgresColumnDefinition($field_name, $parameters);

        // For some reason that is not obvious in the old code, fetching
        // the values from $this_field was causing an infinite loop -
        // now check to see if the key exists before assigning to $sql_fields
        $sqlDDL = $field_name;
        if (isset($this_field['type']))
            $sqlDDL = $sqlDDL . ' ' . $this_field['type'];

        if (isset($this_field['null']))
            $sqlDDL = $sqlDDL . ' ' . $this_field['null'];

        if (isset($this_field['default']))
            $sqlDDL = $sqlDDL . ' ' . $this_field['default'];
            
        if (isset($parameters['increment']) && $parameters['increment'] == true) {
            // we only support one such field per table, so we simplify the
            // sequence name to apply on the table without the specific column name.
            $epilogue .= 'ALTER TABLE '.$tableName.'_'.$field_name.'_seq RENAME TO '.$tableName.'_seq;';
        }

        $sql_fields[] = $sqlDDL;

        // Check for primary key
        if (isset($this_field['primary_key'])) {
            if ($this_field['primary_key'] == true) {
                $primary_key[] = $field_name;
            }
        }
    }

    $sql = 'CREATE TABLE '.$tableName.' ('.implode(', ',$sql_fields);
    if (!empty($primary_key)) {
        $sql .= ', PRIMARY KEY ('.implode(',',$primary_key).')';
    }
    $sql .= ');';
    $sql .= $epilogue;

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
 * @throws BadParameterException
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDB__postgresqlAlterTable($tableName, $args)
{
    switch ($args['command']) {
        case 'add':
            if (empty($args['field'])) {
                throw new BadParameterException('args','Invalid parameter "#(1)" (field key must be set).');
            }
            $sql = 'ALTER TABLE '.$tableName.' ADD '.$args['field'].' ';
            // Get column definitions
            $this_field = xarDB__postgresColumnDefinition($args['field'], $args);
            // Add column values if they exist
            // Note:  PostgreSQL does not support default or null values in ALTER TABLE
            $sqlDDL = "";
            if (isset($this_field['type']))
                $sqlDDL = $sqlDDL . ' ' . $this_field['type'];
            $sql .= $sqlDDL;
            break;
        case 'rename':
            if (empty($args['new_name'])) {
                throw new BadParameterException('args','Invalid parameter "#(1)" (new_name key must be set.)');
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
                throw new BadParameterException('args','Invalid parameter "#(1)" (field key must be set).');
            }
            // check to make sure we have an action to perform on the colunm
            if (!empty($args['type']) || !empty($args['size']) || !empty($args['default']) || !empty($args['unsigned']) || !empty($args['increment']) || !empty($args['primary_key'])) {
                throw new BadParameterException('args','Modify does not currently support: type, size, default, unsigned, increment, or primary_key)');
            }

            // check to make sure we have an action to perform on the colunm
            if (empty($args['null']) && $args['null']!=false) {
                throw new BadParameterException('args','Invalid parameter "#(1)" (type,size,default,null, unsigned, increment, or primary_key must be set)');
            }

            // prep the first part of the query
            $sql = 'ALTER TABLE '.$tableName.' ALTER COLUMN '.$args['field'].' ';

            // see if the want to add or remove null
            if ($args['null']==false){
                $sql.='DROP NOT NULL';
            }
            if ($args['null']==true){
                $sql.='SET NOT NULL';
            }

            // break out of the case to return the modify sql
            break;
        default:
            throw new BadParameterException($args['command'],'Unknown command: "#(1)"');

    }
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
            if (isset($parameters['increment']) && $parameters['increment']) {
                // serial autocreates a sequence tablename_colname_seq
                $this_field['type'] = 'SERIAL';
                unset($parameters['default']); // taken care of
                unset($parameters['null']); // taken care of
                break;
            }
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

?>
