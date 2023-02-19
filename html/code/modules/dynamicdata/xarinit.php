<?php
/**
 * Dynamic data initialization
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */
/**
 * Initialise the dynamicdata module
 *
 * This function is only ever called once during the lifetime of a particular
 * module instance
 * @author mikespub <mikespub@xaraya.com>
 */
function dynamicdata_init()
{
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        sys::import('xaraya.tableddl');
        xarXMLInstaller::createTable('table_schema-def', 'dynamicdata');
        // We're done, commit
        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }
    
    $xartable =& xarDB::getTables();
    $prefix = xarDB::getPrefix();

    $dynamic_objects = $xartable['dynamic_objects'];
    $dynamic_properties = $xartable['dynamic_properties'];
    $dynamic_data = $xartable['dynamic_data'];
    
    // Create tables inside a transaction
    try {
        $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
        $dbconn->begin();
# --------------------------------------------------------
#
# Create the object and property dataobjects
#
        $module_id = xarMod::getRegID('dynamicdata');

        // create default objects for dynamic data
        $sql = "INSERT INTO $dynamic_objects (
                name, label,
                module_id, itemtype, class, filepath, urlparam,
                maxid, datastore, access, config, sources, relations, objects,isalias)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $dbconn->prepareStatement($sql);

        $objects = array(
            array(
                'objects',
                'Dynamic Objects',
                $module_id,
                0,
                'DataObject',
                'auto', 
                'itemid',
                0,
                'relational',
                'a:4:{s:14:"display_access";a:3:{s:5:"group";s:1:"0";s:5:"level";s:3:"200";s:7:"failure";s:1:"0";}s:13:"modify_access";a:3:{s:5:"group";s:1:"0";s:5:"level";s:3:"800";s:7:"failure";s:1:"0";}s:13:"delete_access";a:3:{s:5:"group";s:1:"0";s:5:"level";s:3:"800";s:7:"failure";s:1:"0";}s:6:"access";s:174:"a:5:{s:7:"display";a:5:{i:0;i:5;i:1;i:2;i:2;i:1;i:3;i:3;i:4;i:4;}s:6:"update";a:1:{i:0;i:2;}s:6:"create";a:1:{i:0;i:2;}s:6:"delete";a:1:{i:0;i:2;}s:6:"config";a:1:{i:0;i:2;}}";}',
                serialize(array()),
                serialize(array(
                    'dynamic_objects' => array($prefix . '_dynamic_objects', 'internal'),
//                    'linkages' => array($prefix . '_categories_linkage', 'foreign'),
//                    'categories' => array($prefix . '_categories', 'foreign'),
                )),
                serialize(array()),
                serialize(array()),
                false
                ),
            array(
                'properties',
                'Dynamic Properties',
                $module_id,
                1,
                'DataObject',
                'auto',
                'itemid',
                0,
                'relational',
                'a:4:{s:14:"display_access";a:3:{s:5:"group";s:1:"0";s:5:"level";s:3:"200";s:7:"failure";s:1:"0";}s:13:"modify_access";a:3:{s:5:"group";s:1:"0";s:5:"level";s:3:"800";s:7:"failure";s:1:"0";}s:13:"delete_access";a:3:{s:5:"group";s:1:"0";s:5:"level";s:3:"800";s:7:"failure";s:1:"0";}s:6:"access";s:174:"a:5:{s:7:"display";a:5:{i:0;i:5;i:1;i:2;i:2;i:1;i:3;i:3;i:4;i:4;}s:6:"update";a:1:{i:0;i:2;}s:6:"create";a:1:{i:0;i:2;}s:6:"delete";a:1:{i:0;i:2;}s:6:"config";a:1:{i:0;i:2;}}";}',
                'a:0:{}',
                serialize(array(
                    'dynamic_properties' => array($prefix . '_dynamic_properties', 'internal')
                )),
                'a:0:{}',
                'a:0:{}',
                false
                ),
        );

        $objectid = array();
        $idx = 0;
        foreach ($objects as &$object) {
            $stmt->executeUpdate($object);
            $idx++;
            $objectid[$idx] = $dbconn->getLastId($dynamic_objects);
        }
        /**
         * Note : same remark as above - we can't use createproperty() here
         *        because dynamicdata doesn't know anything about properties yet :-)
         */
        // create default properties for dynamic data objects
        $sql = "INSERT INTO $dynamic_properties (
                name, label, object_id,
                type, defaultvalue, source,
                status, seq, configuration)
            VALUES (?,?,?,?,?,?,?,?,?)";
        $stmt = $dbconn->prepareStatement($sql);
        // TEMP FIX for the constants, rewrite this
        sys::import('modules.dynamicdata.class.properties');
        $properties = array(
            // Properties for the Objects DD object
            array('objectid'  ,'Id'                 ,$objectid[1],21,''            ,'dynamic_objects.id'         ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_NOINPUT,1 ,''),
            array('name'      ,'Name'               ,$objectid[1],2 ,''            ,'dynamic_objects.name'       ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,2 ,''),
            array('label'     ,'Label'              ,$objectid[1],2 ,''            ,'dynamic_objects.label'      ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,3 ,''),
            array('module_id' ,'Module'             ,$objectid[1],19,'182'         ,'dynamic_objects.module_id'  ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,5 ,'a:4:{s:14:"display_layout";s:7:"default";s:24:"initialization_refobject";s:7:"modules";s:25:"initialization_store_prop";s:5:"regid";s:27:"initialization_display_prop";s:4:"name";}'), // FIXME: change this validation when we move from regid to systemid
            array('itemtype'  ,'Item Type'          ,$objectid[1],20,"xarMod::apiFunc('dynamicdata','admin','getnextitemtype')"           ,'dynamic_objects.itemtype'   ,DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN | DataPropertyMaster::DD_INPUTSTATE_NOINPUT,6 ,'a:10:{s:18:"display_combo_mode";s:1:"2";s:14:"display_layout";s:7:"default";s:19:"validation_override";s:1:"1";s:21:"initialization_module";s:1:"3";s:23:"initialization_itemtype";s:1:"0";s:23:"initialization_function";s:0:"";s:19:"initialization_file";s:0:"";s:25:"initialization_collection";s:0:"";s:22:"initialization_options";s:0:"";s:25:"initialization_other_rule";s:0:"";}'),
            array('class'     ,'Class'              ,$objectid[1],2 ,'DataObject'  ,'dynamic_objects.class'      ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,7 ,''),
            array('filepath'  ,'Location'           ,$objectid[1],2 ,'auto'        ,'dynamic_objects.filepath'   ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,8 ,''),
            array('urlparam'  ,'URL Param'          ,$objectid[1],2 ,'itemid'      ,'dynamic_objects.urlparam'   ,DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,9 ,''),
            array('maxid'     ,'Max Id'             ,$objectid[1],15,'0'           ,'dynamic_objects.maxid'      ,DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,10 ,''),
            array('isalias'   ,'Alias in short URLs',$objectid[1],14,'1'           ,'dynamic_objects.isalias'    ,DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,11 ,''),
            array('datastore' ,'Datastore'          ,$objectid[1],6,'dynamicdata'  ,'dynamic_objects.datastore'  ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,12 ,'a:2:{s:14:"display_layout";s:7:"default";s:22:"initialization_options";s:80:"relational,relational;module_variables,module_variables;dynamicdata,dynamicdata;";}'),
            array('access'    ,'Access'             ,$objectid[1],2,'a:0:{}'       ,'dynamic_objects.access'     ,DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN | DataPropertyMaster::DD_INPUTSTATE_NOINPUT,10 ,      'a:6:{s:20:"display_minimum_rows";s:1:"1";s:20:"display_maximum_rows";s:2:"10";s:25:"display_column_definition";a:1:{s:5:"value";a:4:{i:0;a:2:{i:0;s:3:"Key";i:1;s:5:"Value";}i:1;a:2:{i:0;s:1:"2";i:1;s:1:"2";}i:2;a:2:{i:0;s:0:"";i:1;s:0:"";}i:3;a:2:{i:0;s:0:"";i:1;s:0:"";}}}s:14:"display_layout";s:7:"default";s:28:"validation_associative_array";s:1:"1";s:24:"initialization_addremove";s:1:"2";}'),
            array('config'    ,'Configuration'      ,$objectid[1],999 ,''          ,'dynamic_objects.config'     ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,12 ,'a:6:{s:20:"display_minimum_rows";s:1:"1";s:20:"display_maximum_rows";s:2:"10";s:25:"display_column_definition";a:2:{i:0;a:4:{i:0;s:3:"Key";i:1;s:1:"2";i:2;s:0:"";i:3;s:0:"";}i:1;a:4:{i:0;s:5:"Value";i:1;s:1:"2";i:2;s:0:"";i:3;s:0:"";}}s:14:"display_layout";s:7:"default";s:28:"validation_associative_array";s:1:"1";s:24:"initialization_addremove";s:1:"2";}'),
            array('sources'   ,'Sources'            ,$objectid[1],999 ,''          ,'dynamic_objects.sources'    ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,13 ,'a:6:{s:20:"display_minimum_rows";s:1:"1";s:20:"display_maximum_rows";s:2:"10";s:25:"display_column_definition";a:3:{i:0;a:4:{i:0;s:5:"Alias";i:1;s:1:"2";i:2;s:0:"";i:3;s:0:"";}i:1;a:4:{i:0;s:5:"Table";i:1;s:1:"2";i:2;s:0:"";i:3;s:0:"";}i:2;a:4:{i:0;s:4:"Type";i:1;s:1:"6";i:2;s:8:"internal";i:3;s:144:"a:3:{s:12:"display_rows";s:1:"0";s:14:"display_layout";s:7:"default";s:22:"initialization_options";s:34:"internal,internal;foreign,foreign;";}"}";}}s:14:"display_layout";s:7:"default";s:28:"validation_associative_array";s:1:"1";s:24:"initialization_addremove";s:1:"2";}'),
            array('relations' ,'Relations'          ,$objectid[1],999 ,''          ,'dynamic_objects.relations'  ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,14 ,'a:5:{s:20:"display_minimum_rows";s:1:"1";s:20:"display_maximum_rows";s:2:"10";s:25:"display_column_definition";a:2:{i:0;a:4:{i:0;s:9:"Link From";i:1;s:1:"2";i:2;s:0:"";i:3;s:0:"";}i:1;a:4:{i:0;s:7:"Link To";i:1;s:1:"2";i:2;s:0:"";i:3;s:0:"";}}s:14:"display_layout";s:7:"default";s:24:"initialization_addremove";s:1:"2";}'),
            array('objects'   ,'Objects'            ,$objectid[1],999 ,''          ,'dynamic_objects.objects'    ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,15 ,'a:5:{s:20:"display_minimum_rows";s:1:"1";s:20:"display_maximum_rows";s:2:"10";s:25:"display_column_definition";a:2:{i:0;a:4:{i:0;s:11:"Parent Link";i:1;s:1:"2";i:2;s:0:"";i:3;s:0:"";}i:1;a:4:{i:0;s:10:"Child Link";i:1;s:1:"2";i:2;s:0:"";i:3;s:0:"";}}s:14:"display_layout";s:7:"default";s:24:"initialization_addremove";s:1:"2";}'),
            array('category'  ,'Category'           ,$objectid[1],100,6            ,''                           ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,16 ,'a:3:{s:14:"display_layout";s:7:"default";s:29:"initialization_include_no_cat";s:1:"1";s:29:"initialization_basecategories";a:1:{i:0;a:4:{i:0;s:15:"Object Category";i:1;a:1:{i:0;a:1:{i:0;s:1:"5";}}i:2;b:0;i:3;s:1:"1";}}}'),

            // Properties for the Properties DD object
            array('id'           ,'Id'                 ,$objectid[2],21,''            ,'dynamic_properties.id'        ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,1 ,''),
            array('name'         ,'Name'               ,$objectid[2],2 ,''            ,'dynamic_properties.name'      ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,2 ,''),
            array('label'        ,'Label'              ,$objectid[2],2 ,''            ,'dynamic_properties.label'     ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,3 ,''),
            array('objectid'     ,'Object'             ,$objectid[2],24,''            ,'dynamic_properties.object_id' ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,4 ,''),
            array('type'         ,'Property Type'      ,$objectid[2],22,''            ,'dynamic_properties.type'      ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,7 ,''),
            array('defaultvalue' ,'Default Value'   ,$objectid[2],3 ,''            ,'dynamic_properties.defaultvalue'   ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,8 ,'varchar (254)'),
            array('source'       ,'Source'             ,$objectid[2],23,'dynamic_data','dynamic_properties.source'    ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE,9 ,''),
            array('status'       ,'Status'             ,$objectid[2],25,'33'          ,'dynamic_properties.status'    ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,10,''),
            array('translatable' ,'Translatable'       ,$objectid[2],25,'0'           ,'dynamic_properties.translatable',DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,10,''),
            array('seq'          ,'Order'              ,$objectid[2],15,'0'           ,'dynamic_properties.seq'       ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,11,''),
            array('configuration','Configuration'   ,$objectid[2],998,'a:0:{}'        ,'dynamic_properties.configuration',DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,12,''),
        );
        $propid = array();
        $idx = 0;
        foreach ($properties as &$property) {
            $stmt->executeUpdate($property);
            $idx++;
            $propid[$idx] = $dbconn->getLastId($dynamic_properties);
        }
        $dbconn->commit();
    } catch (Exception $e) {
        // nice try
        $dbconn->rollback();
        throw $e;
    }
