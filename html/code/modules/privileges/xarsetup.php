<?php
/**
 * @package modules
 * @subpackage privileges module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/1098.html
 */
/**
 * Default setup for roles and privileges
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
*/
function initializeSetup()
{

    /*********************************************************************
    * Define instances for the core modules
    * Format is
    * xarDefineInstance(Module,Component,Querystring,ApplicationVar,LevelTable,ChildIDField,ParentIDField)
    *********************************************************************/
    $prefix = xarDB::getPrefix();

    $blockTypesTable     = $prefix . '_block_types';
    $blockInstancesTable = $prefix . '_block_instances';
    $modulesTable        = $prefix . '_modules';
    $rolesTable          = $prefix . '_roles';
    $roleMembersTable    = $prefix . '_rolemembers';
    $privilegesTable     = $prefix . '_privileges';
    $privMembersTable    = $prefix . '_privmembers';
    $themesTable         = $prefix . '_themes';
    $categorytable       = $prefix . '_categories';

   //--------------------------------- Roles Module
    $info = xarMod::getBaseInfo('roles');
    $sysid = $info['systemid'];
    $query1 = "SELECT DISTINCT name FROM $blockTypesTable WHERE module_id = $sysid";
    $query2 = "SELECT DISTINCT instances.title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id WHERE module_id = $sysid";
    $query3 = "SELECT DISTINCT instances.id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id WHERE module_id = $sysid";
    $instances = array(array('header' => 'Block Type:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Block Title:',
                             'query' => $query2,
                             'limit' => 20),
                       array('header' => 'Block ID:',
                             'query' => $query3,
                             'limit' => 20));
    xarDefineInstance('roles','Block',$instances);

    $query = "SELECT DISTINCT name FROM $rolesTable";
    $instances = array(array('header' => 'Users and Groups',
                             'query' => $query,
                             'limit' => 20));
    xarDefineInstance('roles','Roles',$instances,0,$roleMembersTable,'id','parentid','Instances of the roles module, including multilevel nesting');

    $instances = array(array('header' => 'Parent:',
                             'query' => $query,
                             'limit' => 20),
                       array('header' => 'Child:',
                             'query' => $query,
                             'limit' => 20));
    xarDefineInstance('roles','Relation',$instances,0,$roleMembersTable,'id','parentid','Instances of the roles module, including multilevel nesting');

   // ----------------------------- Privileges Module
    $query = "SELECT DISTINCT name FROM $privilegesTable";
    $instances = array(array('header' => 'Privileges',
                             'query' => $query,
                             'limit' => 20));
    xarDefineInstance('privileges','Privileges',$instances,0,$privMembersTable,'privilege_id','parent_id','Instances of the privileges module, including multilevel nesting');

    // ----------------------------- Base Module
    $info = xarMod::getBaseInfo('base');
    $sysid = $info['systemid'];
    $query1 = "SELECT DISTINCT name FROM $blockTypesTable WHERE module_id = $sysid";
    $query2 = "SELECT DISTINCT instances.title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id WHERE module_id = $sysid";
    $query3 = "SELECT DISTINCT instances.id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id WHERE module_id = $sysid";
    $instances = array(array('header' => 'Block Type:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Block Title:',
                             'query' => $query2,
                             'limit' => 20),
                       array('header' => 'Block ID:',
                             'query' => $query3,
                             'limit' => 20));
    xarDefineInstance('base','Block',$instances);

   // ------------------------------- Themes Module
    $query1 = "SELECT DISTINCT name FROM $themesTable";
    $query2 = "SELECT DISTINCT regid FROM $themesTable";
    $instances = array(array('header' => 'Theme Name:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Theme ID:',
                             'query' => $query2,
                             'limit' => 20));
    xarDefineInstance('themes','Themes',$instances);

    $info = xarMod::getBaseInfo('themes');
    $sysid = $info['systemid'];
    $query1 = "SELECT DISTINCT name FROM $blockTypesTable WHERE module_id = $sysid";
    $query2 = "SELECT DISTINCT instances.title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id WHERE module_id = $sysid";
    $query3 = "SELECT DISTINCT instances.id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id WHERE module_id = $sysid";
    $instances = array(array('header' => 'Block Type:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Block Title:',
                             'query' => $query2,
                             'limit' => 20),
                       array('header' => 'Block ID:',
                             'query' => $query3,
                             'limit' => 20));
    xarDefineInstance('themes','Block',$instances);

   // ------------------------------- Categories Module
    $info = xarMod::getBaseInfo('categories');
    $sysid = $info['systemid'];
    $query = "SELECT DISTINCT instances.title FROM blockInstancesTable as instances LEFT JOIN blockTypesTable as btypes ON btypes.id = instances.type_id WHERE module_id = $sysid";
    $instances = array(
                        array('header' => 'Category Block Title:',
                                'query' => $query,
                                'limit' => 20
                            )
                    );
    xarDefineInstance('categories','Block',$instances);

    // use external privilege wizard for 'Category' and 'Link' instances
    $instances = array(
                       array('header' => 'external', // this keyword indicates an external "wizard"
                             'query'  => xarModURL('categories', 'admin', 'privileges'),
                             'limit'  => 0
                            )
                    );
    xarDefineInstance('categories', 'Link', $instances);
// TODO: get this parent/child stuff to work someday, or implement some other way ?
    //xarDefineInstance('categories', 'Category', $instances);
    xarDefineInstance('categories', 'Category', $instances,1,$categorytable,'id',
    'parent_id','Instances of the categories module, including multilevel nesting');

    /*********************************************************************
    * Register the module components that are privileges objects
    * Format is
    * xarMasks::register(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

//    xarMasks::register('AdminAll','All','All','All','All',xarSecurityLevel('ACCESS_ADMIN'));

    xarMasks::register('ViewBase','All','base','All','All',xarSecurityLevel('ACCESS_OVERVIEW'));
    xarMasks::register('ReadBase','All','base','All','All',xarSecurityLevel('ACCESS_READ'));
    xarMasks::register('EditBase','All','base','All','All',xarSecurityLevel('ACCESS_EDIT'));
    xarMasks::register('ManageBase','All','base','All','All',xarSecurityLevel('ACCESS_DELETE'));
    xarMasks::register('AdminBase','All','base','All','All',xarSecurityLevel('ACCESS_ADMIN'));
    /* This AdminPanel mask is added to replace the adminpanel module equivalent
     *   - since adminpanel module is removed as of 1.1.0
     * At some stage we should remove this but practice has been to use this mask in xarSecurityCheck
     * frequently in module code and templates - left here for now for ease in backward compatibiilty
     * @todo remove this
     */
    xarMasks::register('AdminPanel','All','base','All','All',xarSecurityLevel('ACCESS_ADMIN'));

    xarMasks::register('AdminInstaller','All','installer','All','All',xarSecurityLevel('ACCESS_ADMIN'));

    xarMasks::register('ViewRoles','All','roles','All','All',xarSecurityLevel('ACCESS_OVERVIEW'));
    xarMasks::register('ReadRoles','All','roles','All','All',xarSecurityLevel('ACCESS_READ'));
    xarMasks::register('EditRoles','All','roles','All','All',xarSecurityLevel('ACCESS_EDIT'));
    xarMasks::register('AddRoles','All','roles','All','All',xarSecurityLevel('ACCESS_ADD'));
    xarMasks::register('ManageRoles','All','roles','All','All',xarSecurityLevel('ACCESS_DELETE'));
    xarMasks::register('AdminRoles','All','roles','All','All',xarSecurityLevel('ACCESS_ADMIN'));
    xarMasks::register('MailRoles','All','roles','Mail','All',xarSecurityLevel('ACCESS_ADMIN'));

    xarMasks::register('AttachRole','All','roles','Relation','All',xarSecurityLevel('ACCESS_ADD'));
    xarMasks::register('RemoveRole','All','roles','Relation','All',xarSecurityLevel('ACCESS_DELETE'));

    xarMasks::register('ViewPrivileges','All','privileges','All','All',xarSecurityLevel('ACCESS_OVERVIEW'));
    xarMasks::register('EditPrivileges','All','privileges','All','All',xarSecurityLevel('ACCESS_EDIT'));
    xarMasks::register('AddPrivileges','All','privileges','All','All',xarSecurityLevel('ACCESS_ADD'));
    xarMasks::register('ManagePrivileges','All','privileges','All','All',xarSecurityLevel('ACCESS_DELETE'));
    xarMasks::register('AdminPrivileges','All','privileges','All','All',xarSecurityLevel('ACCESS_ADMIN'));

    xarMasks::register('ViewModules','All','modules','All','All',xarSecurityLevel('ACCESS_OVERVIEW'));
    xarMasks::register('EditModules','All','modules','All','All',xarSecurityLevel('ACCESS_EDIT'));
    xarMasks::register('ManageModules','All','modules','All','All',xarSecurityLevel('ACCESS_DELETE'));
    xarMasks::register('AdminModules','All','modules','All','All',xarSecurityLevel('ACCESS_ADMIN'));

    return true;
}
?>
