<?php

/**
 * @package modules\installer
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Installer\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Installer\AdminGui;
use xarPrivileges;
use xarRoles;
use Exception;

/**
 * installer admin create_administrator function
 * @extends MethodClass<AdminGui>
 */
class CreateAdministratorMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Create default administrator
     * @access public
     * @param string language
     * @return bool
     * @return mixed boolean after redirect or output display string if invalid data submitted
     * @todo make confirm password work
     * @todo remove URL field from users table
     * @todo normalize user's table
     * @see AdminGui::createAdministrator()
     */
    public function __invoke(array $args = [])
    {
        if (!file_exists('install.php')) {
            throw new Exception('Already installed');
        }
        $this->var()->find('install_language', $install_language, 'str::', 'en_US.utf-8');

        $this->mem()->set('installer', 'installing', true);
        $this->tpl()->setThemeName('installer');

        $data = [];
        $data['language'] = $install_language;
        $data['phase'] = 6;
        $data['phase_label'] = $this->ml('Create Administrator');

        $userId = (int) $this->mod('roles')->getVar('admin');
        $data['admin'] = $this->user()->getRole('id', $userId);

        // Set up some custom validation checks and messages
        $data['admin']->properties['name']->display_layout = 'single';
        $data['admin']->properties['name']->validation_min_length = 4;
        $data['admin']->properties['name']->validation_min_length_invalid = $this->ml('The display name must be at least 4 characters long');
        $data['admin']->properties['uname']->validation_min_length = 4;
        $data['admin']->properties['uname']->validation_min_length_invalid = $this->ml('The user name must be at least 4 characters long');
        $data['admin']->properties['password']->validation_min_length = 4;
        $data['admin']->properties['password']->validation_min_length_invalid = $this->ml('The password must be at least 4 characters long');
        $data['admin']->properties['password']->validation_password_confirm = 0;
        $data['admin']->properties['email']->validation_min_length = 1;
        $data['admin']->properties['email']->validation_min_length_invalid = $this->ml('An email address must be entered');
        $data['admin']->properties['role_type']->display_combo_mode = 2;

        $data['properties'] = $data['admin']->getProperties();

        $this->var()->find('create', $create, 'isset', false);
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
            return $data;
        }

        // Good to go. Save the data
        $this->mod('mail')->setVar('adminname', $data['admin']->properties['name']->getValue());
        $this->mod('mail')->setVar('adminmail', $data['admin']->properties['email']->getValue());
        $this->mod('themes')->setVar('SiteCopyRight', '&copy; Copyright ' . date("Y") . ' ' . $data['admin']->properties['name']->getValue());
        $this->mod('roles')->setVar('lastuser', $data['admin']->properties['uname']->getValue());
        $this->mod('roles')->setVar('adminpass', $data['admin']->properties['password']->password);

        //Try to update the role to the repository and bail if an error was thrown
        $itemid = $data['admin']->updateItem();
        if (!$itemid) {
            return;
        }

        // CHECKME: misc. undefined module variables
        $this->mod('themes')->setVar('variable_dump', false);
        $this->mod('base')->setVar('releasenumber', 10);
        $this->mod('base')->setVar('AlternatePageTemplateName', '');
        $this->mod('base')->setVar('UseAlternatePageTemplate', false);
        $this->mod('base')->setVar('editor', 'none');
        $this->mod('base')->setVar('proxyhost', '');
        $this->mod('base')->setVar('proxyport', 0);

        /*********************************************************************
        * Enter some default privileges
        * Format is
        * register(Name,Realm,Module,Component,Instance,Level,Description)
        *********************************************************************/

        xarPrivileges::register('Administration', 'All', 'All', 'All', 'All', 'ACCESS_ADMIN', $this->ml('Admin access to all modules'));
        xarPrivileges::register('SiteManagement', 'All', 'All', 'All', 'All', 'ACCESS_DELETE', $this->ml('Site Manager access to all modules'));
        xarPrivileges::register('GeneralLock', 'All', null, 'All', 'All', 'ACCESS_NONE', $this->ml('A container privilege for denying access to certain roles'));
        xarPrivileges::register('LockEverybody', 'All', 'roles', 'Roles', 'Everybody', 'ACCESS_NONE', $this->ml('Deny access to Everybody role'));
        xarPrivileges::register('LockAnonymous', 'All', 'roles', 'Roles', 'Anonymous', 'ACCESS_NONE', $this->ml('Deny access to Anonymous role'));
        xarPrivileges::register('LockAdministrators', 'All', 'roles', 'Roles', 'Administrators', 'ACCESS_NONE', $this->ml('Deny access to Administrators role'));
        xarPrivileges::register('LockAdministration', 'All', 'privileges', 'Privileges', 'Administration', 'ACCESS_NONE', $this->ml('Deny access to Administration privilege'));
        xarPrivileges::register('LockGeneralLock', 'All', 'privileges', 'Privileges', 'GeneralLock', 'ACCESS_NONE', $this->ml('Deny access to GeneralLock privilege'));
        xarPrivileges::register('ReadAccess', 'All', 'All', 'All', 'All', 'ACCESS_READ', 'Read access to all modules');

        /*********************************************************************
        * Arrange the  privileges in a hierarchy
        * Format is
        * xarPrivileges::makeMember(Child,Parent)
        *********************************************************************/

        xarPrivileges::makeMember('LockEverybody', 'GeneralLock');
        xarPrivileges::makeMember('LockAnonymous', 'GeneralLock');
        xarPrivileges::makeMember('LockAdministrators', 'GeneralLock');
        xarPrivileges::makeMember('LockAdministration', 'GeneralLock');
        xarPrivileges::makeMember('LockGeneralLock', 'GeneralLock');

        /*********************************************************************
        * Assign the default privileges to groups/users
        * Format is
        * assign(Privilege,Role)
        *********************************************************************/

        xarPrivileges::assign('Administration', 'Administrators');
        xarPrivileges::assign('SiteManagement', 'SiteManagers');
        xarPrivileges::assign('GeneralLock', 'Everybody');
        xarPrivileges::assign('ReadAccess', 'Everybody');
        xarPrivileges::assign('GeneralLock', 'Administrators');
        xarPrivileges::assign('GeneralLock', 'Users');

        $this->ctl()->redirect($this->ctl()->getModuleURL('installer', 'admin', 'security', ['install_language' => $install_language]));
        return true;
    }
}
