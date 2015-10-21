<?php
/**
 * Dynamic data initialization
 *
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/182.html
 */
sys::import('xaraya.tableddl');
/**
 * Initialise the dynamicdata module
 *
 * This function is only ever called once during the lifetime of a particular
 * module instance
 * @author mikespub <mikespub@xaraya.com>
 */
function dynamicdata_init()
{
    /**
     * Create tables
     */
    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();
    $prefix = xarDB::getPrefix();

    $dynamic_objects = $xartable['dynamic_objects'];
    $dynamic_properties = $xartable['dynamic_properties'];
    $dynamic_data = $xartable['dynamic_data'];
    $dynamic_configurations = $xartable['dynamic_configurations'];
    $dynamic_properties_def = $xartable['dynamic_properties_def'];
    $modulestable = $xartable['modules'];

    // Create tables inside a transaction
    try {
        $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
        $dbconn->begin();
        /**
         * DataObjects table
         */
        $objectfields = array(
            'id' => array(
                'type'        => 'integer',
                'unsigned'     => true,
                'null'        => false,
                'increment'   => true,
                'primary_key' => true
            ),
            /* the name used to reference an object */
            'name'     => array(
                'type'        => 'varchar',
                'size'        => 64,
                'null'        => false,
                'charset'     => $charset,
            ),
            /* the label used for display */
            'label'    => array(
                'type'        => 'varchar',
                'size'        => 254,
                'null'        => false,
                'charset'     => $charset,
            ),
            /* the module this object relates to */
            'module_id' => array(
                'type'        => 'integer',
                'unsigned'    => true,
                'null'        => false
            ),
            /* the optional item type within this module */
            'itemtype' => array(
                'type'        => 'integer',
                'unsigned'    => true,
                'null'        => false,
                'default'     => '0'
            ),
            /* the class this object belongs to */
            'class'     => array(
                'type'        => 'varchar',
                'size'        => 254,
                'null'        => false,
                'default'     => 'DataObject',
                'charset'     => $charset,
            ),
            /* the location where the class file lives */
            'filepath'     => array(
                'type'        => 'varchar',
                'size'        => 254,
                'null'        => false,
                'default'     => 'modules/dynamicdata/class/objects/base.php',
                'charset'     => $charset,
            ),
            /* the URL parameter used to pass on the item id to the original module */
            'urlparam' => array(
                'type'        => 'varchar',
                'size'        => 30,
                'null'        => false,
                'default'     => 'itemid',
                'charset'     => $charset,
            ),
            /* the highest item id for this object (used if the object has a dynamic item id field) */
            'maxid'    => array(
                'unsigned'    => true,
                'type'        => 'integer',
                'null'        => false,
                'default'     => '0'
            ),
            /* the data store this object uses */
            'datastore'   => array(
                'type'        => 'varchar',
                'size'        => 60,
                'null'        => false,
                'default'     => 'dynamicdata',
                'charset'     => $charset,
            ),
            /* access settings for this object */
            'access'   => array(
                'type'=>'text',
                'charset'     => $charset,
            ),
            /* any configuration settings for this object */
            'config'   => array(
                'type'        =>'text',
                'charset'     => $charset,
            ),
            /* the data sources this object uses */
            'sources'   => array(
                'type'        =>'text'
            ),
            /* the data source relations this object uses */
            'relations' => array(
                'type'        =>'text'
            ),
            /* the data source relations this object uses */
            'objects'   => array(
                'type'        =>'text'
            ),
            /* use the name of this object as alias for short URLs */
            'isalias'  => array(
                'type'        => 'boolean',
                'default'     => true
            ),
        );

        $query = xarDBCreateTable($dynamic_objects,$objectfields);
        $dbconn->Execute($query);

        // TODO: evaluate efficiency of combined index vs. individual ones
        // the combination of module id + item type *must* be unique
        $query = xarDBCreateIndex(
            $dynamic_objects,
            array(
                'name'   => $prefix . '_dynobjects_combo',
                'fields' => array('module_id','itemtype'),
                'unique' => 'true'
            )
        );
        $dbconn->Execute($query);

        // the object name *must* be unique
        $query = xarDBCreateIndex(
            $dynamic_objects,
            array(
                'name'   => $prefix . '_dynobjects_name',
                'fields' => array('name'),
                'unique' => 'true'
            )
        );
        $dbconn->Execute($query);

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


# --------------------------------------------------------
#
# Create the Dynamic Properties table
#
        $propfields = array(
            'id'     => array(
                'type'        => 'integer',
                'unsigned'     => true,
                'null'        => false,
                'increment'   => true,
                'primary_key' => true
            ),
            /* the name used to reference a particular property, e.g. in function calls and templates */
            'name'       => array(
                'type'        => 'varchar',
                'size'        => 64,
                'null'        => false,
                'charset'     => $charset,
            ),
            /* the label used for display */
            'label'      => array(
                'type'        => 'varchar',
                'size'        => 254,
                'null'        => false,
                'charset'     => $charset,
            ),
            /* the object this property belong to */
            'object_id'   => array(
                'type'        => 'integer',
                'unsigned'    => true,
                'null'        => false
            ),
            /* the property type of this property */
            'type'       => array(
                'type'        => 'integer',
                'unsigned'    => true,
                'null'        => false,
                'default'     => null
            ),
            /* the default value for this property */
            'defaultvalue'    => array(
                'type'        => 'varchar',
                'size'        => 254,
                'default'     => null,
                'charset'     => $charset,
            ),
            /* the data source for this property (dynamic data, static table, hook, user function, LDAP (?), file, ... */
            'source'     => array(
                'type'        => 'varchar',
                'size'        => 254,
                'null'        => false,
                'default'     => 'dynamic_data',
                'charset'     => $charset,
            ),
            /* is this property active ? (unused at the moment) */
            'status'     => array(
                'type'        => 'integer',
                'size'        => 'tiny',
                'unsigned'     => true,
                'null'        => false,
                'default'     => '33'
            ),
            /* the order of this property */
            'seq'      => array(
                'type'        => 'integer',
                'size'        => 'tiny',
                'unsigned'     => true,
                'null'        => false
            ),
            /* specific configuration rules for this property (e.g. basedir, size, ...) */
            'configuration' => array(
                'type'        => 'text',
                'charset'     => $charset,
            )
        );

        $query = xarDBCreateTable($dynamic_properties,$propfields);
        $dbconn->Execute($query);

        $query = xarDBCreateIndex(
            $dynamic_properties,
            array(
                'name'   => $prefix . '_dynprops_combo',
                'fields' => array('object_id', 'name'),
                'unique' => 'true'
            )
        );
        $dbconn->Execute($query);

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
            array('module_id' ,'Module'             ,$objectid[1],19,'182'         ,'dynamic_objects.module_id'  ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,5 ,'a:4:{s:14:"display_layout";s:7:"default";s:24:"initialization_refobject";s:7:"modules";s:25:"initialization_store_prop";s:5:"regid";s:27:"initialization_display_prop";s:4:"name";}'), // FIXME: change this validation when we move from regid to systemid
            array('itemtype'  ,'Item Type'          ,$objectid[1],20,"xarMod::apiFunc('dynamicdata','admin','getnextitemtype')"           ,'dynamic_objects.itemtype'   ,DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN | DataPropertyMaster::DD_INPUTSTATE_NOINPUT,6 ,'a:10:{s:18:"display_combo_mode";s:1:"2";s:14:"display_layout";s:7:"default";s:19:"validation_override";s:1:"1";s:21:"initialization_module";s:1:"3";s:23:"initialization_itemtype";s:1:"0";s:23:"initialization_function";s:0:"";s:19:"initialization_file";s:0:"";s:25:"initialization_collection";s:0:"";s:22:"initialization_options";s:0:"";s:25:"initialization_other_rule";s:0:"";}'),
            array('class'     ,'Class'              ,$objectid[1],2 ,'DataObject'  ,'dynamic_objects.class'      ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,7 ,''),
            array('filepath'  ,'Location'           ,$objectid[1],2 ,'auto'        ,'dynamic_objects.filepath'   ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,8 ,''),
            array('urlparam'  ,'URL Param'          ,$objectid[1],2 ,'itemid'      ,'dynamic_objects.urlparam'   ,DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,9 ,''),
            array('maxid'     ,'Max Id'             ,$objectid[1],15,'0'           ,'dynamic_objects.maxid'      ,DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,10 ,''),
            array('isalias'   ,'Alias in short URLs',$objectid[1],14,'1'           ,'dynamic_objects.isalias'    ,DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,11 ,''),
            array('datastore' ,'Datastore'          ,$objectid[1],6,'dynamicdata'  ,'dynamic_objects.datastore'  ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,12 ,'a:2:{s:14:"display_layout";s:7:"default";s:22:"initialization_options";s:80:"relational,relational;module_variables,module_variables;dynamicdata,dynamicdata;";}'),
            array('access'    ,'Access'             ,$objectid[1],2,'a:0:{}'       ,'dynamic_objects.access'     ,DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN | DataPropertyMaster::DD_INPUTSTATE_NOINPUT,10 ,      'a:6:{s:20:"display_minimum_rows";s:1:"1";s:20:"display_maximum_rows";s:2:"10";s:25:"display_column_definition";a:1:{s:5:"value";a:4:{i:0;a:2:{i:0;s:3:"Key";i:1;s:5:"Value";}i:1;a:2:{i:0;s:1:"2";i:1;s:1:"2";}i:2;a:2:{i:0;s:0:"";i:1;s:0:"";}i:3;a:2:{i:0;s:0:"";i:1;s:0:"";}}}s:14:"display_layout";s:7:"default";s:28:"validation_associative_array";s:1:"1";s:24:"initialization_addremove";s:1:"2";}'),
            array('config'    ,'Configuration'      ,$objectid[1],999 ,''          ,'dynamic_objects.config'     ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,12 ,'a:5:{s:20:"display_minimum_rows";s:1:"1";s:20:"display_maximum_rows";s:2:"10";s:25:"display_column_definition";a:2:{i:0;a:4:{i:0;s:3:"Key";i:1;s:1:"2";i:2;s:0:"";i:3;s:0:"";}i:1;a:4:{i:0;s:5:"Value";i:1;s:1:"2";i:2;s:0:"";i:3;s:0:"";}}s:14:"display_layout";s:7:"default";s:24:"initialization_addremove";s:1:"2";}'),
            array('sources'   ,'Sources'            ,$objectid[1],999 ,''          ,'dynamic_objects.sources'    ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,13 ,'a:6:{s:20:"display_minimum_rows";s:1:"1";s:20:"display_maximum_rows";s:2:"10";s:25:"display_column_definition";a:3:{i:0;a:4:{i:0;s:5:"Alias";i:1;s:1:"2";i:2;s:0:"";i:3;s:0:"";}i:1;a:4:{i:0;s:5:"Table";i:1;s:1:"2";i:2;s:0:"";i:3;s:0:"";}i:2;a:4:{i:0;s:4:"Type";i:1;s:1:"6";i:2;s:8:"internal";i:3;s:144:"a:3:{s:12:"display_rows";s:1:"0";s:14:"display_layout";s:7:"default";s:22:"initialization_options";s:34:"internal,internal;foreign,foreign;";}"}";}}s:14:"display_layout";s:7:"default";s:28:"validation_associative_array";s:1:"1";s:24:"initialization_addremove";s:1:"2";}'),
            array('relations' ,'Relations'          ,$objectid[1],999 ,''          ,'dynamic_objects.relations'  ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,14 ,'a:5:{s:20:"display_minimum_rows";s:1:"1";s:20:"display_maximum_rows";s:2:"10";s:25:"display_column_definition";a:2:{i:0;a:4:{i:0;s:9:"Link From";i:1;s:1:"2";i:2;s:0:"";i:3;s:0:"";}i:1;a:4:{i:0;s:7:"Link To";i:1;s:1:"2";i:2;s:0:"";i:3;s:0:"";}}s:14:"display_layout";s:7:"default";s:24:"initialization_addremove";s:1:"2";}'),
            array('objects'   ,'Objects'            ,$objectid[1],999 ,''          ,'dynamic_objects.objects'    ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,15 ,'a:5:{s:20:"display_minimum_rows";s:1:"1";s:20:"display_maximum_rows";s:2:"10";s:25:"display_column_definition";a:2:{i:0;a:4:{i:0;s:11:"Parent Link";i:1;s:1:"2";i:2;s:0:"";i:3;s:0:"";}i:1;a:4:{i:0;s:10:"Child Link";i:1;s:1:"2";i:2;s:0:"";i:3;s:0:"";}}s:14:"display_layout";s:7:"default";s:24:"initialization_addremove";s:1:"2";}'),
            array('category'  ,'Category'           ,$objectid[1],100,8            ,''                           ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,16 ,'a:3:{s:14:"display_layout";s:7:"default";s:29:"initialization_include_no_cat";s:1:"1";s:29:"initialization_basecategories";a:1:{i:0;a:4:{i:0;s:15:"Object Category";i:1;a:1:{i:0;a:1:{i:0;s:2:"1";}}i:2;b:1;i:3;s:1:"1";}}}'),

            // Properties for the Properties DD object
            array('id'        ,'Id'                 ,$objectid[2],21,''            ,'dynamic_properties.id'        ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,1 ,''),
            array('name'      ,'Name'               ,$objectid[2],2 ,''            ,'dynamic_properties.name'      ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,2 ,''),
            array('label'     ,'Label'              ,$objectid[2],2 ,''            ,'dynamic_properties.label'     ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,3 ,''),
            array('objectid'  ,'Object'             ,$objectid[2],24,''            ,'dynamic_properties.object_id' ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,4 ,''),
            array('type'      ,'Property Type'      ,$objectid[2],22,''            ,'dynamic_properties.type'      ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,7 ,''),
            array('defaultvalue' ,'Default Value'   ,$objectid[2],3 ,''            ,'dynamic_properties.defaultvalue'   ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,8 ,'varchar (254)'),
            array('source'    ,'Source'             ,$objectid[2],23,'dynamic_data','dynamic_properties.source'    ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE,9 ,''),
            array('status'    ,'Status'             ,$objectid[2],25,'1'           ,'dynamic_properties.status'    ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,10,''),
            array('seq'       ,'Order'              ,$objectid[2],15,'0'           ,'dynamic_properties.seq'       ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,11,''),
            array('configuration','Configuration'   ,$objectid[2],998,'a:0:{}'     ,'dynamic_properties.configuration',DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,12,''),
        );

        $propid = array();
        $idx = 0;
        foreach ($properties as &$property) {
            $stmt->executeUpdate($property);
            $idx++;
            $propid[$idx] = $dbconn->getLastId($dynamic_properties);
        }

        /**
         * Dynamic Data table (= one of the possible data sources for properties)
         */
        $datafields = array(
            'id'   => array(
                'type'        => 'integer',
                'unsigned'     => true,
                'null'        => false,
                'increment'   => true,
                'primary_key' => true
            ),
            /* the property this dynamic data belongs to */
            'property_id'   => array(
                'type'        => 'integer',
                'unsigned'    => true,
                'null'        => false
            ),
            /* the item id this dynamic data belongs to */
            'item_id'   => array(
                'type'        => 'integer',
                'unsigned'    => true,
                'null'        => false
            ),
            /* the value of this dynamic data */
            'value'    => array(
                'type'        => 'text', // or blob when storing binary data (but not for PostgreSQL - see bug 1324)
                'size'        => 'medium',
                'null'        => 'false',
                'charset'     => $charset,
            )
        );

        // Create the Table - the function will return the SQL is successful or
        // raise an exception if it fails, in this case $query is empty
        $query = xarDBCreateTable($dynamic_data,$datafields);
        $dbconn->Execute($query);

        $query = xarDBCreateIndex(
            $dynamic_data,
            array(
                'name'   => $prefix . '_dyndata_property_id',
                'fields' => array('property_id')
            )
        );
        $dbconn->Execute($query);

        $query = xarDBCreateIndex(
            $dynamic_data,
            array(
                'name'   => $prefix . '_dyndata_item_id',
                'fields' => array('item_id')
            )
        );
        $dbconn->Execute($query);

        /**
         * Configurations table
         */

        $configfields = array(
            'id'   => array(
                'type'        => 'integer',
                'unsigned'     => true,
                'null'        => false,
                'default'     => '0',
                'increment'   => true,
                'primary_key' => true
            ),
            'name'      => array(
                'type'        => 'varchar',
                'size'        => 254,
                'null'        => false,
                'default'     => '',
                'charset'     => $charset,
            ),
            'description'     => array(
                'type'        => 'varchar',
                'size'        => 254,
                'null'        => false,
                'default'     => '',
                'charset'     => $charset,
            ),
            'property_id'     => array(
                'type'        => 'integer',
                'unsigned'     => true,
                'null'        => false,
                'default'     => '0'
            ),
            'label'     => array(
                'type'        => 'varchar',
                'size'        => 254,
                'null'        => false,
                'default'     => '',
                'charset'     => $charset,
            ),
            'ignore_empty'     => array(
                'type'        => 'boolean',
                'default'     => false
            ),
            'configuration'   => array(
                'type'        => 'text',
                'size'        => 'medium',
                'null'        => 'false',
                'charset'     => $charset,
            )
        );
        $query = xarDBCreateTable($dynamic_configurations,$configfields);
        $dbconn->Execute($query);

        // Add Dynamic Data Properties Definition Table
        dynamicdata_createPropDefTable();

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
    xarModVars::set('dynamicdata', 'administrators', serialize(array()));
    xarModVars::set('dynamicdata', 'getlinkedobjects', 0);


    /**
     * Register hooks
     */

    xarModRegisterHook('item', 'search', 'GUI', 'dynamicdata', 'user', 'search');

    /*********************************************************************
     * Register the module components that are privileges objects
     * Format is
     * register(Name,Realm,Module,Component,Instance,Level,Description)
     *********************************************************************/

    xarRegisterMask('ViewDynamicData','All','dynamicdata','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('EditDynamicData','All','dynamicdata','All','All','ACCESS_EDIT');
    xarRegisterMask('AddDynamicData','All','dynamicdata','All','All','ACCESS_ADD');
    xarRegisterMask('ManageDynamicData','All','dynamicdata','All','All','ACCESS_DELETE');
    xarRegisterMask('AdminDynamicData','All','dynamicdata','All','All','ACCESS_ADMIN');

    xarRegisterMask('ViewDynamicDataItems','All','dynamicdata','Item','All:All:All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_READ');
    xarRegisterMask('EditDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_EDIT');
    xarRegisterMask('AddDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_ADD');
    xarRegisterMask('DeleteDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_DELETE');
    xarRegisterMask('AdminDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_ADMIN');

    /*********************************************************************
     * Define instances for this module
     * Format is
     * setInstance(Module,Component,Query,ApplicationVar,LevelTable,ChildIDField,ParentIDField)
     *********************************************************************/

    $instances = array(
        array(
            'header' => 'external', // this keyword indicates an external "wizard"
            'query'  => xarModURL('dynamicdata', 'admin', 'privileges'),
            'limit'  => 0
        )
    );
    xarDefineInstance('dynamicdata','Item',$instances);

    // Installation complete; check for upgrades
    return dynamicdata_upgrade('2.0.0');
}

    /**
 * upgrade the dynamicdata module from an old version
 * This function can be called multiple times
 */
function dynamicdata_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        case '2.0.0':

            // when a new module item is being specified
            xarModRegisterHook('item', 'new', 'GUI', 'dynamicdata', 'admin', 'newhook');
            // when a module item is created (uses 'dd_*')
            xarModRegisterHook('item', 'create', 'API', 'dynamicdata', 'admin', 'createhook');
            // when a module item is being modified (uses 'dd_*')
            xarModRegisterHook('item', 'modify', 'GUI', 'dynamicdata', 'admin', 'modifyhook');
            // when a module item is updated (uses 'dd_*')
            xarModRegisterHook('item', 'update', 'API', 'dynamicdata', 'admin', 'updatehook');
            // when a module item is deleted
            xarModRegisterHook('item', 'delete', 'API', 'dynamicdata', 'admin', 'deletehook');
            // when a module configuration is being modified (uses 'dd_*')
            xarModRegisterHook('module', 'modifyconfig', 'GUI', 'dynamicdata', 'admin', 'modifyconfighook');
            // when a module configuration is updated (uses 'dd_*')
            xarModRegisterHook('module', 'updateconfig', 'API', 'dynamicdata', 'admin', 'updateconfighook');
            // when a whole module is removed, e.g. via the modules admin screen
            // (set object ID to the module name !)
            xarModRegisterHook('module', 'remove', 'API', 'dynamicdata', 'admin', 'removehook');

        //  Ideally, people should be able to use the dynamic fields in their
        //  module templates as if they were 'normal' fields -> this means
        //  adapting the get() function in the user API of the module, and/or
        //  using some common data retrieval function (DD) in the future...

        /*  display hook is now disabled by default - use the BL tags or APIs instead
            xarModRegisterHook('item', 'display', 'GUI', 'dynamicdata', 'user', 'displayhook');
        */

            // fall through to next upgrade

        default:
            break;
    }
    return true;
}

