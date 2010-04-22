<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 */

sys::import('xaraya.datastores.interface');

/**
 * Base class for DD objects
 */
class DDObject extends Object implements IDDObject
{

    public $name;

    function __construct($name=null)
    {
        $this->name = isset($name) ? $name : self::toString();
    }

    function loadSchema(Array $args = array())
    {
        $this->schemaobject = $this->readSchema($args);
    }

    function readSchema(Array $args = array())
    {
        extract($args);
        $module = isset($module) ? $module : '';
        $type = isset($type) ? $type : '';
        $func = isset($func) ? $func : '';
        if (!empty($module)) {
            $file = sys::code() . 'modules/' . $module . '/xar' . $type . '/' . $func . '.xml';
        }
        try {
            return simplexml_load_file($file);
        } catch (Exception $e) {
            throw new BadParameterException(array($file),'Bad or no xml file encountered: #(1)');
        }
    }

    //Stolen off http://it2.php.net/manual/en/ref.simplexml.php
    function toArray(SimpleXMLElement $schemaobject=null)
    {
        $schemaobject = isset($schemaobject) ? $schemaobject : $this->schemaobject;
        if (empty($schemaobject)) return array();
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
                           $return[$element] = array($return[$element], (string)$value);
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

    function toXML(SimpleXMLElement $schemaobject=null)
    {
        $schemaobject = isset($schemaobject) ? $schemaobject : $this->schemaobject;
        if (empty($schemaobject)) return array();
        return $schemaobject->asXML();
    }
}

/**
 * Factory Class to create Dynamic Data Stores
 *
 * @todo this factory should go into core once we use datastores in more broad ways.
 * @todo the classnames could use a bit of a clean up (shorter, lowercasing)
 */
class DataStoreFactory extends Object
{
    /**
     * Class method to get a new dynamic data store (of the right type)
     */
    static function &getDataStore($name = '_dynamic_data_', $type = 'data')
    {
        switch ($type)
        {
            case 'relational':
                sys::import('xaraya.datastores.sql.relational');
                $datastore = new RelationalDataStore();
                break;
            case 'table':
                sys::import('xaraya.datastores.sql.flattable');
                $datastore = new FlatTableDataStore($name);
                break;
            case 'data':
                sys::import('xaraya.datastores.sql.variabletable');
                $datastore = new VariableTableDataStore($name);
                break;
            case 'hook':
                sys::import('xaraya.datastores.hook');
                $datastore = new HookDataStore($name);
                break;
            case 'function':
                sys::import('xaraya.datastores.function');
                $datastore = new FunctionDataStore($name);
                break;
            case 'uservars':
                sys::import('xaraya.datastores.usersettings');
                // TODO: integrate user variable handling with DD
                $datastore = new UserSettingsDataStore($name);
                break;
            case 'modulevars':
                sys::import('xaraya.datastores.sql.modulevariables');
                // TODO: integrate module variable handling with DD
                $datastore = new ModuleVariablesDataStore($name);
                break;

                // TODO: other data stores
            case 'ldap':
                sys::import('xaraya.datastores.ldap');
                $datastore = new LDAPDataStore($name);
                break;
            case 'xml':
                sys::import('xaraya.datastores.file.xml');
                $datastore = new XMLFileDataStore($name);
                break;
            case 'csv':
                sys::import('xaraya.datastores.file.csv');
                $datastore = new CSVFileDataStore($name);
                break;
            case 'none':
                sys::import('xaraya.datastores.virtual');
                $datastore = new DummyDataStore($name);
                break;
            default:
                sys::import('xaraya.datastores.sql.variabletable');
                $datastore = new VariableTableDataStore($name);
                break;
        }
        return $datastore;
    }

    function getDataStores()
    {
    }

    /**
     * Get possible data sources
     *
     * @param $args['table'] optional extra table whose fields you want to add as potential data source
     */
    static function &getDataSources($args = array())
    {
        $sources[] = array('id' => '', 'name' => xarML('None'));

        $dbconn = xarDB::getConn();
        $dbInfo = $dbconn->getDatabaseInfo();

        // TODO: re-evaluate this once we're further along
        $modules = xarMod::apiFunc('modules', 'admin', 'getlist', array('filter' => array('State' => XARMOD_STATE_ACTIVE)));
        foreach ($modules as $module) {
            $sources[] = array('id'=> $module['regid'], 'name' => 'module variable: ' . $module['name']);
        }
        // try to get the meta table definition
        if (!empty($args))
        {
            foreach ($args as $key => $value) {
                if (is_array($value)){
                    $tableobject = $dbInfo->getTable(current($value));
                } else {
                    $tableobject = $dbInfo->getTable($value);
                }
                $fields = $tableobject->getColumns();
                foreach ($fields as $field) {
                    $sources[] = array('id'=> $key . "." . $field->getName(), 'name' => $key . "." . $field->getName());
                }
            }
        } else {
            $sources[] = array('id'=> 'dynamicdata', 'name' => xarML('DynamicData'));
        }
        return $sources;
    }
}
?>
