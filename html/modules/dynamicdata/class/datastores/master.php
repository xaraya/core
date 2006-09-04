<?php
/**
 * Base class for Xaraya objects
 * Needed as a common base for the extensions
 * This class is kept generic enough to serve as a base for a wider collection of Xaraya objects
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
**/
	class XarayaObject {

		function toString() {
			return get_class($this) . ":" . $this->hash();
		}
		function equals($object) {
			return $this === $object;
		}
		function getClass() {
			return get_class($this);
		}
		function hash() {
			return sha1(serialize($this));
		}
	}

/**
 * Base class for DD objects
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
**/
	class XarayaDDObject extends XarayaObject {

		public $name;

		function __construct($name=null)
		{
			$this->name = isset($name) ? $name : parent::toString();
		}

		function loadSchema($args = array())
		{
			$this->schemaobject = $this->readSchema($args);
		}

		function readSchema($args = array())
		{
			extract($args);
			$module = isset($module) ? $module : '';
			$type = isset($type) ? $type : '';
			$func = isset($func) ? $func : '';
			if (!empty($module)) {
				$file = 'modules/' . $module . '/xar' . $type . '/' . $func . '.xml';
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
				return $false;
			}
		}

		function toString() {
			return $this->name;
		}

		function toXML(SimpleXMLElement $schemaobject=null)
		{
			$schemaobject = isset($schemaobject) ? $schemaobject : $this->schemaobject;
			if (empty($schemaobject)) return array();
			return $schemaobject->asXML();
		}
	}

/**
 * Factory Class to creazte Dynamic Data Stores
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 * @todo this factory should go into core once we use datastores in more broad ways.
 * @todo the classnames could use a bit of a clean up (shorter, lowercasing)
 */
class DataStoreFactory extends XarayaObject
{
    /**
     * Class method to get a new dynamic data store (of the right type)
     */
    static function &getDataStore($name = '_dynamic_data_', $type = 'data')
    {
        switch ($type)
        {
            case 'table':
                sys::import('datastores.sql.flattable');
                $datastore = new Dynamic_FlatTable_DataStore($name);
                break;
            case 'data':
                sys::import('datastores.sql.variabletable');
                $datastore = new Dynamic_VariableTable_DataStore($name);
                break;
            case 'hook':
                sys::import('datastores.hook');
                $datastore = new Dynamic_Hook_DataStore($name);
                break;
            case 'function':
                sys::import('datastores.function');
                $datastore = new Dynamic_Function_DataStore($name);
                break;
            case 'uservars':
                sys::import('datastores.usersettings');
                // TODO: integrate user variable handling with DD
                $datastore = new Dynamic_UserSettings_DataStore($name);
                break;
            case 'modulevars':
                sys::import('datastores.modulevariables');
                // TODO: integrate module variable handling with DD
                $datastore = new Dynamic_ModuleVariables_DataStore($name);
                break;

                // TODO: other data stores
            case 'ldap':
                sys::import('datastores.ldap');
                $datastore = new Dynamic_LDAP_DataStore($name);
                break;
            case 'xml':
                sys::import('datastores.file.xml');
                $datastore = new Dynamic_XMLFile_DataStore($name);
                break;
            case 'csv':
                sys::import('datastores.file.csv');
                $datastore = new Dynamic_CSVFile_DataStore($name);
                break;
            case 'dummy':
            default:
                sys::import('datastores.dummy');
                $datastore = new Dynamic_Dummy_DataStore($name);
                break;
        }
        return $datastore;
    }

    function getDataStores()
    {
    }

    /**
     * Get possible data sources (// TODO: for a module ?)
     *
     * @param $args['table'] optional extra table whose fields you want to add as potential data source
     */
    static function &getDataSources($args = array())
    {
        $sources = array();

        // default data source is dynamic data
        $sources[] = 'dynamic_data';

        // module variables
        $sources[] = 'module variables';

        // user settings (= user variables per module)
        $sources[] = 'user settings';

        // session variables // TODO: perhaps someday, if this makes sense
        //$sources[] = 'session variables';

        // TODO: re-evaluate this once we're further along
        // hook modules manage their own data
        $sources[] = 'hook module';

        // user functions manage their own data
        $sources[] = 'user function';

        // no local storage
        $sources[] = 'dummy';

        // try to get the meta table definition
        if (!empty($args['table']))
        {
            try
            {
                $meta = xarModAPIFunc('dynamicdata','util','getmeta',$args);
            }
            catch ( NotFoundExceptions $e )
            {
                // No worries
            }
            if (!empty($meta) && !empty($meta[$args['table']]))
            {
                foreach ($meta[$args['table']] as $column)
                    if (!empty($column['source']))
                        $sources[] = $column['source'];
            }
        }

        $dbconn =& xarDBGetConn();
        $dbInfo = $dbconn->getDatabaseInfo();
        $dbTables = $dbInfo->getTables();
        foreach($dbTables as $tblInfo)
        {
            $tblColumns = $tblInfo->getColumns();
            foreach($tblColumns as $colInfo)
                $sources[] = $tblInfo->getName().".".$colInfo->getName();
        }
        return $sources;
    }
}
?>