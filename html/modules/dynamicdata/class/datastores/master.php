<?php
/**
 * Utility Class to manage Dynamic Data Stores
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 * @todo this factory should go into core once we use datastores in more broad ways.
 * @todo the classnames could use a bit of a clean up (shorter, lowercasing)
 */
class Dynamic_DataStore_Master
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