<?php
/**
 * Dynamic data initilazation
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 */
/**
 * initialise the dynamicdata module
 * This function is only ever called once during the lifetime of a particular
 * module instance
 * @author mikespub <mikespub@xaraya.com>
 */
function dynamicdata_init()
{
    /**
     * Create tables
     */
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $dynamic_objects = $xartable['dynamic_objects'];
    $dynamic_properties = $xartable['dynamic_properties'];
    $dynamic_data = $xartable['dynamic_data'];
    $dynamic_relations = $xartable['dynamic_relations'];
    $dynamic_properties_def = $xartable['dynamic_properties_def'];
    $modulestable = $xartable['modules'];

    //Load Table Maintenance API
    xarDBLoadTableMaintenanceAPI();

    // Create all in one swoop (or not)
    try {
        $dbconn->begin();
        /**
         * Dynamic Objects table
         */
        $objectfields = array('xar_object_id' => array('type'        => 'integer',
                                                       'null'        => false,
                                                       'default'     => '0',
                                                       'increment'   => true,
                                                       'primary_key' => true),
                              /* the name used to reference an object and for short urls (eventually) */
                              'xar_object_name'     => array('type'        => 'varchar',
                                                             'size'        => 30,
                                                             'null'        => false,
                                                             'default'     => ''),
                              /* the label used for display */
                              'xar_object_label'    => array('type'        => 'varchar',
                                                             'size'        => 254,
                                                             'null'        => false,
                                                             'default'     => ''),
                              /* the module this object relates to */
                              'xar_object_moduleid' => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                              /* the optional item type within this module */
                              'xar_object_itemtype' => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                /* the item type of the parent of this object */
                    'xar_object_parent' => array('type'        => 'integer',
                                                  'null'        => false,
                                                  'default'     => '0'),
                              /* the URL parameter used to pass on the item id to the original module */
                              'xar_object_urlparam' => array('type'        => 'varchar',
                                                             'size'        => 30,
                                                             'null'        => false,
                                                             'default'     => 'itemid'),
                              /* the highest item id for this object (used if the object has a dynamic item id field) */
                              'xar_object_maxid'    => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                              /* any configuration settings for this object (future) */
                              'xar_object_config'   => array('type'=>'text'),
                              /* use the name of this object as alias for short URLs */
                              'xar_object_isalias'  => array('type'        => 'integer',
                                                             'size'        => 'tiny',
                                                             'null'        => false,
                                                             'default'     => '1'),
                              );

        // Create the Table - the function will return the SQL is successful or
        // raise an exception if it fails, in this case $query is empty
        $query = xarDBCreateTable($dynamic_objects,$objectfields);
        $dbconn->Execute($query);

        // TODO: evaluate efficiency of combined index vs. individual ones
        // the combination of module id + item type *must* be unique
        $query = xarDBCreateIndex($dynamic_objects,
                                  array('name'   => 'i_' . xarDBGetSiteTablePrefix() . '_dynobjects_combo',
                                        'fields' => array('xar_object_moduleid',
                                                          'xar_object_itemtype'),
                                        'unique' => 'true'));
        $dbconn->Execute($query);

        // the object name *must* be unique
        $query = xarDBCreateIndex($dynamic_objects,
                                  array('name'   => 'i_' . xarDBGetSiteTablePrefix() . '_dynobjects_name',
                                        'fields' => array('xar_object_name'),
                                        'unique' => 'true'));
        $dbconn->Execute($query);

        /**
         * Note : Classic chicken and egg problem - we can't use createobject() here
         *        because dynamicdata doesn't know anything about objects yet :-)
         */

        $modid = xarModGetIDFromName('dynamicdata');

        // create default objects for dynamic data
        $sql = "INSERT INTO $dynamic_objects (
                xar_object_id, xar_object_name, xar_object_label,
                xar_object_moduleid, xar_object_itemtype, xar_object_urlparam,
                xar_object_maxid, xar_object_config, xar_object_isalias)
                VALUES (?,?,?,?,?,?,?,?,?)";
        $stmt = $dbconn->prepareStatement($sql);

        $objects = array(
                         array('objects'   ,'Dynamic Objects'   ,$modid,0,'itemid',0,''               ,0),
                         array('properties','Dynamic Properties',$modid,1,'itemid',0,''               ,0),
                         array('sample'    ,'Sample Object'     ,$modid,2,'itemid',3,'nothing much...',0)
                         );

        $objectid = array();
        $idx = 0;
        foreach ($objects as &$object) {
            $nextId = $dbconn->GenId($dynamic_objects);
            array_unshift($object,$nextId);
            $stmt->executeUpdate($object);
            $idx++;
            $objectid[$idx] = $dbconn->PO_Insert_ID($dynamic_objects,'xar_object_id');
        }


        /**
         * Dynamic Properties table
         */
        $propfields = array('xar_prop_id'     => array('type'        => 'integer',
                                                       'null'        => false,
                                                       'default'     => '0',
                                                       'increment'   => true,
                                                       'primary_key' => true),
                            /* the name used to reference a particular property, e.g. in function calls and templates */
                            'xar_prop_name'       => array('type'        => 'varchar',
                                                           'size'        => 30,
                                                           'null'        => false,
                                                           'default'     => ''),
                            /* the label used for display */
                            'xar_prop_label'      => array('type'        => 'varchar',
                                                           'size'        => 254,
                                                           'null'        => false,
                                                           'default'     => ''),
                            /* the object this property belong to */
                            'xar_prop_objectid'   => array('type'        => 'integer',
                                                           'null'        => false,
                                                           'default'     => '0'),
                            /* we keep those 2 for efficiency, even though they're known via the object id as well */
                            /* the module this property relates to */
                            'xar_prop_moduleid'   => array('type'        => 'integer',
                                                           'null'        => false,
                                                           'default'     => '0'),
                            /* the optional item type within this module */
                            'xar_prop_itemtype'   => array('type'        => 'integer',
                                                           'null'        => false,
                                                           'default'     => '0'),
                            /* the property type of this property */
                            'xar_prop_type'       => array('type'        => 'integer',
                                                           'null'        => false,
                                                           'default'     => NULL),
                            /* the default value for this property */
                            'xar_prop_default'    => array('type'        => 'varchar',
                                                           'size'        => 254,
                                                           'default'     => NULL),
                            /* the data source for this property (dynamic data, static table, hook, user function, LDAP (?), file, ... */
                            'xar_prop_source'     => array('type'        => 'varchar',
                                                           'size'        => 254,
                                                           'null'        => false,
                                                           'default'     => 'dynamic_data'),
                            /* is this property active ? (unused at the moment) */
                            'xar_prop_status'     => array('type'        => 'integer',
                                                           'size'        => 'tiny',
                                                           'null'        => false,
                                                           'default'     => '1'),
                            /* the order of this property */
                            'xar_prop_order'      => array('type'        => 'integer',
                                                           'size'        => 'tiny',
                                                           'null'        => false,
                                                           'default'     => '0'),
                            /* specific validation rules for this property (e.g. basedir, size, ...) */
                            'xar_prop_validation' => array('type'        => 'text')
                            );

        // Create the Table - the function will return the SQL is successful or
        // raise an exception if it fails, in this case $query is empty
        $query = xarDBCreateTable($dynamic_properties,$propfields);
        $dbconn->Execute($query);

        // TODO: evaluate efficiency of combined index vs. individual ones
        // the combination of module id + item type + property name *must* be unique !
        $query = xarDBCreateIndex($dynamic_properties,
                                  array('name'   => 'i_' . xarDBGetSiteTablePrefix() . '_dynprops_combo',
                                        'fields' => array('xar_prop_moduleid',
                                                          'xar_prop_itemtype',
                                                          'xar_prop_name'),
                                        'unique' => 'true'));
        $dbconn->Execute($query);

        $query = xarDBCreateIndex($dynamic_properties,
                                  array('name'   => 'i_' . xarDBGetSiteTablePrefix() . '_dynprops_name',
                                        'fields' => array('xar_prop_name')));
        $dbconn->Execute($query);

        $query = xarDBCreateIndex($dynamic_properties,
                                  array('name'   => 'i_' . xarDBGetSiteTablePrefix() . '_dynprops_objectid',
                                        'fields' => array('xar_prop_objectid')));
        $dbconn->Execute($query);

        /**
         * Note : same remark as above - we can't use createproperty() here
         *        because dynamicdata doesn't know anything about properties yet :-)
         */

        // create default properties for dynamic data objects
        $sql = "INSERT INTO $dynamic_properties (
                xar_prop_id, xar_prop_name, xar_prop_label, xar_prop_objectid,
                xar_prop_moduleid, xar_prop_itemtype, xar_prop_type,
                xar_prop_default, xar_prop_source, xar_prop_status,
                xar_prop_order, xar_prop_validation)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $dbconn->prepareStatement($sql);

        $properties = array(
                            // 1 -> 9
                            array('objectid'  ,'Id'                 ,$objectid[1],182,0,21,''            ,$dynamic_objects.'.xar_object_id'         ,DD_PROPERTYSTATE_NOINPUT | DD_PROPERTYSTATE_ACTIVE,1 ,'integer'),
                            array('name'      ,'Name'               ,$objectid[1],182,0,2 ,''            ,$dynamic_objects.'.xar_object_name'       ,DD_PROPERTYSTATE_ACTIVE,2 ,'varchar (30)'),
                            array('label'     ,'Label'              ,$objectid[1],182,0,2 ,''            ,$dynamic_objects.'.xar_object_label'      ,DD_PROPERTYSTATE_ACTIVE,3 ,'varchar (254)'),
                            array('moduleid'  ,'Module'             ,$objectid[1],182,0,19,'182'         ,$dynamic_objects.'.xar_object_moduleid'   ,DD_PROPERTYSTATE_ACTIVE,4 ,'integer'),
                            array('itemtype'  ,'Item Type'          ,$objectid[1],182,0,20,'0'           ,$dynamic_objects.'.xar_object_itemtype'   ,DD_PROPERTYSTATE_ACTIVE,5 ,'integer'),
                            array('urlparam'  ,'URL Param'          ,$objectid[1],182,0,2 ,'itemid'      ,$dynamic_objects.'.xar_object_urlparam'   ,DD_PROPERTYSTATE_ACTIVE,6 ,'varchar (30)'),
                            array('maxid'     ,'Max Id'             ,$objectid[1],182,0,15,'0'           ,$dynamic_objects.'.xar_object_maxid'      ,DD_PROPERTYSTATE_DISPLAYONLY,7 ,'integer'),
                            array('config'    ,'Config'             ,$objectid[1],182,0,4 ,''            ,$dynamic_objects.'.xar_object_config'     ,DD_PROPERTYSTATE_DISPLAYONLY,8 ,'text'),
// TODO: (random) Review this. I don't really understand the status
//                            array('isalias'   ,'Alias in short URLs',$objectid[1],182,0,14,'1'           ,$dynamic_objects.'.xar_object_isalias'    ,$objectid[1],182 ,'integer (tiny)'),
                            array('isalias'   ,'Alias in short URLs',$objectid[1],182,0,14,'1'           ,$dynamic_objects.'.xar_object_isalias'    ,DD_PROPERTYSTATE_ACTIVE,182 ,'integer (tiny)'),
                            array('parent'    ,'Parent',             $objectid[1],182,0,600,'0'          ,$dynamic_objects.'.xar_object_parent'     ,DD_PROPERTYSTATE_ACTIVE,6 ,'integer'),
                            array('id'        ,'Id'                 ,$objectid[2],182,1,21,''            ,$dynamic_properties.'.xar_prop_id'        ,DD_PROPERTYSTATE_ACTIVE,1 ,'integer'),
                            array('name'      ,'Name'               ,$objectid[2],182,1,2 ,''            ,$dynamic_properties.'.xar_prop_name'      ,DD_PROPERTYSTATE_DISPLAYONLY,2 ,'varchar (30)'),
                            array('label'     ,'Label'              ,$objectid[2],182,1,2 ,''            ,$dynamic_properties.'.xar_prop_label'     ,DD_PROPERTYSTATE_ACTIVE,3 ,'varchar (254)'),
                            array('objectid'  ,'Object'             ,$objectid[2],182,1,24,''            ,$dynamic_properties.'.xar_prop_objectid'  ,DD_PROPERTYSTATE_ACTIVE,4 ,'integer'),
                            array('moduleid'  ,'Module'             ,$objectid[2],182,1,19,''            ,$dynamic_properties.'.xar_prop_moduleid'  ,DD_PROPERTYSTATE_DISPLAYONLY,5 ,'integer'),
                            array('itemtype'  ,'Item Type'          ,$objectid[2],182,1,20,''            ,$dynamic_properties.'.xar_prop_itemtype'  ,DD_PROPERTYSTATE_DISPLAYONLY,6 ,'integer'),
                            array('type'      ,'Property Type'      ,$objectid[2],182,1,22,''            ,$dynamic_properties.'.xar_prop_type'      ,DD_PROPERTYSTATE_ACTIVE,7 ,'integer'),
                            array('default'   ,'Default'            ,$objectid[2],182,1,3 ,''            ,$dynamic_properties.'.xar_prop_default'   ,DD_PROPERTYSTATE_ACTIVE,8 ,'varchar (254)'),
                            array('source'    ,'Source'             ,$objectid[2],182,1,23,'dynamic_data',$dynamic_properties.'.xar_prop_source'    ,DD_PROPERTYSTATE_ACTIVE,9 ,'varchar (254)'),
                            array('status'    ,'Status'             ,$objectid[2],182,1,25,'1'           ,$dynamic_properties.'.xar_prop_status'    ,DD_PROPERTYSTATE_ACTIVE,10,'integer (tiny)'),
                            array('order'     ,'Order'              ,$objectid[2],182,1,15,'0'           ,$dynamic_properties.'.xar_prop_order'     ,DD_PROPERTYSTATE_DISPLAYONLY,11,'integer (tiny)'),
                            array('validation','Validation'         ,$objectid[2],182,1,2 ,''            ,$dynamic_properties.'.xar_prop_validation',DD_PROPERTYSTATE_DISPLAYONLY,12,'varchar (254)'),
                            // 23 -> 26
                            array('id'        ,'Id'                 ,$objectid[3],182,2,21,''                         ,'dynamic_data',DD_PROPERTYSTATE_DISPLAYONLY,1,''),
                            array('name'      ,'Name'               ,$objectid[3],182,2,2 ,'please enter your name...','dynamic_data',DD_PROPERTYSTATE_ACTIVE,2,'1:30'),
                            array('age'       ,'Age'                ,$objectid[3],182,2,15,''                         ,'dynamic_data',DD_PROPERTYSTATE_ACTIVE,3,'0:125'),
                            array('location'  ,'Location'           ,$objectid[3],182,2,12,''                         ,'dynamic_data',DD_PROPERTYSTATE_DISPLAYONLY,4,'')
                            );

        $propid = array();
        $idx = 0;
        foreach ($properties as &$property) {
            $nextId = $dbconn->GenId($dynamic_properties);
            array_unshift($property, $nextId);
            $stmt->executeUpdate($property);
            $idx++;
            $propid[$idx] = $dbconn->PO_Insert_ID($dynamic_properties,'xar_prop_id');
        }


        /**
         * Dynamic Data table (= one of the possible data sources for properties)
         */
        $datafields = array('xar_dd_id'   => array('type'        => 'integer',
                                                   'null'        => false,
                                                   'default'     => '0',
                                                   'increment'   => true,
                                                   'primary_key' => true),
                            /* the property this dynamic data belongs to */
                            'xar_dd_propid'   => array('type'        => 'integer',
                                                       'null'        => false,
                                                       'default'     => '0'),
                            /* only needed if we go for freely extensible fields per item (not now)
                             'xar_dd_moduleid' => array('type'        => 'integer',
                             'null'        => false,
                             'default'     => '0'),
                             'xar_dd_itemtype' => array('type'        => 'integer',
                             'null'        => false,
                             'default'     => '0'),
                            */
                            /* the item id this dynamic data belongs to */
                            'xar_dd_itemid'   => array('type'        => 'integer',
                                                       'null'        => false,
                                                       'default'     => '0'),
                            /* the value of this dynamic data */
                            'xar_dd_value'    => array('type'        => 'text', // or blob when storing binary data (but not for PostgreSQL - see bug 1324)
                                                       'size'        => 'medium',
                                                       'null'        => 'false')
                            );

        // Create the Table - the function will return the SQL is successful or
        // raise an exception if it fails, in this case $query is empty
        $query = xarDBCreateTable($dynamic_data,$datafields);
        $dbconn->Execute($query);

        $query = xarDBCreateIndex($dynamic_data,
                                  array('name'   => 'i_' . xarDBGetSiteTablePrefix() . '_dyndata_propid',
                                        'fields' => array('xar_dd_propid')));
        $dbconn->Execute($query);

        $query = xarDBCreateIndex($dynamic_data,
                                  array('name'   => 'i_' . xarDBGetSiteTablePrefix() . '_dyndata_itemid',
                                        'fields' => array('xar_dd_itemid')));
        $dbconn->Execute($query);

        /**
         * Note : here we *could* start using the dynamicdata APIs, but since
         *        the module isn't activated yet, Xaraya doesn't like that either :-)
         */

        // we don't really need to create an object and properties for the dynamic data table

        // create some sample data for the sample object
        $sql = "INSERT INTO $dynamic_data (xar_dd_id, xar_dd_propid, xar_dd_itemid, xar_dd_value)
            VALUES (?,?,?,?)";
        $stmt = $dbconn->prepareStatement($sql);

        $dataentries = array(
                             array($propid[23],1,'1'),
                             array($propid[24],1,'Johnny'),
                             array($propid[25],1,'32'),
                             array($propid[26],1,'http://mikespub.net/xaraya/images/cuernos1.jpg'),

                             array($propid[23],2,'2'),
                             array($propid[24],2,'Nancy'),
                             array($propid[25],2,'29'),
                             array($propid[26],2,'http://mikespub.net/xaraya/images/agra1.jpg'),

                             array($propid[23],3,'3'),
                             array($propid[24],3,'Baby'),
                             array($propid[25],3,'1'),
                             array($propid[26],3,'http://mikespub.net/xaraya/images/sydney1.jpg')
                             );

        foreach ($dataentries as &$dataentry) {
            $nextId = $dbconn->GenId($dynamic_data);
            array_unshift($dataentry, $nextId);
            $stmt->executeUpdate($dataentry);
        }

        /**
         * Dynamic Relations table (= to keep track of relationships between objects)
         */
        $relationfields = array('xar_relation_id'    => array('type'        => 'integer',
                                                              'null'        => false,
                                                              'default'     => '0',
                                                              'increment'   => true,
                                                              'primary_key' => true),
                                // TODO:                /* more fields we need to add :) */
                                'xar_relation_todo'  => array('type'        => 'integer',
                                                              'null'        => false,
                                                              'default'     => '0'),
                                );

        // Create the Table - the function will return the SQL is successful or
        // raise an exception if it fails, in this case $query is empty
        $query = xarDBCreateTable($dynamic_relations,$relationfields);
        $dbconn->Execute($query);

        // Add Dynamic Data Properties Definition Table
        dynamicdata_createPropDefTable();

        /**
         * Set module variables
         */
        xarModSetVar('dynamicdata', 'SupportShortURLs', 1);

        /**
         * Register blocks
         */
        xarModAPIFunc('blocks','admin','register_block_type', array('modName'=>'dynamicdata','blockType'=>'form'));

        /**
         * Register hooks
         */
        // when a new module item is being specified
        xarModRegisterHook('item', 'new', 'GUI', 'dynamicdata', 'admin', 'newhook');

        // when a module item is created (uses 'dd_*')
        xarModRegisterHook('item', 'create', 'API','dynamicdata', 'admin', 'createhook');

        // when a module item is being modified (uses 'dd_*')
        xarModRegisterHook('item', 'modify', 'GUI','dynamicdata', 'admin', 'modifyhook');

        // when a module item is updated (uses 'dd_*')
        xarModRegisterHook('item', 'update', 'API','dynamicdata', 'admin', 'updatehook');

        // when a module item is deleted
        xarModRegisterHook('item', 'delete', 'API', 'dynamicdata', 'admin', 'deletehook');

        // when a module configuration is being modified (uses 'dd_*')
        xarModRegisterHook('module', 'modifyconfig', 'GUI','dynamicdata', 'admin', 'modifyconfighook');

        // when a module configuration is updated (uses 'dd_*')
        xarModRegisterHook('module', 'updateconfig', 'API','dynamicdata', 'admin', 'updateconfighook');

        // when a whole module is removed, e.g. via the modules admin screen
        // (set object ID to the module name !)
        xarModRegisterHook('module', 'remove', 'API','dynamicdata', 'admin', 'removehook');

        //  Ideally, people should be able to use the dynamic fields in their
        //  module templates as if they were normal fields, this means
        //  adapting the get() function in the user API of the module, and/or
        //  using some common data retrieval function (DD) in the future...

        /*  display hook is now disabled by default - use the BL tags or APIs instead
         // when a module item is being displayed
         xarModRegisterHook('item', 'display', 'GUI','dynamicdata', 'user', 'displayhook');
        */

        xarModRegisterHook('item', 'search', 'GUI', 'dynamicdata', 'user', 'search');

        /**
         * Register BL tags
         */
        // TODO: move this to some common place in Xaraya ?
        // Register BL user tags
        // output this property
        xarTplRegisterTag('dynamicdata', 'data-output', array(), 'dynamicdata_userapi_handleOutputTag');
        // display this item
        xarTplRegisterTag('dynamicdata', 'data-display',array(), 'dynamicdata_userapi_handleDisplayTag');
        // view a list of these items
        xarTplRegisterTag('dynamicdata', 'data-view', array(),'dynamicdata_userapi_handleViewTag');

        // Register BL admin tags
        // input field for this property
        xarTplRegisterTag('dynamicdata', 'data-input', array(), 'dynamicdata_adminapi_handleInputTag');
        // input form for this item
        xarTplRegisterTag('dynamicdata', 'data-form', array(), 'dynamicdata_adminapi_handleFormTag');
        // admin list for these items
        xarTplRegisterTag('dynamicdata', 'data-list', array(), 'dynamicdata_adminapi_handleListTag');

        // Register BL item tags to get properties and values directly in the template
        // get properties for this item
        xarTplRegisterTag('dynamicdata', 'data-getitem', array(),'dynamicdata_userapi_handleGetItemTag');
        // get properties and item values for these items
        xarTplRegisterTag('dynamicdata', 'data-getitems', array(),'dynamicdata_userapi_handleGetItemsTag');

        // Register BL utility tags to avoid OO problems with the BL compiler
        // get label for this object or property
        xarTplRegisterTag('dynamicdata', 'data-label', array(),'dynamicdata_userapi_handleLabelTag');
        // get value or invoke method for this object or property
        xarTplRegisterTag('dynamicdata', 'data-object', array(), 'dynamicdata_userapi_handleObjectTag');

        /*********************************************************************
         * Register the module components that are privileges objects
         * Format is
         * register(Name,Realm,Module,Component,Instance,Level,Description)
         *********************************************************************/

        xarRegisterMask('ViewDynamicData','All','dynamicdata','All','All','ACCESS_OVERVIEW');
        xarRegisterMask('EditDynamicData','All','dynamicdata','All','All','ACCESS_EDIT');
        xarRegisterMask('AdminDynamicData','All','dynamicdata','All','All','ACCESS_ADMIN');

        xarRegisterMask('ViewDynamicDataItems','All','dynamicdata','Item','All:All:All','ACCESS_OVERVIEW');
        xarRegisterMask('ReadDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_READ');
        xarRegisterMask('EditDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_EDIT');
        xarRegisterMask('AddDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_ADD');
        xarRegisterMask('DeleteDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_DELETE');
        xarRegisterMask('AdminDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_ADMIN');

        xarRegisterMask('ReadDynamicDataField','All','dynamicdata','Field','All:All:All','ACCESS_READ');
        xarRegisterMask('EditDynamicDataField','All','dynamicdata','Field','All:All:All','ACCESS_EDIT');
        xarRegisterMask('AddDynamicDataField','All','dynamicdata','Field','All:All:All','ACCESS_ADD');
        xarRegisterMask('DeleteDynamicDataField','All','dynamicdata','Field','All:All:All','ACCESS_DELETE');
        xarRegisterMask('AdminDynamicDataField','All','dynamicdata','Field','All:All:All','ACCESS_ADMIN');

        xarRegisterMask('ViewDynamicDataBlocks','All','dynamicdata','Block','All:All:All','ACCESS_OVERVIEW');
        xarRegisterMask('ReadDynamicDataBlock','All','dynamicdata','Block','All:All:All','ACCESS_READ');
        /*********************************************************************
         * Define instances for this module
         * Format is
         * setInstance(Module,Component,Query,ApplicationVar,LevelTable,ChildIDField,ParentIDField)
         *********************************************************************/

        $instances = array(
                           array('header' => 'external', // this keyword indicates an external "wizard"
                                 'query'  => xarModURL('dynamicdata', 'admin', 'privileges'),
                                 'limit'  => 0
                                 )
                           );
        xarDefineInstance('dynamicdata','Item',$instances);

        $instances = array(
                           array('header' => 'external', // this keyword indicates an external "wizard"
                                 'query'  => xarModURL('dynamicdata', 'admin', 'privileges'),
                                 'limit'  => 0
                                 )
                           );
        xarDefineInstance('dynamicdata','Field',$instances);

        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }
    // Initialisation successful
    return true;
}

    /**
 * upgrade the dynamicdata module from an old version
 * This function can be called multiple times
 */
