<?php
/**
 * Installer
 *
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

/* Do not allow this script to run if the install script has been removed.
 * This assumes the install.php and index.php are in the same directory.
 * @author Paul Rosania
 * @author Marcel van der Boom <marcel@hsdev.com>
 */

/**
 * Create default administrator and default blocks
 *
 * @access public
 * @param create
 * @return bool
 * @todo make confirm password work
 * @todo remove URL field from users table
 * @todo normalize user's table
 */
function installer_admin_create_administrator()
{
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

    xarVarSetCached('installer','installing', true);
    xarTplSetThemeName('installer');

    $data['language'] = $install_language;
    $data['phase'] = 6;
    $data['phase_label'] = xarML('Create Administrator');

    sys::import('modules.roles.class.roles');
    $data['admin'] = xarRoles::getRole((int)xarModVars::get('roles','admin'));

    // Set up some custom validation checks and messages
    $data['admin']->properties['name']->display_layout = 'single';
    $data['admin']->properties['name']->validation_min_length = 4;
    $data['admin']->properties['name']->validation_min_length_invalid = xarML('The display name must be at least 4 characters long');
    $data['admin']->properties['uname']->validation_min_length = 4;
    $data['admin']->properties['uname']->validation_min_length_invalid = xarML('The user name must be at least 4 characters long');
    $data['admin']->properties['password']->validation_min_length = 4;
    $data['admin']->properties['password']->validation_min_length_invalid = xarML('The password must be at least 4 characters long');
    $data['admin']->properties['password']->validation_password_confirm = 1;
    $data['admin']->properties['email']->validation_min_length = 1;
    $data['admin']->properties['email']->validation_min_length_invalid = xarML('An email address must be entered');

    $data['properties'] = $data['admin']->getProperties();

    if (!xarVarFetch('create', 'isset', $create, FALSE, XARVAR_NOT_REQUIRED)) return;
    if (!$create) {
        return $data;
    }

    $isvalid = $data['admin']->checkInput();
    if (!$isvalid) {
        return xarTplModule('installer','admin','create_administrator',$data);
    }

    xarModVars::set('mail', 'adminname', $data['admin']->properties['name']->getValue());
    xarModVars::set('mail', 'adminmail', $data['admin']->properties['email']->getValue());
    xarModVars::set('themes', 'SiteCopyRight', '&copy; Copyright ' . date("Y") . ' ' . $data['admin']->properties['name']->getValue());
    xarModVars::set('roles', 'lastuser', $data['admin']->properties['uname']->getValue());
    xarModVars::set('roles', 'adminpass', $data['admin']->properties['password']->password);

// CHECKME: misc. undefined module variables
    xarModVars::set('themes', 'var_dump', false);
    xarModVars::set('base', 'releasenumber', 10);
    xarModVars::set('base', 'AlternatePageTemplateName', '');
    xarModVars::set('base', 'UseAlternatePageTemplate', false);
    xarModVars::set('base', 'editor', 'none');
    xarModVars::set('base', 'proxyhost', '');
    xarModVars::set('base', 'proxyport', 0);

    //Try to update the role to the repository and bail if an error was thrown
    $itemid = $data['admin']->updateItem();
    if (!$itemid) {return;}

    // Register blockgroup block type (blocks)
    // @CHECKME: move this to blocks init?
    if (!xarMod::apiFunc('blocks', 'admin', 'register_block_type',
        array('modName'  => 'blocks', 'blockType'=> 'blockgroup'))) return;

    // Register Block types from modules installed before block apis (base)
    $blocks = array('adminmenu','waitingcontent','finclude','menu','content');

    foreach ($blocks as $block) {
        if (!xarMod::apiFunc('blocks', 'admin', 'register_block_type', array('modName'  => 'base', 'blockType'=> $block))) return;
    }

    if (xarVarIsCached('Mod.BaseInfos', 'blocks')) xarVarDelCached('Mod.BaseInfos', 'blocks');

    // get blockgroup block id
    $blockgroupBlockType = xarModAPIFunc('blocks', 'user', 'getblocktype',
                                    array('module'  => 'blocks',
                                          'type'    => 'blockgroup'));

    $blockgroupBlockTypeID = $blockgroupBlockType['tid'];
    assert('is_numeric($blockgroupBlockTypeID);');

    // Create default block groups/instances
    //                            name        template
    $default_blockgroups = array ('left'   => null,
                                  'right'  => 'right',
                                  'header' => 'header',
                                  'admin'  => null,
                                  'center' => 'center',
                                  'topnav' => 'topnav'
                                  );

    foreach ($default_blockgroups as $name => $template) {
        if(!xarMod::apiFunc('blocks', 'user', 'get', array('name' => $name))) {
            // Not there yet
            if (!xarMod::apiFunc('blocks', 'admin', 'create_instance',
                 array('name' => $name, 'template' => $template,
                       'type' => $blockgroupBlockTypeID, 'state' => 2))) return;
        }
    }

    // get the admin blockgroup block id
    $adminBlockgroup = xarMod::apiFunc('blocks', 'user', 'get', array('name' => 'admin'));
    if ($adminBlockgroup == false) {
        $msg = xarML("Blockgroup 'admin' not found.");
        throw new Exception($msg);
    }
    $adminBlockgroupID = $adminBlockgroup['bid'];
    assert('is_numeric($adminBlockgroupID);');

    $adminBlockType = xarMod::apiFunc('blocks', 'user', 'getblocktype',
                                    array('module'  => 'base',
                                          'type'    => 'adminmenu'));

    $adminBlockTypeId = $adminBlockType['tid'];
    assert('is_numeric($adminBlockTypeId);');
    if (!xarMod::apiFunc('blocks', 'user', 'get', array('name'  => 'adminpanel'))) {
        if (!xarMod::apiFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Admin',
                                 'name'     => 'adminpanel',
                                 'type'     => $adminBlockTypeId,
                                 'groups'   => array(array('id' => $adminBlockgroupID)),
                                 'state'    =>  2))) {
            return;
        }
    }

    /*********************************************************************
    * Enter some default privileges
    * Format is
    * register(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

    xarRegisterPrivilege('Administration','All','All','All','All','ACCESS_ADMIN',xarML('Admin access to all modules'));
    xarRegisterPrivilege('SiteManagement','All','All','All','All','ACCESS_DELETE',xarML('Site Manager access to all modules'));
    xarRegisterPrivilege('GeneralLock','All',null,'All','All','ACCESS_NONE',xarML('A container privilege for denying access to certain roles'));
    xarRegisterPrivilege('LockEverybody','All','roles','Roles','Everybody','ACCESS_NONE',xarML('Deny access to Everybody role'));
    xarRegisterPrivilege('LockAnonymous','All','roles','Roles','Anonymous','ACCESS_NONE',xarML('Deny access to Anonymous role'));
    xarRegisterPrivilege('LockAdministrators','All','roles','Roles','Administrators','ACCESS_NONE',xarML('Deny access to Administrators role'));
    xarRegisterPrivilege('LockAdministration','All','privileges','Privileges','Administration','ACCESS_NONE',xarML('Deny access to Administration privilege'));
    xarRegisterPrivilege('LockGeneralLock','All','privileges','Privileges','GeneralLock','ACCESS_NONE',xarML('Deny access to GeneralLock privilege'));
    xarRegisterPrivilege('ReadAccess','All','All','All','All','ACCESS_READ','Read access to all modules');

    /*********************************************************************
    * Arrange the  privileges in a hierarchy
    * Format is
    * xarMakePrivilegeMember(Child,Parent)
    *********************************************************************/

    xarMakePrivilegeMember('LockEverybody','GeneralLock');
    xarMakePrivilegeMember('LockAnonymous','GeneralLock');
    xarMakePrivilegeMember('LockAdministrators','GeneralLock');
    xarMakePrivilegeMember('LockAdministration','GeneralLock');
    xarMakePrivilegeMember('LockGeneralLock','GeneralLock');

    /*********************************************************************
    * Assign the default privileges to groups/users
    * Format is
    * assign(Privilege,Role)
    *********************************************************************/

    xarAssignPrivilege('Administration','Administrators');
    xarAssignPrivilege('SiteManagement','SiteManagers');
    xarAssignPrivilege('GeneralLock','Everybody');
    xarAssignPrivilege('ReadAccess','Everybody');
    xarAssignPrivilege('GeneralLock','Administrators');
    xarAssignPrivilege('GeneralLock','Users');

    xarController::redirect(xarModURL('installer', 'admin', 'security',array('install_language' => $install_language)));
}


?>