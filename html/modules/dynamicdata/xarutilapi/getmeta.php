<?php
/**
 * (try to) get the "meta" properties of tables 
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * (try to) get the "meta" properties of tables via db abstraction layer
 *
 * @author the DynamicData module development team
 * @param $args['table']  optional table you're looking for
 * @returns mixed
 * @return array of field definitions, or null on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_utilapi_getmeta($args)
{
    static $propertybag = array();

    extract($args);

    if (empty($table)) {
        $table = '';
    } elseif (isset($propertybag[$table])) {
        return array($table => $propertybag[$table]);
    }

    $dbconn =& xarDBGetConn();
    // dbInfo holds the meta information about the database 
    $dbInfo =& $dbconn->getDatabaseInfo();

    // Note: this only works if we use the same database connection
    if (!empty($db) && $db != $dbInfo->getName()) {
        $dbconn->SelectDB($db);
        $prefix = $db . '.';
    } else {
        $prefix = '';
    }

    // Build an array of TableInfo objects
    if (!empty($table)) {
        $tables = array($dbInfo->getTable($table));
    } else {
        $tables =& $dbInfo->getTables();
    }
    if (!isset($tables)) return;

    // Based on this, loop over the table info object and fill the metadata
    $metadata = array();
    foreach ($tables as $tblInfo) {
        $curtable = $prefix . $tblInfo->getName();
        if (isset($propertybag[$curtable])) {
             $metadata[$curtable] = $propertybag[$curtable];
             continue;
        }
        
        // Get the columns and the primary keys
        $fields =& $tblInfo->getColumns();
        $keyInfo = $tblInfo->getPrimaryKey();
        $id = 1;
        $columns = array();
        foreach ($fields as $field) {
            $fieldname = $field->getName();
            $datatype = $field->getType();
            $size = $field->getSize();

            // assign some default label for now, by removing the first part (xar_)
            $name = preg_replace('/^.+?_/','',$fieldname);
            $label = strtr($name,'_',' ');
            $label = ucwords($label);
            if (isset($columns[$name])) {
                $i = 1;
                while (isset($columns[$name . '_' . $i])) {
                    $i++;
                }
                $name = $name . '_' . $i;
                $label = $label . '_' . $i;
            }
            $status = 1;

            // assign some default validation for now
            $validation = $datatype;
            $validation .= (empty($size) || $size < 0) ? '' : ' (' . $size . ')';

            // (try to) assign some default property type for now
            // = obviously limited to basic data types in this case
            $dtype = $datatype;
            // skip special definitions (unsigned etc.)
            $dtype = preg_replace('/\(.*$/','',$dtype);
            switch ($dtype) {
                case 'char':
                case 'varchar':
                    $proptype = 2; // Text Box
                    if (!empty($size) && $size > 0) {
                        $validation = "0:$size";
                    }
                    break;
                case 'int':
                case 'integer':
                case 'tinyint':
                case 'smallint':
                case 'mediumint':
                    if ($size == 1) {
                        $proptype = 14; // Checkbox
                    } else {
                        $proptype = 15; // Number Box
                    }
                    break;
                case 'float':
                case 'decimal':
                case 'double':
                    $proptype = 17; // Number Box (float)
                    break;
                case 'boolean':
                    $proptype = 14; // Checkbox
                    break;
                case 'date':
                case 'datetime':
                case 'timestamp':
                    $proptype = 8; // Calendar
                    break;
                case 'text':
                    $proptype = 4; // Medium Text Area
                    $status = 2;
                    break;
                case 'longtext':
                    $proptype = 5; // Large Text Area
                    $status = 2;
                    break;
                case 'blob':       // caution, could be binary too !
                    $proptype = 4; // Medium Text Area
                    $status = 2;
                    break;
                case 'enum':
                    $proptype = 6; // Dropdown
                    $validation = strtr($validation,array('enum(' => '', ')' => '', "'" => '', ',' => ';'));
                    break;
                default:
                    $proptype = 1; // Static Text
                    break;
            }

            // try to figure out if it's the item id
            // FIXME: this only deals with primary keys which consist of 1 column
            // The mod_uservars table as such will be wrongly identified
            if(is_object($keyInfo) && $fieldname == $keyInfo->getName()) {
                // CHECKME: how are multiple tuples handled here?
                // not allowed to modify primary key !
                $proptype = 21; // Item ID
            }

            $columns[$name] = array('name' => $name,
                                   'label' => $label,
                                   'type' => $proptype,
                                   'id' => $id,
                                   'default' => '', // unknown here
                                   'source' => $curtable . '.' . $fieldname,
                                   'status' => $status,
                                   'order' => $id,
                                   'validation' => $validation);
            $id++;
        }
        $metadata[$curtable] = $columns;
        $propertybag[$curtable] = $columns;
    }

    // Note: this only works if we use the same database connection
    if (!empty($db) && $db != xarDBGetName()) {
        $dbconn->SelectDB(xarDBGetName());
    }

    return $metadata;
}

?>