function dynamicdata_upgrade($oldVersion)
{
    // Upgrade dependent on old version number
    switch($oldVersion) {
    case '1.0':
        // Code to upgrade from version 1.0 goes here

        // Register BL item tags to get properties and values directly in the template
        // get properties for this item
        xarTplRegisterTag('dynamicdata', 'data-getitem',
                          array(),
                          'dynamicdata_userapi_handleGetItemTag');
        // get properties and item values for these items
        xarTplRegisterTag('dynamicdata', 'data-getitems',
                          array(),
                          'dynamicdata_userapi_handleGetItemsTag');

        // for the switch from blob to text of the xar_dd_value field, no upgrade is necessary for MySQL,
        // and no simple upgrade is possible for PostgreSQL
    case '1.1':
        // Fall through to next upgrade

    case '1.1.0':
        xarRemoveInstances('dynamicdata');
        $instances = array(
                           array('header' => 'external', // this keyword indicates an external "wizard"
                                 'query'  => xarModURL('dynamicdata', 'admin', 'privileges'),
                                 'limit'  => 0
                                )
                        );
        xarDefineInstance('dynamicdata','Field',$instances);

        $instances = array(
                           array('header' => 'external', // this keyword indicates an external "wizard"
                                 'query'  => xarModURL('dynamicdata', 'admin', 'privileges'),
                                 'limit'  => 0
                                )
                        );
        xarDefineInstance('dynamicdata','Item',$instances);

        // Fall through to next upgrade
    case '1.2.0':
        // Add Dynamic Data Properties Definition Table
        if( !dynamicdata_createPropDefTable() ) return;

        // Fall through to next upgrade
    case '2.0.0':
        // Code to upgrade from version 2.0.0 goes here
        break;
    }

    // Update successful
    return true;
}

