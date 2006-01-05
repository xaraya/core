<?php
/**
 * Table Maintenance API for other databases (using xarDataDict)
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
 * Generate the DataDict specific SQL to create a table
 *
 * @access private
 * @param tableName the physical table name
 * @param fields an array containing the fields to create
 * @return string|false the generated SQL statement, or false on failure
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDB__datadictCreateTable($tableName, $fields)
{
    $sql_fields = array();

    while (list($field_name, $parameters) = each($fields)) {
        $this_field = xarDB__datadictColumnDefinition($field_name, $parameters);
        if (empty($this_field)) continue;

        $sql_fields[] = $field_name .' '
                      . $this_field['type'] .' '
                      . $this_field['unsigned'] .' '
                      . $this_field['null'] .' '
                      . $this_field['default'] .' '
                      . $this_field['auto_increment'] .' '
                      . $this_field['primary_key'];
    }

    $datadict =& xarDB__datadictInit();
    $sql = $datadict->dict->CreateTableSQL($tableName, join(', ',$sql_fields));

    if (isset($sql) && is_array($sql)) {
    // CHECKME: will this work for multiple statements ?
        return join('; ',$sql);
    } else {
        return $sql;
    }
}

/**
 * DataDict specific function to alter a table
 *
 * @access private
 * @param tableName the table to alter
 * @param args['command'] command to perform on the table
 * @param args['field'] name of column to modify
 * @param args['after_field']
 * @param args['new_name'] new name of table
 * @return string|false datadict specific sql to alter a table
 * @raise BAD_PARAM
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDB__datadictAlterTable($tableName, $args)
{
    switch ($args['command']) {
        case 'add':
            if (empty($args['field'])) {
                throw new BadParameterException('args','Invalid parameter "#(1)", the "fields" key must be set');
            }
            $coldef = xarDB__datadictColumnDefinition($args['field'],$args);
            if (empty($coldef)) {
                throw new BadParameterException('args','Invalid parameter "#(1)" (type,size,default,null, unsigned, increment, or primary_key must be set)');
            }
            $fields = $args['field'] .' '
                . $coldef['type'] . ' '
                . $coldef['unsigned'] . ' '
                . $coldef['null'] . ' '
                . $coldef['default'] . ' '
                . $coldef['auto_increment'] . ' '
                . $coldef['primary_key'];

            // Generate SQL to add a column to the table
            $datadict =& xarDB__datadictInit();
            $sql = $datadict->dict->AddColumnSQL($tableName, $fields);

            break;

        case 'rename':
            if (empty($args['new_name'])) {
                throw new BadParameterException('args','Invalid parameter "#(1)" (new_name key must be set.)');
            }

            // Generate SQL to rename the table
            $datadict =& xarDB__datadictInit();
            $sql = $datadict->dict->RenameTableSQL($tableName,$args['new_name']);

            break;

        case 'modify':
            if (empty($args['field'])) {
                throw new BadParameterException('args','Invalid parameter "#(1)" (field key must be set).');
            }
            $coldef = xarDB__datadictColumnDefinition($args['field'],$args);
            if (empty($coldef)) {
                throw new BadParameterException('args','Invalid parameter "#(1)" (type,size,default,null, unsigned, increment, or primary_key must be set)');
            }
            $fields = $args['field'] .' '
                . $coldef['type'] . ' '
                . $coldef['unsigned'] . ' '
                . $coldef['null'] . ' '
                . $coldef['default'] . ' '
                . $coldef['auto_increment'] . ' '
                . $coldef['primary_key'];

            // Generate SQL to modify a column in the table
            $datadict =& xarDB__datadictInit();
            $sql = $datadict->dict->AlterColumnSQL($tableName, $fields);

            break;

        default:
            throw new BadParameterException($args['command'],'Unknown command: "#(1)"');
    }

    if (isset($sql) && is_array($sql)) {
    // CHECKME: will this work for multiple statements ?
        return join('; ',$sql);
    } else {
        return $sql;
    }
}

/**
 * DataDict specific column type generation - adapted from a d o d b-mysql.inc.php mapping
 *
 * @access private
 * @param field_name
 * @param parameters
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDB__datadictColumnDefinition($field_name, $parameters)
{
    $this_field = array();

    switch($parameters['type']) {

        case 'integer':
            if (empty($parameters['size'])) {
                $parameters['size'] = 'int';
            }
            switch ($parameters['size']) {
                case 'tiny':
                    $this_field['type'] = 'I1';
                    break;
                case 'small':
                    $this_field['type'] = 'I2';
                    break;
                case 'medium':
                    $this_field['type'] = 'I4';
                    break;
                case 'big':
                    $this_field['type'] = 'I8';
                    break;
                default:
                    $this_field['type'] = 'I';
            } // switch ($parameters['size'])
            break;

        case 'char':
            if (empty($parameters['size'])) {
                return false;
            } else {
                $this_field['type'] = 'C('.$parameters['size'].')';
            }
            break;

        case 'varchar':
            if (empty($parameters['size'])) {
                return false;
            } else {
                $this_field['type'] = 'C('.$parameters['size'].')';
            }
            break;

        case 'text':
            if (empty($parameters['size'])) {
                $parameters['size'] = 'text';
            }
            switch ($parameters['size']) {
                case 'tiny':
                    $this_field['type'] = 'C';
                    break;
                case 'medium':
                    $this_field['type'] = 'X';
                    break;
                case 'long':
                    $this_field['type'] = 'X';
                    break;
                default:
                    $this_field['type'] = 'X';
            }
            break;

        case 'blob':
            if (empty($parameters['size'])) {
                $parameters['size'] = 'blob';
            }
            switch ($parameters['size']) {
                case 'tiny':
                    $this_field['type'] = 'C';
                    break;
                case 'medium':
                    $this_field['type'] = 'B';
                    break;
                case 'long':
                    $this_field['type'] = 'B';
                    break;
                default:
                    $this_field['type'] = 'B';
            }
            break;

        case 'boolean':
            $this_field['type'] = "L";
            break;

        case 'datetime':
            $this_field['type'] = "T";
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
            $this_field['type'] = "D";
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
                    $data_type = 'F';
                    break;
                case 'decimal':
                    $data_type = 'N';
                    break;
                default:
                    $data_type = 'F';
            }
            if (isset($parameters['width']) && isset($parameters['decimals'])) {
               $data_type = 'N('.$parameters['width'].'.'.$parameters['decimals'].')';
            }
            $this_field['type'] = $data_type;
            break;
        // Added Time field via marsel@phatcom.net (David Taylor)
        case 'time':
            $this_field['type'] = "T";
            break;
        case 'timestamp':
            if (empty($parameters['size'])) {
                $parameters['size'] = 'timestamp';
            }
            switch ($parameters['size']) {
                case 'YY':
                    $this_field['type'] = 'T';
                    break;
                case 'YYYY':
                    $this_field['type'] = 'T';
                    break;
                case 'YYYYMM':
                    $this_field['type'] = 'T';
                    break;
                case 'YYYYMMDD':
                    $this_field['type'] = 'T';
                    break;
                case 'YYYYMMDDHH':
                    $this_field['type'] = 'T';
                    break;
                case 'YYYYMMDDHHMM':
                    $this_field['type'] = 'T';
                    break;
                case 'YYYYMMDDHHMMSS':
                    $this_field['type'] = 'T';
                    break;
                default:
                    $this_field['type'] = 'T';
            }
            break;

        // undefined type
        default:
            return false;
    }

    // Test for UNSIGNED
    $this_field['unsigned'] = (isset($parameters['unsigned']) && $parameters['unsigned'] == true)
                            ? ''
                            : '';

    // Test for NO NULLS
    $this_field['null']    = (isset($parameters['null']) && $parameters['null'] == false)
                        ? 'NOTNULL'
                        : '';

    // Test for DEFAULTS
    $this_field['default'] = (isset($parameters['default']))
                           ? (($parameters['default'] == 'NULL')
                                    ? 'DEFAULT NULL'
                                    : "DEFAULT '".$parameters['default']."'")
                           : '';

    // Test for AUTO_INCREMENT
    $this_field['auto_increment'] = (isset($parameters['increment']) && $parameters['increment'] == true)
                                  ? 'AUTO'
                                  : '';

    // Bug #744 - Check "increment_start" field so that Other increment field will start at the appropriate startid
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
                               ? 'PRIMARY'
                               : '';

    return $this_field;
}

// OTHER FUNCTIONS FROM xarTableDDL.php

/**
 * Generate the SQL to create a database
 *
 * @access private
 * @param databaseName
 * @return string sql statement for database creation
 * @raise BAD_PARAM
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDB__datadictCreateDatabase($databaseName)
{
    $datadict =& xarDB__datadictInit();
    $sql = $datadict->dict->CreateDatabase($databaseName);

    if (isset($sql) && is_array($sql)) {
    // CHECKME: will this work for multiple statements ?
        return join('; ',$sql);
    } else {
        return $sql;
    }
}

/**
 * Generate the DataDict specific SQL to drop a table
 *
 * @access private
 * @param tableName the physical table name
 * @return string|false the generated SQL statement, or false on failure
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDB__datadictDropTable($tableName)
{
    $datadict =& xarDB__datadictInit();
    $sql = $datadict->dict->DropTableSQL($tableName);

    if (isset($sql) && is_array($sql)) {
    // CHECKME: will this work for multiple statements ?
        return join('; ',$sql);
    } else {
        return $sql;
    }
}

/**
 * Generate the SQL to create a table index
 *
 * @param tableName the physical table name
 * @param index an array containing the index name, type and fields array
 * @return string|false the generated SQL statement, or false on failure
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDB__datadictCreateIndex($tableName, $index)
{
    $datadict =& xarDB__datadictInit();
    $sql = $datadict->dict->CreateIndexSQL($index['name'], $tableName, $index['fields']);

    if (isset($sql) && is_array($sql)) {
    // CHECKME: will this work for multiple statements ?
        return join('; ',$sql);
    } else {
        return $sql;
    }
}

/**
 * Generate the SQL to drop an index
 *
 * @access private
 * @param tableName
 * @param name a db index name
 * @return string|false generated sql to drop an index
 * @raise BAD_PARAM
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDB__datadictDropIndex($tableName, $index)
{
    $datadict =& xarDB__datadictInit();
    $sql = $datadict->dict->DropIndexSQL($index['name'], $tableName);

    if (isset($sql) && is_array($sql)) {
    // CHECKME: will this work for multiple statements ?
        return join('; ',$sql);
    } else {
        return $sql;
    }
}

/**
 * Initialize data dictionary
 */
function &xarDB__datadictInit()
{
    static $datadict = null;

// CHECKME: what if we want to change stuff in another database ?
//          The xarTableDDL API doesn't really provide for this

    if (!isset($datadict)) {
        $dbconn =& xarDBGetConn();
        $datadict =& xarDBNewDataDict($dbconn, 'ALTERTABLE');
    }

    return $datadict;
}

?>
