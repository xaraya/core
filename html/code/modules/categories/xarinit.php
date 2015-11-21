<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/147.html
 *
 * @author Jim McDonald, Flavio Botelho <nuncanada@xaraya.com>, mikespub <postnuke@mikespub.net>
 */

//Load Table Maintainance API
sys::import('xaraya.tableddl');
sys::import('xaraya.structures.query');

/**
 * * Initialise the categories module
 *
 * @author  Jim McDonald, Flavio Botelho <nuncanada@xaraya.com>
 * @author  mikespub <postnuke@mikespub.net>
 * @param   void N/A
 * @return  boolean True on success null/false on failure.
 */
function categories_init()
{
# --------------------------------------------------------
#
# Set up tables
#
    // Get database information
    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();

    $prefix = xarDB::getPrefix();

    $fields = array(
        'id' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'increment' => true, 'primary_key' => true),
        'name'        => array('type'=>'varchar','size'=>64,'null'=>false),
        'description' => array('type'=>'varchar','size'=>255,'null'=>false),
        'image'       => array('type'=>'varchar','size'=>255,'null'=>false),
        'template'    => array('type'=>'varchar','size'=>255,'null'=>false),
        'parent_id'   => array('type'=>'integer','null'=>false,'default'=>'0'),
        'left_id'     => array('type'=>'integer','null'=>true,'unsigned'=>true),
        'right_id'    => array('type'=>'integer','null'=>true,'unsigned'=>true),
        'child_object'=> array('type'=>'varchar','size'=>255,'null'=>false),
        'links'       => array('type'=>'integer','null'=>false,'default'=>'0','unsigned'=>true),
        'state'       => array('type'=>'integer','null'=>false,'default'=>'3')
    );
    $query = xarDBCreateTable($xartable['categories'],$fields);

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $index = array('name'      => 'i_' . $prefix . '_left_id',
                   'fields'    => array('left_id'),
                   'unique'    => FALSE);

    $query = xarDBCreateIndex($xartable['categories'],$index);

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $index = array('name'      => 'i_' . $prefix . '_right_id',
                   'fields'    => array('right_id'),
                   'unique'    => FALSE);

    $query = xarDBCreateIndex($xartable['categories'],$index);

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $index = array('name'      => 'i_' . $prefix . '_parent_id',
                   'fields'    => array('parent_id'),
                   'unique'    => FALSE);

    $query = xarDBCreateIndex($xartable['categories'],$index);

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $fields = array(
        'id'                 => array('type'=>'integer','unsigned'=>true,'null'=>false,'increment'=>true, 'primary_key' => true),
        'category_id'        => array('type'=>'integer','null'=>false,'unsigned'=>true,'default'=>'0'),
        'child_category_id'  => array('type'=>'integer','null'=>false,'unsigned'=>true,'default'=>'0'),
        'item_id'            => array('type'=>'integer','null'=>false,'unsigned'=>true,'default'=>'0'),
        'module_id'          => array('type'=>'integer','null'=>false,'unsigned'=>true,'default'=>'0'),
        'itemtype'           => array('type'=>'integer','null'=>false,'unsigned'=>true,'default'=>'0'),
        'property_id'        => array('type'=>'integer','null'=>false,'unsigned'=>true,'default'=>'0'),
        'basecategory'       => array('type'=>'integer','null'=>false,'unsigned'=>true,'default'=>'0')
    );
    $query = xarDBCreateTable($xartable['categories_linkage'],$fields);

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $index = array('name'      => 'i_' . $prefix . '_cat_linkage_1',
                   'fields'    => array('category_id'),
                   'unique'    => FALSE);

    $query = xarDBCreateIndex($xartable['categories_linkage'],$index);

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $q = new Query();
    $query = "DROP TABLE IF EXISTS " . $prefix . "_categories_basecategories";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_categories_basecategories (
      id integer unsigned NOT NULL auto_increment,
      category_id int(11) DEFAULT '1' NOT NULL,
      module_id int(11) DEFAULT NULL,
      itemtype int(11) DEFAULT NULL,
      name varchar(64) NOT NULL,
      selectable int(1) DEFAULT '1' NOT NULL,
      PRIMARY KEY  (id)
    )";
    if (!$q->run($query)) return;