/**
 * delete the dynamicdata module
 * This function is only ever called once during the lifetime of a particular
 * module instance
 */
function dynamicdata_delete()
{

  //this module cannot be removed
  return false;

    /**
     * Drop tables
     */
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    //Load Table Maintenance API
    xarDBLoadTableMaintenanceAPI();

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartable['dynamic_objects']);
    if (empty($query)) return; // throw back
    $result = $dbconn->Execute($query);
    if (!isset($result)) return;

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartable['dynamic_properties']);
    if (empty($query)) return; // throw back
    $result = $dbconn->Execute($query);
    if (!isset($result)) return;

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartable['dynamic_data']);
    if (empty($query)) return; // throw back
    $result = $dbconn->Execute($query);
    if (!isset($result)) return;

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartable['dynamic_relations']);
    if (empty($query)) return; // throw back
    $result = $dbconn->Execute($query);
    if (!isset($result)) return;

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartable['dynamic_properties_def']);
    if (empty($query)) return; // throw back
    $result = $dbconn->Execute($query);
    if (!isset($result)) return;

    /**
     * Delete module variables
     */
    xarModDelVar('dynamicdata', 'SupportShortURLs');

    /**
     * Unregister blocks
     */
    if (!xarModAPIFunc('blocks',
                       'admin',
                       'unregister_block_type',
                       array('modName'  => 'dynamicdata',
                             'blockType'=> 'form'))) return;

    /**
     * Unregister hooks
     */
    // Remove module hooks
    if (!xarModUnregisterHook('item', 'new', 'GUI',
                             'dynamicdata', 'admin', 'newhook')) {
        xarSessionSetVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarModUnregisterHook('item', 'create', 'API',
                             'dynamicdata', 'admin', 'createhook')) {
        xarSessionSetVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarModUnregisterHook('item', 'modify', 'GUI',
                             'dynamicdata', 'admin', 'modifyhook')) {
        xarSessionSetVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarModUnregisterHook('item', 'update', 'API',
                             'dynamicdata', 'admin', 'updatehook')) {
        xarSessionSetVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarModUnregisterHook('item', 'delete', 'API',
                             'dynamicdata', 'admin', 'deletehook')) {
        xarSessionSetVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarModUnregisterHook('module', 'modifyconfig', 'GUI',
                             'dynamicdata', 'admin', 'modifyconfighook')) {
        xarSessionSetVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarModUnregisterHook('module', 'updateconfig', 'API',
                             'dynamicdata', 'admin', 'updateconfighook')) {
        xarSessionSetVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarModUnregisterHook('module', 'remove', 'API',
                             'dynamicdata', 'admin', 'removehook')) {
        xarSessionSetVar('errormsg', xarML('Could not unregister hook'));
    }

