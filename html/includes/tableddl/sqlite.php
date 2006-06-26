<?php
/**
 * Table Maintenance API for SQLite
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
 * SQLite specific function to alter a table
 *
 * @access private
 * @param tableName the table to alter
 * @param args['command'] command to perform on the table
 * @param args['field'] name of column to modify
 * @param args['after_field']
 * @param args['new_name'] new name of table
 * @return string|false sqlite specific sql to alter a table
 * @throws BadParameterException
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDB__sqliteAlterTable($tableName, $args) 
{
    switch ($args['command']) {
        case 'add':
            if (empty($args['field'])) {
                throw new BadParameterException('args','Invalid parameter "#(1)" (field key must be set).');
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
                throw new BadParameterException('args','Invalid parameter "#(1)" (new_name key must be set.)');
            }
            $sql = 'ALTER TABLE '.$tableName.' RENAME TO '.$args['new_name'];
            break;
        default:
            throw new BadParameterException($args['command'],'Unknown command: "#(1)"');
        }

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
