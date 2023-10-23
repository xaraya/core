<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\DataObject;

use Xaraya\Core\Traits\DatabaseInterface;
use Xaraya\Core\Traits\DatabaseTrait;
use DataObjectMaster;
use DataPropertyMaster;
use BadParameterException;
use xarDB;
use sys;

sys::import('xaraya.traits.databasetrait');

/**
 * Class to handle the dynamicdata util API
 */
class UtilApi implements DatabaseInterface
{
    use DatabaseTrait;

    protected static string $moduleName = 'dynamicdata';
    /** @var array<string, int> */
    protected static array $propTypeIds = [];

    public static function getObjectConfig($objectname, $item = null)
    {
        $item ??= DataObjectMaster::getObjectInfo(['name' => $objectname]);
        if (empty($item) || $item['name'] !== $objectname) {
            throw new BadParameterException($objectname, 'Invalid object name #(1)');
        }
        $configuration = [
            'name' => $objectname,
        ];
        if (!empty($item['config'])) {
            $configuration = unserialize($item['config']);
        }
        if (!empty($configuration['dbConnArgs']) && is_string($configuration['dbConnArgs'])) {
            $configuration['dbConnArgs'] = json_decode($configuration['dbConnArgs'], true);
            if (is_callable($configuration['dbConnArgs'])) {
                $configuration['callable'] = [
                    'class' => $configuration['dbConnArgs'][0],
                    'method' => $configuration['dbConnArgs'][1],
                ];
                $configuration['dbConnArgs'] = [];
            }
        }
        $configuration = array_merge($configuration, $item);
        return $configuration;
    }

    /**
     * (try to) get the "meta" properties of tables via db abstraction layer
     *
     * @param string $table name of the database table (required)
     * @param ?string $db optional database you're looking in (mysql only)
     * @param int|string $dbConnIndex connection index of the database if different from Xaraya DB (optional)
     * @param array<string, mixed> $dbConnArgs connection params of the database if different from Xaraya DB (optional)
     * @return array<string, array<string, mixed>>|void of field definitions, or null on failure
     */
    public static function getMeta($table, $db = null, $dbConnIndex = 0, $dbConnArgs = [])
    {
        /** @var array<string, array<string, mixed>> */
        static $propertybag = [];

        if (empty($table)) {
            $table = '';
        } elseif (isset($propertybag[$table])) {
            return [$table => $propertybag[$table]];
        }

        if (empty($dbConnArgs)) {
            $dbconn = xarDB::getConn($dbConnIndex);
        } else {
            // open a new database connection
            $dbconn = xarDB::newConn($dbConnArgs);
            // save the connection index
            $dbConnIndex = xarDB::getConnIndex();
        }
        // dbInfo holds the meta information about the database
        $dbInfo = $dbconn->getDatabaseInfo();

        // Note: not applicable for dbConnIndex > 0
        $dbtype = xarDB::getType();
        $dbname = xarDB::getName();
        if (empty($db)) {
            $db = $dbname;
        }

        // Note: not supported for other database types
        if ($dbtype == 'mysqli' && $db == $dbname && !empty($table) && strpos($table, '.') !== false) {
            [$db, $table] = explode('.', $table);
        }

        // Note: this only works if we use the same database connection
        if (!empty($db) && $db != $dbname && empty($dbConnIndex)) {
            $dbInfo->selectDb($db);
            $prefix = $db . '.';
        } else {
            $prefix = '';
        }

        // Build an array of TableInfo objects
        if (!empty($table)) {
            $tables = [$dbInfo->getTable($table)];
        } else {
            $tables = $dbInfo->getTables();
        }
        if (!isset($tables)) {
            return;
        }

        // Based on this, loop over the table info object and fill the metadata
        $metadata = [];
        foreach ($tables as $tblInfo) {
            $curtable = $prefix . $tblInfo->getName();
            if (isset($propertybag[$curtable])) {
                $metadata[$curtable] = $propertybag[$curtable];
                continue;
            }

            $metadata[$curtable] = static::getTableInfo($curtable, $tblInfo);
            $propertybag[$curtable] = $metadata[$curtable];
        }

        // Note: this only works if we use the same database connection
        if (!empty($db) && $db != $dbname && empty($dbConnIndex)) {
            $dbInfo->selectDb($dbname);
        }

        return $metadata;
    }