//  Ideally, people should be able to use the dynamic fields in their
//  module templates as if they were 'normal' fields -> this means
//  adapting the get() function in the user API of the module, and/or
//  using some common data retrieval function (DD) in the future...

/*  display hook is now disabled by default - use the BL tags or APIs instead
    if (!xarModUnregisterHook('item', 'display', 'GUI',
                             'dynamicdata', 'user', 'displayhook')) {
        xarSessionSetVar('errormsg', xarML('Could not unregister hook'));
    }
*/

    if (!xarModUnregisterHook('item', 'search', 'GUI',
                             'dynamicdata', 'user', 'search')) {
        xarSessionSetVar('errormsg', xarML('Could not unregister hook'));
    }

    /**
     * Unregister BL tags
     */
// TODO: move this to some common place in Xaraya ?
    // Unregister BL tags
    xarTplUnregisterTag('data-input');
    xarTplUnregisterTag('data-output');
    xarTplUnregisterTag('data-form');

    xarTplUnregisterTag('data-display');
    xarTplUnregisterTag('data-list');
    xarTplUnregisterTag('data-view');

    xarTplUnregisterTag('data-getitem');
    xarTplUnregisterTag('data-getitems');

    xarTplUnregisterTag('data-label');
    xarTplUnregisterTag('data-object');

    // Remove Masks and Instances
    xarRemoveMasks('dynamicdata');
    xarRemoveInstances('dynamicdata');


    // Deletion successful
    return true;
}