# --------------------------------------------------------
#
# Set up hooks
#
    // when a new module item is being specified
    if (!xarModRegisterHook('item', 'new', 'GUI', 'categories', 'admin', 'newhook'))  return false;

    // when a module item is created (uses 'cids')
    if (!xarModRegisterHook('item', 'create', 'API', 'categories', 'admin', 'createhook')) return false;

    // when a module item is being modified (uses 'cids')
    if (!xarModRegisterHook('item', 'modify', 'GUI', 'categories', 'admin', 'modifyhook')) return false;

    // when a module item is updated (uses 'cids')
    if (!xarModRegisterHook('item', 'update', 'API', 'categories', 'admin', 'updatehook')) return false;

    // when a module item is deleted
    if (!xarModRegisterHook('item', 'delete', 'API', 'categories', 'admin', 'deletehook')) return false;

    // when a module configuration is being modified (uses 'cids')
    if (!xarModRegisterHook('module', 'modifyconfig', 'GUI', 'categories', 'admin', 'modifyconfighook')) return false;

    // when a module configuration is updated (uses 'cids')
    if (!xarModRegisterHook('module', 'updateconfig', 'API', 'categories', 'admin', 'updateconfighook')) return false;

    // when a whole module is removed, e.g. via the modules admin screen
    // (set object ID to the module name !)
    if (!xarModRegisterHook('module', 'remove', 'API', 'categories', 'admin', 'removehook'))  return false;

    /*********************************************************************
    * Define instances for this module
    * Format is
    * setInstance(Module,Type,ModuleTable,IDField,NameField,ApplicationVar,LevelTable,ChildIDField,ParentIDField)
    *********************************************************************/
/*
    $query1 = "SELECT DISTINCT name FROM ".$categorytable;
    $query2 = "SELECT DISTINCT id FROM ".$categorytable;
    $instances = array(
                        array('header' => 'Category Name:',
                                'query' => $query1,
                                'limit' => 20
                            ),
                        array('header' => 'Category ID:',
                                'query' => $query2,
                                'limit' => 20
                            )
                    );
    xarDefineInstance('categories','Category',$instances,1,$categorytable,'id',
    'parent_id','Instances of the categories module, including multilevel nesting');
*/

# --------------------------------------------------------
#
# Set up masks
#
    xarRegisterMask('ViewCategories','All','categories','Category','All:All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadCategories','All','categories','Category','All:All','ACCESS_READ');
    xarRegisterMask('CommmentCategories','All','categories','Category','All:All','ACCESS_COMMENT');
    xarRegisterMask('ModerateCategories','All','categories','Category','All:All','ACCESS_MODERATE');
    xarRegisterMask('EditCategories','All','categories','Category','All:All','ACCESS_EDIT');
    xarRegisterMask('AddCategories','All','categories','Category','All:All','ACCESS_ADD');
    xarRegisterMask('ManageCategories','All','categories','Category','All:All','ACCESS_DELETE');
    xarRegisterMask('AdminCategories','All','categories','Category','All:All','ACCESS_ADMIN');

    xarRegisterMask('ReadCategoryBlock','All','categories','Block','All:All:All','ACCESS_READ');

    xarRegisterMask('ViewCategoryLink','All','categories','Link','All:All:All:All','ACCESS_OVERVIEW');
    xarRegisterMask('SubmitCategoryLink','All','categories','Link','All:All:All:All','ACCESS_COMMENT');
    xarRegisterMask('EditCategoryLink','All','categories','Link','All:All:All:All','ACCESS_EDIT');
    xarRegisterMask('ManageCategoryLink','All','categories','Link','All:All:All:All','ACCESS_DELETE');

# --------------------------------------------------------
#
# Set up privileges
#
    xarRegisterPrivilege('ViewCategories','All','categories','Category','All','ACCESS_OVERVIEW');
    xarRegisterPrivilege('ReadCategories','All','categories','Category','All','ACCESS_READ');
    xarRegisterPrivilege('CommmentCategories','All','categories','Category','All','ACCESS_COMMENT');
    xarRegisterPrivilege('ModerateCategories','All','categories','Category','All','ACCESS_MODERATE');
    xarRegisterPrivilege('EditCategories','All','categories','Category','All','ACCESS_EDIT');
    xarRegisterPrivilege('AddCategories','All','categories','Category','All','ACCESS_ADD');
    xarRegisterPrivilege('ManageCategories','All','categories','Category','All:All','ACCESS_DELETE');
    xarRegisterPrivilege('AdminCategories','All','categories','Category','All','ACCESS_ADMIN');

# --------------------------------------------------------
#
# Set up modvars
#
    xarModVars::set('categories', 'usejsdisplay', 0);
    xarModVars::set('categories', 'numstats', 100);
    xarModVars::set('categories', 'showtitle', 1);
    xarModVars::set('categories', 'categoriesobject', 'categories');

    // Initialisation successful
    return true;
}

/**
 * Upgrade the categories module from an old version
 *
 * @author  Jim McDonald, Flavio Botelho <nuncanada@xaraya.com>, mikespub <postnuke@mikespub.net>
 * @access  public
 * @param   $oldVersion
 * @return  true on success or false on failure
 * @throws  no exceptions
 * @todo    nothing
*/
function categories_upgrade($oldversion)
{
    // Get database information
    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();

    // Upgrade dependent on old version number
    switch($oldversion) {
        case '2.6.0':
            // Code to upgrade from version 2.6.0 goes here
            // fall through to the next upgrade

            break;
    }

    // Upgrade successful
    return true;
}

function categories_delete()
{
  //this module cannot be removed
  return false;
}

?>