    /**
     * Summary of getTableInfo
     * @param string $curtable
     * @param \TableInfo|\PDOTable $tblInfo
     * @return array<string, array<string, mixed>>
     */
    public static function getTableInfo($curtable, $tblInfo)
    {
        // Get the columns and the primary keys
        $fields = $tblInfo->getColumns();
        $keyInfo = $tblInfo->getPrimaryKey();
        $id = 1;
        $columns = [];
        foreach ($fields as $field) {
            /** @var \ColumnInfo|\PDOColumn $field */
            $name = (string) $field->getName();
            $datatype = $field->getNativeType();
            $size = $field->getSize();
            $default = $field->getDefaultValue();

            $label = strtr($name, '_', ' ');
            // cosmetic for 1.x style xar_* field names
            $label = preg_replace('/^xar /', '', $label);
            $label = ucwords((string) $label);
            if (isset($columns[$name])) {
                $i = 1;
                while (isset($columns[$name . '_' . $i])) {
                    $i++;
                }
                $name = $name . '_' . $i;
                $label = $label . '_' . $i;
            }

            // try to figure out if it's the item id
            // FIXME: this only deals with primary keys which consist of 1 column
            // The mod_uservars table as such will be wrongly identified
            if(is_object($keyInfo) && $name == $keyInfo->getName()) {
                // CHECKME: how are multiple tuples handled here?
                // not allowed to modify primary key !
                $datatype = 'itemid';
            }

            [$proptype, $configuration, $status] = static::mapPropertyType($datatype, $size);

            // JDJ: added 'primary' and 'autoincrement' fields.
            // If this causes a problem, it could be made optional.
            // It is used by the FlatTable datastore to determine the primary key.
            // Jojodee: is causing probs with sqlite at least in installer
            // made some changes - please review
            $columns[$name] = [
                'name' => $name,
                'label' => $label,
                'type' => $proptype,
                'id' => $id,
                'defaultvalue' => $default,
                'source' => $curtable . '.' . $name,
                'status' => $status,
                'seq' => $id,
                'configuration' => $configuration,
                //'primary' => isset($field->primary_key)?$field->primary_key : '',
                //'autoincrement' => isset($field->auto_increment))? $field->auto_increment : ''
            ];
            if (isset($field->primary_key)) {
                $newelement = ['primary' => $field->primary_key];
                $columns[$name] = array_merge($columns[$name], $newelement);
            }
            if (isset($field->auto_increment)) {
                $newelement = ['autoincrement' => $field->auto_increment];
                $columns[$name] = array_merge($columns[$name], $newelement);
            }
            $id++;
        }
        return $columns;
    }

    /**
     * Summary of mapPropertyType
     * @param string $datatype
     * @param string|int $size
     * @return array{0: int, 1: string, 2: int}
     */
    public static function mapPropertyType($datatype, $size)
    {
        $proptype = '';
        $status = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;

        $proptypeid = static::getPropTypeIds();

        // assign some default validation for now
        $configuration = $datatype;
        $configuration .= (empty($size) || $size < 0) ? '' : ' (' . $size . ')';

        // (try to) assign some default property type for now
        // = obviously limited to basic data types in this case
        $dtype = strtolower($datatype);
        // skip special definitions (unsigned etc.)
        $dtype = preg_replace('/\(.*$/', '', $dtype);
        switch ($dtype) {
            case 'itemid':
                $proptype = $proptypeid['itemid']; // Item ID
                $configuration = '';
                break;
            case 'char':
            case 'varchar':
                $proptype = $proptypeid['textbox']; // Text Box
                if (!empty($size) && $size > 0) {
                    $configuration = "0:" . strval($size);
                }
                break;
            case 'tinyint':
                if ($size == 1) {
                    $proptype = $proptypeid['checkbox']; // Checkbox
                    //$configuration = '';
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
                    $configuration = '';
                } elseif (empty($size)) {
                    $configuration = '';
                }
                break;
            case 'bigint':
                $proptype = $proptypeid['integerbox']; // Number Box
                break;
            case 'float':
            case 'decimal':
            case 'double':
            case 'real':
                $proptype = $proptypeid['floatbox']; // Number Box (float)
                $configuration = '';
                break;
                // in case we have some leftover bit(1) columns instead of tinyint(1) for boolean in MySQL
            case 'bit':
            case 'bool':
            case 'boolean':
                $proptype = $proptypeid['checkbox']; // Checkbox
                $configuration = '';
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
                $configuration = '';
                break;
            case 'longtext':
                $proptype = $proptypeid['textarea_large']; // Large Text Area
                $status = DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY;
                $configuration = '';
                break;
            case 'blob':       // caution, could be binary too !
                $proptype = $proptypeid['textarea_medium']; // Medium Text Area
                $status = DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY;
                break;
            case 'enum':
                $proptype = $proptypeid['dropdown']; // Dropdown
                $configuration = strtr($configuration, ['enum(' => '', ')' => '', "'" => '', ',' => ';']);
                break;
            default:
                $proptype = $proptypeid['static']; // Static Text
                break;
        }

        return [$proptype, $configuration, $status];
    }

    /**
     * Summary of getPropTypeIds
     * @return array<string, int>
     */
    public static function getPropTypeIds()
    {
        if (count(static::$propTypeIds) > 0) {
            return static::$propTypeIds;
        }
        // Get the default property types
        sys::import('modules.dynamicdata.class.properties.master');
        $proptypes = DataPropertyMaster::getPropertyTypes();
        static::$propTypeIds = [];
        foreach ($proptypes as $proptype) {
            static::$propTypeIds[(string) $proptype['name']] = (int) $proptype['id'];
        }
        return static::$propTypeIds;
    }
}
