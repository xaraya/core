<?php
/**
 * Dynamic data initialization
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
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
    $xartable = xarDB::getTables();
    $prefix = xarDB::getPrefix();

    $dynamic_objects = $xartable['dynamic_objects'];
    $dynamic_properties = $xartable['dynamic_properties'];
    $dynamic_data = $xartable['dynamic_data'];
    $dynamic_relations = $xartable['dynamic_relations'];
    $dynamic_properties_def = $xartable['dynamic_properties_def'];
    $modulestable = $xartable['modules'];

    // Create tables inside a transaction
    try {
        $dbconn->begin();
        /**
         * DataObjects table
         */
        $objectfields = array(
            'id' => array(
                'type'        => 'integer',
                'null'        => false,
                'default'     => '0',
                'increment'   => true,
                'primary_key' => true
            ),
            /* the name used to reference an object */
            'name'     => array(
                'type'        => 'varchar',
                'size'        => 30,
                'null'        => false,
                'default'     => ''
            ),
            /* the label used for display */
            'label'    => array(
                'type'        => 'varchar',
                'size'        => 254,
                'null'        => false,
                'default'     => ''
            ),
            /* the module this object relates to */
            'module_id' => array(
                'type'        => 'integer',
                'null'        => false,
                'default'     => '0'
            ),
            /* the optional item type within this module */
            'itemtype' => array(
                'type'        => 'integer',
                'null'        => false,
                'default'     => '0'
            ),
            /* the item type of the parent of this object */
            'parent' => array(
                'type'        => 'integer',
                'null'        => false,
                'default'     => '0'
            ),
            /* the class this object belongs to*/
            'class'     => array(
                'type'        => 'varchar',
                'size'        => 255,
                'null'        => false,
                'default'     => 'DataObject'
            ),
            /* the location where the class file lives*/
            'filepath'     => array(
                'type'        => 'varchar',
                'size'        => 255,
                'null'        => false,
                'default'     => 'modules/dynamicdata/class/objects/base.php'
            ),
            /* the URL parameter used to pass on the item id to the original module */
            'urlparam' => array(
                'type'        => 'varchar',
                'size'        => 30,
                'null'        => false,
                'default'     => 'itemid'
            ),
            /* the highest item id for this object (used if the object has a dynamic item id field) */
            'maxid'    => array(
                'type'        => 'integer',
                'null'        => false,
                'default'     => '0'
            ),
            /* any configuration settings for this object (future) */
            'config'   => array(
                'type'=>'text'
            ),
            /* use the name of this object as alias for short URLs */
            'isalias'  => array(
                'type'        => 'integer',
                'size'        => 'tiny',
                'null'        => false,
                'default'     => '1'
            ),
        );

        $query = xarDBCreateTable($dynamic_objects,$objectfields);
        $dbconn->Execute($query);

        // TODO: evaluate efficiency of combined index vs. individual ones
        // the combination of module id + item type *must* be unique
        $query = xarDBCreateIndex(
            $dynamic_objects,
            array(
                'name'   => 'i_' . $prefix . '_dynobjects_combo',
                'fields' => array('module_id','itemtype'),
                'unique' => 'true'
            )
        );
        $dbconn->Execute($query);

        // the object name *must* be unique
        $query = xarDBCreateIndex(
            $dynamic_objects,
            array(
                'name'   => 'i_' . $prefix . '_dynobjects_name',
                'fields' => array('name'),
                'unique' => 'true'
            )
        );
        $dbconn->Execute($query);

        /**
         * Note : Classic chicken and egg problem - we can't use createobject() here
         *        because dynamicdata doesn't know anything about objects yet :-)
         */

        $modid = xarModGetIDFromName('dynamicdata');

        // create default objects for dynamic data
        $sql = "INSERT INTO $dynamic_objects (
                name, label,
                module_id, itemtype, class, filepath, urlparam,
                maxid, config, isalias)
                VALUES (?,?,?,?,?,?,?,?,?,?)";
        $stmt = $dbconn->prepareStatement($sql);

        $objects = array(
            array('objects'   ,'Dynamic Objects'   ,$modid,0,'','',                                               'itemid',0,''               ,0),
            array('properties','Dynamic Properties',$modid,1,'DProperty','modules/dynamicdata/class/property.php','itemid',0,''               ,0),
            array('sample'    ,'Sample Object'     ,$modid,2,'','',                                               'itemid',3,'nothing much...',0)
        );

        $objectid = array();
        $idx = 0;
        foreach ($objects as &$object) {
            $stmt->executeUpdate($object);
            $idx++;
            $objectid[$idx] = $dbconn->getLastId($dynamic_objects);
        }


        /**
         * Dynamic Properties table
         */
        $propfields = array(
            'id'     => array(
                'type'        => 'integer',
                'null'        => false,
                'default'     => '0',
                'increment'   => true,
                'primary_key' => true
            ),
            /* the name used to reference a particular property, e.g. in function calls and templates */
            'name'       => array(
                'type'        => 'varchar',
                'size'        => 30,
                'null'        => false,
                'default'     => ''
            ),
            /* the label used for display */
            'label'      => array(
                'type'        => 'varchar',
                'size'        => 254,
                'null'        => false,
                'default'     => ''
            ),
            /* the object this property belong to */
            'object_id'   => array(
                'type'        => 'integer',
                'null'        => false,
                'default'     => '0'
            ),
            /* the property type of this property */
            'type'       => array(
                'type'        => 'integer',
                'null'        => false,
                'default'     => null
            ),
            /* the default value for this property */
            'defaultvalue'    => array(
                'type'        => 'varchar',
                'size'        => 254,
                'default'     => null
            ),
            /* the data source for this property (dynamic data, static table, hook, user function, LDAP (?), file, ... */
            'source'     => array(
                'type'        => 'varchar',
                'size'        => 254,
                'null'        => false,
                'default'     => 'dynamic_data'
            ),
            /* is this property active ? (unused at the moment) */
            'status'     => array(
                'type'        => 'integer',
                'null'        => false,
                'default'     => '33'
            ),
            /* the order of this property */
            'seq'      => array(
                'type'        => 'integer',
                'size'        => 'tiny',
                'null'        => false,
                'default'     => '0'
            ),
            /* specific validation rules for this property (e.g. basedir, size, ...) */
            'validation' => array(
                'type'        => 'text'
            )
        );

        $query = xarDBCreateTable($dynamic_properties,$propfields);
        $dbconn->Execute($query);

        $query = xarDBCreateIndex(
            $dynamic_properties,
            array(
                'name'   => 'i_' . $prefix . '_dynprops_combo',
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
                status, seq, validation)
            VALUES (?,?,?,?,?,?,?,?,?)";
        $stmt = $dbconn->prepareStatement($sql);

        // TEMP FIX for the constants, rewrite this
        sys::import('modules.dynamicdata.class.properties');
        $properties = array(
            // Properties for the Objects DD object
            array('objectid'  ,'Id'                 ,$objectid[1],21,''            ,$dynamic_objects.'.id'         ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_NOINPUT,1 ,'DataPropertyMaster::integer'),
            array('name'      ,'Name'               ,$objectid[1],2 ,''            ,$dynamic_objects.'.name'       ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,2 ,'varchar (30)'),
            array('label'     ,'Label'              ,$objectid[1],2 ,''            ,$dynamic_objects.'.label'      ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,3 ,'varchar (254)'),
            array('parent'    ,'Parent',             $objectid[1],24,'0'           ,$dynamic_objects.'.parent'     ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,4 ,'a:2:{s:10:"validation";s:7:"integer";s:8:"override";s:1:"1";}'),
            array('module_id' ,'Module'             ,$objectid[1],19,'182'         ,$dynamic_objects.'.module_id'  ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,5 ,'regid'), // FIXME: change this validation when we move from regid to systemid
            array('itemtype'  ,'Item Type'          ,$objectid[1],20,'0'           ,$dynamic_objects.'.itemtype'   ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,6 ,'integer'),
            array('class'     ,'Class'              ,$objectid[1],2 ,'DataObject'  ,$dynamic_objects.'.class'      ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,7 ,'varchar (255)'),
            array('filepath'  ,'Location'           ,$objectid[1],2 ,''            ,$dynamic_objects.'.filepath'   ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,8 ,'varchar (255)'),
            array('urlparam'  ,'URL Param'          ,$objectid[1],2 ,'itemid'      ,$dynamic_objects.'.urlparam'   ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,9 ,'varchar (30)'),
            array('maxid'     ,'Max Id'             ,$objectid[1],15,'0'           ,$dynamic_objects.'.maxid'      ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,10 ,'integer'),
            array('config'    ,'Config'             ,$objectid[1],4 ,''            ,$dynamic_objects.'.config'     ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,11 ,'text'),
            array('isalias'   ,'Alias in short URLs',$objectid[1],14,'1'           ,$dynamic_objects.'.isalias'    ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,12 ,'integer (tiny)'),

            // Properties for the Properties DD object
            array('id'        ,'Id'                 ,$objectid[2],21,''            ,$dynamic_properties.'.id'        ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,1 ,'integer'),
            array('name'      ,'Name'               ,$objectid[2],2 ,''            ,$dynamic_properties.'.name'      ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,2 ,'varchar (30)'),
            array('label'     ,'Label'              ,$objectid[2],2 ,''            ,$dynamic_properties.'.label'     ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,3 ,'varchar (254)'),
            array('objectid'  ,'Object'             ,$objectid[2],24,''            ,$dynamic_properties.'.object_id'  ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,4 ,'integer'),
            array('type'      ,'Property Type'      ,$objectid[2],22,''            ,$dynamic_properties.'.type'      ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,7 ,'integer'),
            array('defaultvalue' ,'Default'         ,$objectid[2],3 ,''            ,$dynamic_properties.'.defaultvalue'   ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,8 ,'varchar (254)'),
            array('source'    ,'Source'             ,$objectid[2],23,'dynamic_data',$dynamic_properties.'.source'    ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE,9 ,'varchar (254)'),
            array('status'    ,'Status'             ,$objectid[2],25,'1'           ,$dynamic_properties.'.status'    ,DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,10,'integer (tiny)'),
            array('seq  '     ,'Order'              ,$objectid[2],15,'0'           ,$dynamic_properties.'.seq'     ,DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,11,'integer (tiny)'),
            array('validation','Validation'         ,$objectid[2],3 ,''            ,$dynamic_properties.'.validation',DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,12,'text'),

            // Properties for the Sample DD object
            // @todo import this
            array('id'        ,'Id'                 ,$objectid[3],21,''                         ,'dynamic_data',DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,1,''),
            array('name'      ,'Name'               ,$objectid[3],2 ,'please enter your name...','dynamic_data',DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,2,'1:30'),
            array('age'       ,'Age'                ,$objectid[3],15,''                         ,'dynamic_data',DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,3,'0:125'),
            array('location'  ,'Location'           ,$objectid[3],12,''                         ,'dynamic_data',DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY,4,'')
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
                'null'        => false,
                'default'     => '0',
                'increment'   => true,
                'primary_key' => true
            ),
            /* the property this dynamic data belongs to */
            'property_id'   => array(
                'type'        => 'integer',
                'null'        => false,
                'default'     => '0'
            ),
            /* the item id this dynamic data belongs to */
            'itemid'   => array(
                'type'        => 'integer',
                'null'        => false,
                'default'     => '0'
            ),
            /* the value of this dynamic data */
            'value'    => array(
                'type'        => 'text', // or blob when storing binary data (but not for PostgreSQL - see bug 1324)
                'size'        => 'medium',
                'null'        => 'false'
            )
        );

        // Create the Table - the function will return the SQL is successful or
        // raise an exception if it fails, in this case $query is empty
        $query = xarDBCreateTable($dynamic_data,$datafields);
        $dbconn->Execute($query);

        $query = xarDBCreateIndex(
            $dynamic_data,
            array(
                'name'   => 'i_' . $prefix . '_dyndata_property_id',
                'fields' => array('property_id')
            )
        );
        $dbconn->Execute($query);

        $query = xarDBCreateIndex(
            $dynamic_data,
            array(
                'name'   => 'i_' . $prefix . '_dyndata_itemid',
                'fields' => array('itemid')
            )
        );
        $dbconn->Execute($query);

        /**
         * Note : here we *could* start using the dynamicdata APIs, but since
         *        the module isn't activated yet, Xaraya doesn't like that either :-)
         */

        // we don't really need to create an object and properties for the dynamic data table

        // create some sample data for the sample object
        $sql = "INSERT INTO $dynamic_data (property_id, itemid, value)
            VALUES (?,?,?)";
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
            $stmt->executeUpdate($dataentry);
        }

        // Add Dynamic Data Properties Definition Table
        dynamicdata_createPropDefTable();

        $dbconn->commit();
    } catch (Exception $e) {
        // nice try
        $dbconn->rollback();
        throw $e;
    }

    /**
     * Set module variables
     */
    xarModVars::set('dynamicdata', 'SupportShortURLs', 1);

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
        array(
            'header' => 'external', // this keyword indicates an external "wizard"
            'query'  => xarModURL('dynamicdata', 'admin', 'privileges'),
            'limit'  => 0
        )
    );
    xarDefineInstance('dynamicdata','Item',$instances);

    $instances = array(
        array(
            'header' => 'external', // this keyword indicates an external "wizard"
            'query'  => xarModURL('dynamicdata', 'admin', 'privileges'),
            'limit'  => 0
        )
    );
    xarDefineInstance('dynamicdata','Field',$instances);

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

        // for the switch from blob to text of the value field, no upgrade is necessary for MySQL,
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
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();


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
    xarModVars::delete('dynamicdata', 'SupportShortURLs');

    /**
     * Unregister blocks
     */
    if (!xarModAPIFunc(
        'blocks',
        'admin',
        'unregister_block_type',
        array(
            'modName'  => 'dynamicdata',
            'blockType'=> 'form'
        )
    )) return;

    /**
     * Unregister hooks
     */
    // Remove module hooks
    if (!xarModUnregisterHook('item', 'new', 'GUI',
                             'dynamicdata', 'admin', 'newhook')) {
        xarSession::setVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarModUnregisterHook('item', 'create', 'API',
                             'dynamicdata', 'admin', 'createhook')) {
        xarSession::setVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarModUnregisterHook('item', 'modify', 'GUI',
                             'dynamicdata', 'admin', 'modifyhook')) {
        xarSession::setVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarModUnregisterHook('item', 'update', 'API',
                             'dynamicdata', 'admin', 'updatehook')) {
        xarSession::setVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarModUnregisterHook('item', 'delete', 'API',
                             'dynamicdata', 'admin', 'deletehook')) {
        xarSession::setVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarModUnregisterHook('module', 'modifyconfig', 'GUI',
                             'dynamicdata', 'admin', 'modifyconfighook')) {
        xarSession::setVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarModUnregisterHook('module', 'updateconfig', 'API',
                             'dynamicdata', 'admin', 'updateconfighook')) {
        xarSession::setVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarModUnregisterHook('module', 'remove', 'API',
                             'dynamicdata', 'admin', 'removehook')) {
        xarSession::setVar('errormsg', xarML('Could not unregister hook'));
    }

