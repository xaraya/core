<?php

/**
 * Handle module installer functions
 *
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles;

use Xaraya\Modules\InstallerClass;
use Exception;
use xarModHooks;
use xarRoles;
use xarXMLInstaller;
use sys;

sys::import('xaraya.modules.installer');

/**
 * Handle module installer functions
 *
 * @internal replaced roles_*() function calls with $this->*() calls
 * @extends InstallerClass<Module>
 */
class Installer extends InstallerClass
{
    /** xarinit.php functions imported by bermuda_cleanup */

    /**
     * Initialise the roles module
     * @access public
     * @return bool
     */
    public function init()
    {
        // Create tables inside a transaction
        $dbconn = $this->db()->getConn();
        try {
            $dbconn->begin();
            sys::import('xaraya.tableddl');
            xarXMLInstaller::createTable('table_schema-def', 'roles');
            // We're done, commit
            $dbconn->commit();
        } catch (Exception $e) {
            $dbconn->rollback();
            throw $e;
        }
        // Create some modvars
        $this->config()->setVar('Site.User.DebugAdmins', ['admin']);
        $this->mod()->setVar('defaultauthmodule', 'authsystem');
        $this->mod()->setVar('defaultregmodule', '');
        $this->mod()->setVar('rolesdisplay', 'tabbed');
        $this->mod()->setVar('locale', '');
        $this->mod()->setVar('duvsettings', '');
        $this->mod()->setVar('userhome', 'undefined');
        $this->mod()->setVar('userlastlogin', 0);
        $this->mod()->setVar('passwordupdate', 0);
        $this->mod()->setVar('usertimezone', $this->config()->getVar('Site.Core.TimeZone'));
        $this->mod()->setVar('useremailformat', 'text');
        $this->mod()->setVar('displayrolelist', false);
        $this->mod()->setVar('usereditaccount', true);
        $this->mod()->setVar('allowuserhomeedit', false);
        $this->mod()->setVar('loginredirect', true);
        $this->mod()->setVar('allowexternalurl', false);
        $this->mod()->setVar('searchbyemail', false);
        $this->mod()->setVar('allowemail', false);
        $this->mod()->setVar('requirevalidation', true);
        //Database Initialisation successful
        return true;
    }

    public function activate()
    {
        try {
            $this->create_roles();
        } catch (Exception $e) {
            // already there
        }
        return true;
    }

    public function create_roles()
    {
        // Register hooks here, init is too soon
        // @todo re-evaluate these hooks - not called anywhere
        $this->hooked()->registerObserver('ItemSearch', 'roles', 'GUI', 'user', 'search');
        $this->hooked()->registerObserver('ItemUsermenu', 'roles', 'GUI', 'user', 'usermenu');
        // Enter some default groups and users and put them in a hierarchy
        $rolefields = [
            'itemid' => 0,  // make this explicit, because we are going to reuse the roles we define
            'users' => 0,
            'regdate' => time(),
            'state' => xarRoles::ROLES_STATE_ACTIVE,
            'valcode' => 'createdbysystem',
            'authmodule' => (int) $this->mod()->getID('roles'),
        ];
        $group = $this->data()->getObject(['name' => 'roles_groups']);
        $rolefields['role_type'] = xarRoles::ROLES_GROUPTYPE;
        $this->mod()->setVar('defaultgroup', 0);
        // The top level group Everybody
        $rolefields['name'] = 'Everybody';
        $rolefields['uname'] = 'everybody';
        $rolefields['parentid'] = 0;
        $topid = $group->createItem($rolefields);
        $this->mod()->setVar('everybody', $topid);
        $this->mod()->setVar('primaryparent', $topid);
        $this->mod()->setUserVar('userhome', '[base]', $topid);
        // The Administrators group
        $rolefields['name'] = 'Administrators';
        $rolefields['uname'] = 'administrators';
        $rolefields['parentid'] = $topid;
        $admingroup = $group->createItem($rolefields);
        $lockdata = ['roles' => [ ['id' => $admingroup,
            'name' => $rolefields['name'],
            'notify' => true]],
            'message' => '',
            'locked' => 0,
            'notifymsg' => ''];
        $this->mod()->setVar('lockdata', serialize($lockdata));
        // The SiteManagers group
        $rolefields['name'] = 'SiteManagers';
        $rolefields['uname'] = 'sitemanagers';
        $rolefields['parentid'] = $topid;
        $mgrgroup = $group->createItem($rolefields);
        // The Users group
        $rolefields['name'] = 'Users';
        $rolefields['uname'] = 'users';
        $rolefields['parentid'] = $topid;
        $usergroup = $group->createItem($rolefields);
        $this->mod()->setVar('defaultgroup', $usergroup);
        $user = $this->data()->getObject(['name' => 'roles_users']);
        $rolefields['role_type'] = xarRoles::ROLES_USERTYPE;
        // The Anonymous user
        $rolefields['name'] = 'Anonymous';
        $rolefields['uname'] = 'anonymous';
        $rolefields['parentid'] = $topid;
        $anonid = $user->createItem($rolefields);
        $this->config()->setVar('Site.User.AnonymousUID', $anonid);
        // The Administrator
        $rolefields['name'] = 'Administrator';
        $rolefields['uname'] = 'admin';
        $rolefields['email'] = 'none@none.com';
        $rolefields['parentid'] = $admingroup;
        $adminid = $user->createItem($rolefields);
        $this->mod()->setVar('admin', $adminid);
        // The SiteManager
        $rolefields['name'] = 'SiteManager';
        $rolefields['uname'] = 'manager';
        $rolefields['email'] = 'none@none.com';
        $rolefields['parentid'] = $mgrgroup;
        $mgrid = $user->createItem($rolefields);
        $this->mod()->setVar('manager', $mgrid);
        // Installation complete; check for upgrades
        return $this->upgrade('2.0.0');
    }

    /**
     * Upgrade this module from an old version
     * @param string $oldversion
     * @return bool true on success, false on failure
     */
    public function upgrade($oldversion)
    {
        // Upgrade dependent on old version number
        switch ($oldversion) {
            default:
                break;
        }
        return true;
    }

    /**
     * Delete this module
     * @return bool
     */
    public function delete()
    {
        //this module cannot be removed
        return false;
    }
}