function dynamicdata_createPropDefTable()
{
    /**
      * Dynamic Data Properties Definition Table
      */

    // Get existing DB info
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $dynamic_properties_def = $xartable['dynamic_properties_def'];

    //Load Table Maintenance API
    xarDBLoadTableMaintenanceAPI();


    $propdefs = array('xar_prop_id'   => array('type'        => 'integer',
                                               'null'        => false,
                                               'default'     => '0',
                                               'increment'   => true,
                                               'primary_key' => true),
                      /* the name of this property */
                      'xar_prop_name'   => array('type'        => 'varchar',
                                                 'size'        => 254,
                                                 'default'     => NULL),
                      /* the label of this property */
                      'xar_prop_label'   => array('type'        => 'varchar',
                                                  'size'        => 254,
                                                  'default'     => NULL),
                      /* this property's parent */
                      'xar_prop_parent'   => array('type'        => 'varchar',
                                                   'size'        => 254,
                                                   'default'     => NULL),
                      /* path to the file defining this property */
                      'xar_prop_filepath'   => array('type'        => 'varchar',
                                                     'size'        => 254,
                                                     'default'     => NULL),
                      /* name of the Class to be instantiated for this property */
                      'xar_prop_class'   => array('type'        => 'varchar',
                                                  'size'        => 254,
                                                  'default'     => NULL),

                      /* the default validation string for this property - no need to use text here... */
                      'xar_prop_validation'   => array('type'        => 'varchar',
                                                       'size'        => 254,
                                                       'default'     => NULL),
                      /* the source of this property */
                      'xar_prop_source'   => array('type'        => 'varchar',
                                                   'size'        => 254,
                                                   'default'     => NULL),
                      /* the semi-colon seperated list of file required to be present before this property is active */
                      'xar_prop_reqfiles'   => array('type'        => 'varchar',
                                                     'size'        => 254,
                                                     'default'     => NULL),
                      /* the semi-colon seperated list of modules required to be active before this property is active */
                      'xar_prop_reqmodules'   => array('type'        => 'varchar',
                                                       'size'        => 254,
                                                       'default'     => NULL),
                      /* the default args for this property -- serialized array */
                      'xar_prop_args'    => array('type'        => 'text',
                                                  'size'        => 'medium',
                                                  'null'        => 'false'),

                      /*  */
                      'xar_prop_aliases'   => array('type'        => 'varchar',
                                                    'size'        => 254,
                                                    'default'     => NULL),
                      /*  */
                      'xar_prop_format'   => array('type'        => 'integer',
                                                   'default'     => '0'),
                      );

    // Create the Table - the function will return the SQL is successful or
    // raise an exception if it fails, in this case $query is empty

    $query = xarDBCreateTable($dynamic_properties_def,$propdefs);
    $dbconn->Execute($query);

    $query = xarDBCreateIndex($dynamic_properties_def,
                              array('name'   => 'i_' . xarDBGetSiteTablePrefix() . '_dynpropdef_mod',
                                    'fields' => array('xar_prop_reqmodules')));
    $dbconn->Execute($query);
    return true;
}
?>
