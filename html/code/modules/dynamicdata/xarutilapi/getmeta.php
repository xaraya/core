<?php
/**
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * (try to) get the "meta" properties of tables via db abstraction layer
 *
 * @param $args['table']  optional table you're looking for
 * @param $args['db']  optional database you're looking in (mysql only)
 * @return array of field definitions, or null on failure
 * @throws BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 * @todo split off the common parts which are also in getstatic.php
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

    $dbconn = xarDB::getConn();
    // dbInfo holds the meta information about the database
    $dbInfo = $dbconn->getDatabaseInfo();

    $dbtype = xarDB::getType();
    $dbname = xarDB::getName();
    if (empty($db)) {
        $db = $dbname;
    }

    // Note: not supported for other database types
    if ($dbtype == 'mysql' && $db == $dbname && !empty($table) && strpos($table,'.') !== false) {
        list($db, $table) = explode('.', $table);
    }

    // Note: this only works if we use the same database connection
    if (!empty($db) && $db != $dbname) {
        $dbInfo->selectDb($db);
        $prefix = $db . '.';
    } else {
        $prefix = '';
    }

    // Build an array of TableInfo objects
    if (!empty($table)) {
        $tables = array($dbInfo->getTable($table));
    } else {
        $tables = $dbInfo->getTables();
    }
    if (!isset($tables)) return;

    // Get the default property types
    sys::import('modules.dynamicdata.class.properties.master');
    $proptypes = DataPropertyMaster::getPropertyTypes();
    $proptypeid = array();
    foreach ($proptypes as $proptype) {
        $proptypeid[$proptype['name']] = $proptype['id'];
    }

    // Based on this, loop over the table info object and fill the metadata
    $metadata = array();
    foreach ($tables as $tblInfo) {
        $curtable = $prefix . $tblInfo->getName();
        if (isset($propertybag[$curtable])) {
             $metadata[$curtable] = $propertybag[$curtable];
             continue;
        }

        // Get the columns and the primary keys
        $fields = $tblInfo->getColumns();
        $keyInfo = $tblInfo->getPrimaryKey();
        $id = 1;
        $columns = array();
        foreach ($fields as $field) {
            $name = $field->getName();
            $datatype = $field->getNativeType();
            $size = $field->getSize();
            $default = $field->getDefaultValue();

            $label = strtr($name,'_',' ');
            // cosmetic for 1.x style xar_* field names
            $label = preg_replace('/^xar /','', $label);
            $label = ucwords($label);
            if (isset($columns[$name])) {
                $i = 1;
                while (isset($columns[$name . '_' . $i])) {
                    $i++;
                }
                $name = $name . '_' . $i;
                $label = $label . '_' . $i;
            }
            $status = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;

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
                    $proptype = $proptypeid['textbox']; // Text Box
                    if (!empty($size) && $size > 0) {
                        $validation = "0:$size";
                    }
                    break;
                case 'tinyint':
                    if ($size == 1) {
                        $proptype = $proptypeid['checkbox']; // Checkbox
                        $validation = '';
                    } else {
                        $proptype = $proptypeid['integerbox']; // Number Box
                    }
                    break;
                case 'int':
                case 'integer':
                case 'smallint':
                case 'mediumint':
                    $proptype = $proptypeid['integerbox']; // Number Box
                    if (!empty($size) && $size > 6) {
                        $validation = '';
                    }
                    break;
                case 'bigint':
                    $proptype = $proptypeid['integerbox']; // Number Box
                    break;
                case 'float':
                case 'decimal':
                case 'double':
                    $proptype = $proptypeid['floatbox']; // Number Box (float)
                    $validation = '';
                    break;
                // in case we have some leftover bit(1) columns instead of tinyint(1) for boolean in MySQL
                case 'bit':
                case 'boolean':
                    $proptype = $proptypeid['checkbox']; // Checkbox
                    $validation = '';
                    break;
                case 'date':
                case 'datetime':
                case 'timestamp':
                    $proptype = $proptypeid['calendar']; // Calendar
                    break;
                case 'text':
                case 'mediumtext':
                    $proptype = $proptypeid['textarea_medium']; // Medium Text Area
                    $status = DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY;
                    $validation = '';
                    break;
                case 'longtext':
                    $proptype = $proptypeid['textarea_large']; // Large Text Area
                    $status = DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY;
                    $validation = '';
                    break;
                case 'blob':       // caution, could be binary too !
                    $proptype = $proptypeid['textarea_medium']; // Medium Text Area
                    $status = DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY;
                    break;
                case 'enum':
                    $proptype = $proptypeid['dropdown']; // Dropdown
                    $validation = strtr($validation,array('enum(' => '', ')' => '', "'" => '', ',' => ';'));
                    break;
                default:
                    $proptype = $proptypeid['static']; // Static Text
                    break;
            }

            // try to figure out if it's the item id
            // FIXME: this only deals with primary keys which consist of 1 column
            // The mod_uservars table as such will be wrongly identified
            if(is_object($keyInfo) && $name == $keyInfo->getName()) {
                // CHECKME: how are multiple tuples handled here?
                // not allowed to modify primary key !
                $proptype = $proptypeid['itemid']; // Item ID
                $validation = '';
            }

            // JDJ: added 'primary' and 'autoincrement' fields.
            // If this causes a problem, it could be made optional.
            // It is used by the FlatTable datastore to determine the primary key.
            // Jojodee: is causing probs with sqlite at least in installer
            // made some changes - please review
            $columns[$name] = array('name' => $name,
                                   'label' => $label,
                                   'type' => $proptype,
                                   'id' => $id,
                                   'defaultvalue' => $default,
                                   'source' => $curtable . '.' . $name,
                                   'status' => $status,
                                   'seq' => $id,
                                   'validation' => $validation,
                                   'configuration' => $validation,
                                   //'primary' => isset($field->primary_key)?$field->primary_key : '',
                                   //'autoincrement' => isset($field->auto_increment))? $field->auto_increment : ''
                                   );
            if (isset($field->primary_key)) {
               $newelement=array('primary'=>$field->primary_key);
               array_merge($columns[$name],$newelement);
            }
            if (isset($field->auto_increment)) {
               $newelement=array('autoincrement'=>$field->auto_increment);
               array_merge($columns[$name],$newelement);

            }
            $id++;
        }
        $metadata[$curtable] = $columns;
        $propertybag[$curtable] = $columns;
    }

    // Note: this only works if we use the same database connection
    if (!empty($db) && $db != $dbname) {
        $dbInfo->selectDb($dbname);
    }

    return $metadata;
}
?>
