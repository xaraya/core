<?php
/**
 * @package core\datastores
 * @subpackage datastores
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */

namespace Xaraya\DataObject\DataStores;

use Xaraya\Database\ExternalDatabase;
use xarDB;
use xarObject;
use DataObject;
use SimpleXMLElement;
use BadParameterException;
use Exception;
use sys;

sys::import('xaraya.datastores.interface');

/**
 * Base class for DD objects datastore
 * @todo move xml schema elsewhere
 */
class DDObject extends xarObject implements IDDObject
{
    /** @var string */
    public $name;
    /** @var SimpleXMLElement */
    public $schemaobject;

    /**
     * Summary of __construct
     * @param ?string $name
     */
    public function __construct($name = null)
    {
        $this->name = $name ?? self::toString();
    }

    /**
     * Summary of loadSchema
     * @param array<string, mixed> $args
     * @return void
     */
    public function loadSchema(array $args = [])
    {
        $this->schemaobject = $this->readSchema($args);
    }

    /**
     * Summary of readSchema
     * @param array<string, mixed> $args
     * @throws \BadParameterException
     * @return SimpleXMLElement|bool
     */
    public function readSchema(array $args = [])
    {
        extract($args);
        $module ??= '';
        $type ??= '';
        $func ??= '';
        $file ??= '';
        if (!empty($module)) {
            $file = sys::code() . 'modules/' . $module . '/xar' . $type . '/' . $func . '.xml';
        }
        try {
            return simplexml_load_file($file);
        } catch (Exception $e) {
            throw new BadParameterException([$file], 'Bad or no xml file encountered: #(1)');
        }
    }

    //Stolen off http://it2.php.net/manual/en/ref.simplexml.php
    /**
     * Summary of toArray
     * @param SimpleXMLElement|null $schemaobject
     * @return array<mixed>|bool
     */
    public function toArray(SimpleXMLElement $schemaobject = null)
    {
        $schemaobject ??= $this->schemaobject;
        if (empty($schemaobject)) {
            return [];
        }
        $children = $schemaobject->children();
        $return = null;

        foreach ($children as $element => $value) {
            if ($value instanceof SimpleXMLElement) {
                $values = (array)$value->children();

                if (count($values) > 0) {
                    $return[$element] = $this->toArray($value);
                } else {
                    if (!isset($return[$element])) {
                        $return[$element] = (string)$value;
                    } else {
                        if (!is_array($return[$element])) {
                            $return[$element] = [$return[$element], (string)$value];
                        } else {
                            $return[$element][] = (string)$value;
                        }
                    }
                }
            }
        }

        if (is_array($return)) {
            return $return;
        } else {
            return false;
        }
    }

    /**
     * Summary of toXML
     * @param SimpleXMLElement|null $schemaobject
     * @return bool|string
     */
    public function toXML(SimpleXMLElement $schemaobject = null)
    {
        $schemaobject ??= $this->schemaobject;
        if (empty($schemaobject)) {
            return '';
        }
        return $schemaobject->asXML();
    }
}

/**
 * Factory Class to create Dynamic Data Stores
 *
 * @todo the classnames could use a bit of a clean up (shorter, lowercasing)
 */
class DataStoreFactory extends xarObject
{
    /**
     * Class method to get a new dynamic data store (of the right type)
     * @param string $name
     * @param string $type type of datastore (relational, data, hook, modulevars, cache, ...)
     * @param ?string $storage storageType for the cacheStorage in CachingDatastore
     * @param int|string|null $dbConnIndex connection index of the database if different from Xaraya DB (optional)
     * @param ?array<string, mixed> $dbConnArgs connection params of the database if different from Xaraya DB (optional)
     * @return IBasicDataStore
     */
    public static function &getDataStore($name = '_dynamic_data_', $type = 'data', $storage = null, $dbConnIndex = 0, $dbConnArgs = [])
    {
        switch ($type) {
            case 'relational':
                sys::import('xaraya.datastores.sql.relational');
                $datastore = new RelationalDataStore(null, $dbConnIndex);
                break;
                // case 'table':
                //     sys::import('xaraya.datastores.sql.flattable');
                //     $datastore = new FlatTableDataStore($name);
                //     break;
            case 'data':
                sys::import('xaraya.datastores.sql.variabletable');
                $datastore = new VariableTableDataStore($name);
                break;
            case 'hook':
                sys::import('xaraya.datastores.hook');
                $datastore = new HookDataStore($name);
                break;
                // case 'function':
                //     sys::import('xaraya.datastores.function');
                //     $datastore = new FunctionDataStore($name);
                //     break;
                // case 'uservars':
                //     sys::import('xaraya.datastores.usersettings');
                //     // TODO: integrate user variable handling with DD
                //     $datastore = new UserSettingsDataStore($name);
                //     break;
            case 'modulevars':
                sys::import('xaraya.datastores.sql.modulevariables');
                // TODO: integrate module variable handling with DD
                $datastore = new ModuleVariablesDataStore($name);
                break;

                // TODO: other data stores
                // case 'ldap':
                //     sys::import('xaraya.datastores.ldap');
                //     $datastore = new LDAPDataStore($name);
                //     break;
                // case 'xml':
                //     sys::import('xaraya.datastores.file.xml');
                //     $datastore = new XMLFileDataStore($name);
                //     break;
                // case 'csv':
                //     sys::import('xaraya.datastores.file.csv');
                //     $datastore = new CSVFileDataStore($name);
                //     break;
            case 'none':
                sys::import('xaraya.datastores.virtual');
                $datastore = new DummyDataStore($name);
                break;
            case 'cache':
                sys::import('xaraya.datastores.caching');
                $datastore = new CachingDataStore($name, $storage);
                break;
            case 'external':
                /** @uses \sys::autoload() */
                sys::import('xaraya.datastores.external');
                $datastore = ExternalDataStore::getDataStore($name, $dbConnIndex, $dbConnArgs);
                break;
            default:
                sys::import('xaraya.datastores.sql.variabletable');
                $datastore = new VariableTableDataStore($name);
                break;
        }
        return $datastore;
    }