# --------------------------------------------------------
#
# Set up modvars
#
    xarModVars::set('dynamicdata', 'items_per_page', 20);
    xarModVars::set('dynamicdata', 'use_module_alias',0);
    xarModVars::set('dynamicdata', 'module_alias_name','Query');
    xarModVars::set('dynamicdata', 'debugmode', 0);
    xarModVars::set('dynamicdata', 'getlinkedobjects', 0);
    xarModVars::set('dynamicdata', 'caching', 0);
    xarModVars::set('dynamicdata', 'suppress_updates', 0);
    /**
     * Register hooks
     */
    xarModHooks::register('item', 'search', 'GUI', 'dynamicdata', 'user', 'search');
    /*********************************************************************
     * Register the module components that are privileges objects
     * Format is
     * register(Name,Realm,Module,Component,Instance,Level,Description)
     *********************************************************************/
    xarMasks::register('ViewDynamicData','All','dynamicdata','All','All','ACCESS_OVERVIEW');
    xarMasks::register('EditDynamicData','All','dynamicdata','All','All','ACCESS_EDIT');
    xarMasks::register('AddDynamicData','All','dynamicdata','All','All','ACCESS_ADD');
    xarMasks::register('ManageDynamicData','All','dynamicdata','All','All','ACCESS_DELETE');
    xarMasks::register('AdminDynamicData','All','dynamicdata','All','All','ACCESS_ADMIN');
    xarMasks::register('ViewDynamicDataItems','All','dynamicdata','Item','All:All:All','ACCESS_OVERVIEW');
    xarMasks::register('ReadDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_READ');
    xarMasks::register('EditDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_EDIT');
    xarMasks::register('AddDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_ADD');
    xarMasks::register('DeleteDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_DELETE');
    xarMasks::register('AdminDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_ADMIN');
    /*********************************************************************
     * Define instances for this module
     * Format is
     * setInstance(Module,Component,Query,ApplicationVar,LevelTable,ChildIDField,ParentIDField)
     *********************************************************************/
    $instances = array(
        array(
            'header' => 'external', // this keyword indicates an external "wizard"
            'query'  => xarController::URL('dynamicdata', 'admin', 'privileges'),
            'limit'  => 0
        )
    );
    xarPrivileges::defineInstance('dynamicdata','Item',$instances);
    // Installation complete; check for upgrades
    return dynamicdata_upgrade('2.0.0');
}
/**
 * upgrade the dynamicdata module from an old version
 * This function can be called multiple times
 *
 * @param string oldversion
 * @return boolean true on success, false on failure
 */
