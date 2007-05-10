<?php
/**
 * Table Maintenance API for MySQL
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
        if (isset($this_field['type']))
            $sqlDDL = $sqlDDL . ' ' . $this_field['type'];

        // Oracle doesn't handle unsigned
        //if (isset($this_field['unsigned']))
        //    $sqlDDL = $sqlDDL . ' ' . $this_field['unsigned'];

        // Order of default and null clause matter
        if (isset($this_field['default']))
            $sqlDDL = $sqlDDL . ' ' . $this_field['default'];

        if (isset($this_field['null']))
            $sqlDDL = $sqlDDL . ' ' . $this_field['null'];

        // Oracle doesn't handle auto_increment - this should be a sequence
        //if (isset($this_field['auto_increment']))
        //    $sqlDDL = $sqlDDL . ' ' . $this_field['auto_increment'];

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
    $sql .= ')';

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
 * @throws BadParameterException
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDB__oracleAlterTable($tableName, $args)
{
    switch ($args['command']) {
        case 'add':
            if (empty($args['field'])) {
                throw new BadParameterException('args','Invalid parameter "#(1)" (field key must be set).');
            }
            $sql = 'ALTER TABLE '.$tableName.' ADD '.$args['field'].' ';
            // Get column definitions
            $this_field = xarDB__oracleColumnDefinition($args['field'], $args);
            // Add column values if they exist
            // Note:  Oracle does not support null values in ALTER TABLE
            $sqlDDL = "";
            if (isset($this_field['type']))
                $sqlDDL = $sqlDDL . ' ' . $this_field['type'];
            if (isset($this_field['default']))
                $sqlDDL = $sqlDDL . ' ' . $this_field['default'];
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
            $sql = 'ALTER TABLE '.$tableName.' MODIFY ('.$args['field'].' ';

            //since we don't allow type to be passed, check the db for type and derive type from
            // the existing schema. Also b/c the fetch mode may or may not be set to NUM, set it to
            // ASSOC so we don't have to loop through the entire returned array looking for are our one
            // field and field type
            $dbconn = xarDB::getConn();
            $dbInfo = $dbconn->getDatabaseInfo();
            $tblInfo = $dbInfo->getTable($tableName);
            $tableInfoArray = $tblInfo->getColumns();
            if (!empty($tableInfoArray[strtoupper($args['field'])]->type)){
                $sql.=$tableInfoArray[strtoupper($args['field'])]->type;
            }
            if (!empty($tableInfoArray[strtoupper($args['field'])]->max_length) && $tableInfoArray[strtoupper($args['field'])]->max_length!="-1"){
                $sql.='('.$tableInfoArray[strtoupper($args['field'])]->max_length.')';
            }

            // see if the want to add null
            if ($args['null']==false){
                $sql.=' NULL ';
            }
            if ($args['null']==true){
                $sql.=' NOT NULL ';
            }

            // add on closing paren
            $sql.=")";

            // break out of the case to return the modify sql
            break;
        default:
            throw new BadParameterException($args['command'],'Unknown command: "#(1)"');

    }
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

?>
