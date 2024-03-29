<?php
/**
 * Installer
 *
 * @package modules\installer\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/200.html
 */

/* Do not allow this script to run if the install script has been removed.
 * This assumes the install.php and index.php are in the same directory.
 * @author Paul Rosania
 * @author Marcel van der Boom <marcel@hsdev.com>
 */

/**
 * Create default administrator
 *
 * @access public
 * @param string language
 * @return boolean
 * @return mixed boolean after redirect or output display string if invalid data submitted
 * @todo make confirm password work
 * @todo remove URL field from users table
 * @todo normalize user's table
 */
function installer_admin_create_administrator()
{
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVar::fetch('install_language','str::',$install_language, 'en_US.utf-8', xarVar::NOT_REQUIRED);

    xarVar::setCached('installer','installing', true);
    xarTpl::setThemeName('installer');

    $data = [];
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
    $data['admin']->properties['password']->validation_password_confirm = 0;
    $data['admin']->properties['email']->validation_min_length = 1;
    $data['admin']->properties['email']->validation_min_length_invalid = xarML('An email address must be entered');
    $data['admin']->properties['role_type']->display_combo_mode = 2;

    $data['properties'] = $data['admin']->getProperties();

    if (!xarVar::fetch('create', 'isset', $create, FALSE, xarVar::NOT_REQUIRED)) return;
    // Not creating yet. Just (re)display the page
    if (!$create) {
        return $data;
    }

    // We will save the page. Get the data from the template and check it.
    $isvalid = $data['admin']->checkInput();
    if (!$isvalid) {
    	// Reset the password property
    	$data['properties']['password']->value = '';
    	// Something's not right. Redisplay the page
        return xarTpl::module('installer','admin','create_administrator',$data);
    }

    // Good to go. Save the data
    xarModVars::set('mail',   'adminname',     $data['admin']->properties['name']->getValue());
    xarModVars::set('mail',   'adminmail',     $data['admin']->properties['email']->getValue());
    xarModVars::set('themes', 'SiteCopyRight', '&copy; Copyright ' . date("Y") . ' ' . $data['admin']->properties['name']->getValue());
    xarModVars::set('roles',  'lastuser',      $data['admin']->properties['uname']->getValue());
    xarModVars::set('roles',  'adminpass',     $data['admin']->properties['password']->password);

    //Try to update the role to the repository and bail if an error was thrown
    $itemid = $data['admin']->updateItem();
    if (!$itemid) {return;}
    
// CHECKME: misc. undefined module variables
    xarModVars::set('themes', 'variable_dump', false);
    xarModVars::set('base',   'releasenumber', 10);
    xarModVars::set('base',   'AlternatePageTemplateName', '');
    xarModVars::set('base',   'UseAlternatePageTemplate', false);
    xarModVars::set('base',   'editor', 'none');
    xarModVars::set('base',   'proxyhost', '');
    xarModVars::set('base',   'proxyport', 0);

    /*********************************************************************
    * Enter some default privileges
    * Format is
    * register(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

    xarPrivileges::register('Administration','All','All','All','All','ACCESS_ADMIN',xarML('Admin access to all modules'));
    xarPrivileges::register('SiteManagement','All','All','All','All','ACCESS_DELETE',xarML('Site Manager access to all modules'));
    xarPrivileges::register('GeneralLock','All',null,'All','All','ACCESS_NONE',xarML('A container privilege for denying access to certain roles'));
    xarPrivileges::register('LockEverybody','All','roles','Roles','Everybody','ACCESS_NONE',xarML('Deny access to Everybody role'));
    xarPrivileges::register('LockAnonymous','All','roles','Roles','Anonymous','ACCESS_NONE',xarML('Deny access to Anonymous role'));
    xarPrivileges::register('LockAdministrators','All','roles','Roles','Administrators','ACCESS_NONE',xarML('Deny access to Administrators role'));
    xarPrivileges::register('LockAdministration','All','privileges','Privileges','Administration','ACCESS_NONE',xarML('Deny access to Administration privilege'));
    xarPrivileges::register('LockGeneralLock','All','privileges','Privileges','GeneralLock','ACCESS_NONE',xarML('Deny access to GeneralLock privilege'));
    xarPrivileges::register('ReadAccess','All','All','All','All','ACCESS_READ','Read access to all modules');

    /*********************************************************************
    * Arrange the  privileges in a hierarchy
    * Format is
    * xarPrivileges::makeMember(Child,Parent)
    *********************************************************************/

    xarPrivileges::makeMember('LockEverybody','GeneralLock');
    xarPrivileges::makeMember('LockAnonymous','GeneralLock');
    xarPrivileges::makeMember('LockAdministrators','GeneralLock');
    xarPrivileges::makeMember('LockAdministration','GeneralLock');
    xarPrivileges::makeMember('LockGeneralLock','GeneralLock');

    /*********************************************************************
    * Assign the default privileges to groups/users
    * Format is
    * assign(Privilege,Role)
    *********************************************************************/

    xarPrivileges::assign('Administration','Administrators');
    xarPrivileges::assign('SiteManagement','SiteManagers');
    xarPrivileges::assign('GeneralLock','Everybody');
    xarPrivileges::assign('ReadAccess','Everybody');
    xarPrivileges::assign('GeneralLock','Administrators');
    xarPrivileges::assign('GeneralLock','Users');

    xarController::redirect(xarController::URL('installer', 'admin', 'security',array('install_language' => $install_language)));
    return true;
}