function dynamicdata_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        case '2.0.0':
            // when a new module item is being specified
            xarModHooks::register('item', 'new', 'GUI', 'dynamicdata', 'admin', 'newhook');
            // when a module item is created (uses 'dd_*')
            xarModHooks::register('item', 'create', 'API', 'dynamicdata', 'admin', 'createhook');
            // when a module item is being modified (uses 'dd_*')
            xarModHooks::register('item', 'modify', 'GUI', 'dynamicdata', 'admin', 'modifyhook');
            // when a module item is updated (uses 'dd_*')
            xarModHooks::register('item', 'update', 'API', 'dynamicdata', 'admin', 'updatehook');
            // when a module item is deleted
            xarModHooks::register('item', 'delete', 'API', 'dynamicdata', 'admin', 'deletehook');
            // when a module configuration is being modified (uses 'dd_*')
            xarModHooks::register('module', 'modifyconfig', 'GUI', 'dynamicdata', 'admin', 'modifyconfighook');
            // when a module configuration is updated (uses 'dd_*')
            xarModHooks::register('module', 'updateconfig', 'API', 'dynamicdata', 'admin', 'updateconfighook');
            // when a whole module is removed, e.g. via the modules admin screen
            // (set object ID to the module name !)
            xarModHooks::register('module', 'remove', 'API', 'dynamicdata', 'admin', 'removehook');
        //  Ideally, people should be able to use the dynamic fields in their
        //  module templates as if they were 'normal' fields -> this means
        //  adapting the get() function in the user API of the module, and/or
        //  using some common data retrieval function (DD) in the future...
        /*  display hook is now disabled by default - use the BL tags or APIs instead
            xarModHooks::register('item', 'display', 'GUI', 'dynamicdata', 'user', 'displayhook');
        */
            // fall through to next upgrade
        default:
            break;
    }
    return true;
}

/**
 * Remove this module
 */
function dynamicdata_delete()
{
  //this module cannot be removed
  return false;
}