/**
 * Upgrade this module from an old version
 *
 * @param oldVersion
 * @return boolean true on success, false on failure
 */
function dynamicdata_delete()
{
  //this module cannot be removed
  return false;
}

function dynamicdata_createPropDefTable()
{
    /**
      * Dynamic Data Properties Definition Table
      */

    // Get existing DB info
    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();
    $prefix = xarDB::getPrefix();
    $dynamic_properties_def = $xartable['dynamic_properties_def'];
    $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');

    $propdefs = array(
        'id'     => array(
            'type'        => 'integer',
            'unsigned'     => true,
            'null'        => false,
            'increment'   => true,
            'primary_key' => true
        ),
        /* the name of this property */
        'name'   => array(
            'type'        => 'varchar',
            'size'        => 64,
            'default'     => null,
            'charset'     => $charset,
        ),
        /* the label of this property */
        'label'  => array(
            'type'        => 'varchar',
            'size'        => 254,
            'default'     => null,
            'charset'     => $charset,
        ),
        /* path to the file defining this property */
        'filepath' => array(
            'type'          => 'varchar',
            'size'          => 254,
            'default'       => null,
            'charset'     => $charset,
        ),
        /* name of the Class to be instantiated for this property */
        'class'  => array(
            'type'        => 'varchar',
            'size'        => 254,
            'default'     => null,
            'charset'     => $charset,
        ),
        /* the default configuration string for this property - no need to use text here... */
        'configuration'   => array(
            'type'              => 'varchar',
            'size'              => 254,
            'default'           => null,
            'charset'     => $charset,
        ),
        /* the source of this property */
        'source'   => array(
            'type'        => 'varchar',
            'size'        => 254,
            'default'     => null,
            'charset'     => $charset,
        ),
        /* the semi-colon seperated list of file required to be present before this property is active */
        'reqfiles'   => array(
            'type'        => 'varchar',
            'size'        => 254,
            'default'     => null,
            'charset'     => $charset,
        ),
        /* the ID of the module owning this property */
        'modid'  => array(
            'type'        => 'integer',
            'unsigned'    => true,
            'null'        => false,
            'default'     => '0'
        ),
        /* the default args for this property -- serialized array */
        'args'    => array(
            'type'        => 'text',
            'size'        => 'medium',
            'null'        => false,
            'charset'     => $charset,
        ),
        /* the aliases for this property -- serialized array */
        'aliases'   => array(
            'type'        => 'varchar',
            'size'        => 254,
            'default'     => null,
            'charset'     => $charset,
        ),
        /*  */
        'format'   => array(
            'type'        => 'integer',
            'default'     => '0'
        ),
    );

    $query = xarDBCreateTable($dynamic_properties_def,$propdefs);
    $dbconn->Execute($query);

    $query = xarDBCreateIndex(
        $dynamic_properties_def,
        array(
            'name'   => $prefix . '_dynpropdef_modid',
            'fields' => array('modid')
        )
    );
    $dbconn->Execute($query);
    return true;
}
?>