    /**
     * Summary of getDataStores
     * @return void
     */
    public function getDataStores()
    {
    }

    /**
     * Get possible data sources
     *
     * @param DataObject|null $object
     * @return list<array<string, string>>
     */
    public static function &getDataSources($object = null)
    {
        $sources = [];
        $sources[] = ['id' => '', 'name' => xarML('None')];

        if (empty($object)) {
            $sources[] = ['id' => 'dynamicdata', 'name' => xarML('DynamicData')];
            return $sources;
        }

        if (empty($object->datasources) && !empty($object->sources)) {
            try {
                $object->datasources = unserialize($object->sources);
            } catch (Exception $e) {
            }
        }
        if (empty($object->datasources)) {
            $sources[] = ['id' => 'dynamicdata', 'name' => xarML('DynamicData')];
            return $sources;
        }

        sys::import('xaraya.database.external');
        $object->dbConnIndex = ExternalDatabase::checkDbConnection($object->dbConnIndex, $object->dbConnArgs);
        // use external database connection
        if (ExternalDatabase::isIndexExternal($object->dbConnIndex)) {
            return static::getExternalDataSources($object->datasources, $object->dbConnIndex);
        }

        $dbconn = xarDB::getConn($object->dbConnIndex);
        $dbInfo = $dbconn->getDatabaseInfo();

        // TODO: re-evaluate this once we're further along
        /*
        $modules = xarMod::apiFunc('modules', 'admin', 'getlist', ['filter' => ['State' => xarMod::STATE_ACTIVE]]);
        foreach ($modules as $module) {
            $sources[] = array('id'=> $module['regid'], 'name' => 'module variable: ' . $module['name']);
        }
        */
        // try to get the meta table definition
        foreach ($object->datasources as $key => $value) {
            if (is_array($value)) {
                $tablename = current($value);
                $tableobject = $dbInfo->getTable($tablename);
            } else {
                $tablename = $value;
                $tableobject = $dbInfo->getTable($tablename);
            }
            // Bail if we don't have an object
            if (!is_object($tableobject)) {
                $message = xarML("'#(1)' is not a valid table name. Go back and change it.", $tablename);
                throw new Exception($message);
            }

            $fields = $tableobject->getColumns();
            foreach ($fields as $field) {
                $sources[] = ['id' => $key . "." . $field->getName(), 'name' => $key . "." . $field->getName()];
            }
        }
        return $sources;
    }

    /**
     * Get possible data sources from external database
     *
     * @param array<mixed> $datasources object datasources
     * @param string $dbConnIndex connection index of the database if different from Xaraya DB (required)
     * @return list<array<string, string>>
     */
    public static function &getExternalDataSources($datasources = [], $dbConnIndex = '')
    {
        $sources = [];
        $sources[] = ['id' => '', 'name' => xarML('None')];

        // try to get the meta table definition
        foreach ($datasources as $key => $value) {
            if (is_array($value)) {
                $tablename = current($value);
            } else {
                $tablename = $value;
            }
            $columns = ExternalDatabase::listTableColumns($dbConnIndex, $tablename);
            foreach ($columns as $name => $datatype) {
                $sources[] = ['id' => $key . "." . $name, 'name' => $key . "." . $name];
            }
        }
        return $sources;
    }
}