//  Ideally, people should be able to use the dynamic fields in their
//  module templates as if they were 'normal' fields -> this means
//  adapting the get() function in the user API of the module, and/or
//  using some common data retrieval function (DD) in the future...

/*  display hook is now disabled by default - use the BL tags or APIs instead
    if (!xarModUnregisterHook('item', 'display', 'GUI',
                             'dynamicdata', 'user', 'displayhook')) {
        xarSession::setVar('errormsg', xarML('Could not unregister hook'));
    }
*/

    if (!xarModUnregisterHook('item', 'search', 'GUI',
                             'dynamicdata', 'user', 'search')) {
        xarSession::setVar('errormsg', xarML('Could not unregister hook'));
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
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $prefix = xarDB::getPrefix();
    $dynamic_properties_def = $xartable['dynamic_properties_def'];

    $propdefs = array(
        'id'     => array(
            'type'        => 'integer',
            'null'        => false,
            'default'     => '0',
            'increment'   => true,
            'primary_key' => true
        ),
        /* the name of this property */
        'name'   => array(
            'type'        => 'varchar',
            'size'        => 254,
            'default'     => null
        ),
        /* the label of this property */
        'label'  => array(
            'type'        => 'varchar',
            'size'        => 254,
            'default'     => null
        ),
        /* this property's parent */
        'parent' => array(
            'type'        => 'varchar',
            'size'        => 254,
            'default'     => null
        ),
        /* path to the file defining this property */
        'filepath' => array(
            'type'          => 'varchar',
            'size'          => 254,
            'default'       => null
        ),
        /* name of the Class to be instantiated for this property */
        'class'  => array(
            'type'        => 'varchar',
            'size'        => 254,
            'default'     => null
        ),
        /* the default validation string for this property - no need to use text here... */
        'validation'   => array(
            'type'              => 'varchar',
            'size'              => 254,
            'default'           => null
        ),
        /* the source of this property */
        'source'   => array(
            'type'        => 'varchar',
            'size'        => 254,
            'default'     => null
        ),
        /* the semi-colon seperated list of file required to be present before this property is active */
        'reqfiles'   => array(
            'type'        => 'varchar',
            'size'        => 254,
            'default'     => null
        ),
        /* the ID of the module owning this property */
        'modid'  => array(
            'type'        => 'integer',
            'null'        => true,
            'default'     => null
        ),
        /* the default args for this property -- serialized array */
        'args'    => array(
            'type'        => 'text',
            'size'        => 'medium',
            'null'        => false
        ),
        /* the aliases for this property -- serialized array */
        'aliases'   => array(
            'type'        => 'varchar',
            'size'        => 254,
            'default'     => null
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
            'name'   => 'i_' . $prefix . '_dynpropdef_modid',
            'fields' => array('modid')
        )
    );
    $dbconn->Execute($query);
    return true;
}
?>
