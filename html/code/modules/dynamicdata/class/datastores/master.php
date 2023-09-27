<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */

sys::import('xaraya.datastores.interface');

/**
 * Base class for DD objects
 */
class DDObject extends xarObject implements IDDObject
{
    public $name;
    public $schemaobject;

    public function __construct($name = null)
    {
        $this->name = $name ?? self::toString();
    }

    public function loadSchema(array $args = [])
    {
        $this->schemaobject = $this->readSchema($args);
    }

    public function readSchema(array $args = [])
    {
        extract($args);
        $module ??= '';
        $type ??= '';
        $func ??= '';
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

    public function toXML(SimpleXMLElement $schemaobject = null)
    {
        $schemaobject ??= $this->schemaobject;
        if (empty($schemaobject)) {
            return [];
        }
        return $schemaobject->asXML();
    }
}

/**
 * Factory Class to create Dynamic Data Stores
 *
 * @todo this factory should go into core once we use datastores in more broad ways.
 * @todo the classnames could use a bit of a clean up (shorter, lowercasing)
 */
class DataStoreFactory extends xarObject
{
    /**
     * Class method to get a new dynamic data store (of the right type)
     */
    public static function &getDataStore($name = '_dynamic_data_', $type = 'data')
    {
        switch ($type) {
            case 'relational':
                sys::import('xaraya.datastores.sql.relational');
                $datastore = new RelationalDataStore();
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
                $datastore = new CachingDataStore($name);
                break;
            default:
                sys::import('xaraya.datastores.sql.variabletable');
                $datastore = new VariableTableDataStore($name);
                break;
        }
        return $datastore;
    }

    public function getDataStores() {}

    /**
     * Get possible data sources
     *
     * @param $args['table'] optional extra table whose fields you want to add as potential data source
     */
    public static function &getDataSources($args = [])
    {
        $sources[] = ['id' => '', 'name' => xarML('None')];

        $dbconn = xarDB::getConn();
        $dbInfo = $dbconn->getDatabaseInfo();

        // TODO: re-evaluate this once we're further along
        $modules = xarMod::apiFunc('modules', 'admin', 'getlist', ['filter' => ['State' => xarMod::STATE_ACTIVE]]);
        /*
        foreach ($modules as $module) {
            $sources[] = array('id'=> $module['regid'], 'name' => 'module variable: ' . $module['name']);
        }
        */
        // try to get the meta table definition
        if (!empty($args)) {
            foreach ($args as $key => $value) {
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
        } else {
            $sources[] = ['id' => 'dynamicdata', 'name' => xarML('DynamicData')];
        }
        return $sources;
    }
}
