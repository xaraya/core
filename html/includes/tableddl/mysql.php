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
                throw new BadParameterException('args','Invalid parameter "#(1)" (field key must be set).');
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
            if (empty($args['null']) && $args['null']!=FALSE) {
                throw new BadParameterException('args','Invalid parameter "#(1)" (type,size,default,null, unsigned, increment, or primary_key must be set)');
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
            throw new BadParameterException($args['command'],'Unknown command: "#(1)"');

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

?>
