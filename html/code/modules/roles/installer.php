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
use DataObjectFactory;
use Exception;
use xarConfigVars;
use xarDB;
use xarMod;
use xarModHooks;
use xarModUserVars;
use xarModVars;
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
    /**
     * Configure this module - override this method
     *
     * @todo use this instead of init() etc. for standard installation
     * @return void
     */
    public function configure()
    {
        $this->objects = [
            // add your DD objects here
            //'roles_object',
        ];
        $this->variables = [
            // add your module variables here
            'hello' => 'world',
        ];
        $this->oldversion = '2.4.1';
    }

    /** xarinit.php functions imported by bermuda_cleanup */

    /**
     * Initialise the roles module
     * @access public
     * @return bool
     */
    public function init()
    {
        // Create tables inside a transaction
        $dbconn = xarDB::getConn();
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
        xarConfigVars::set(null, 'Site.User.DebugAdmins', ['admin']);
        xarModVars::set('roles', 'defaultauthmodule', 'authsystem');
        xarModVars::set('roles', 'defaultregmodule', '');
        xarModVars::set('roles', 'rolesdisplay', 'tabbed');
        xarModVars::set('roles', 'locale', '');
        xarModVars::set('roles', 'duvsettings', '');
        xarModVars::set('roles', 'userhome', 'undefined');
        xarModVars::set('roles', 'userlastlogin', 0);
        xarModVars::set('roles', 'passwordupdate', 0);
        xarModVars::set('roles', 'usertimezone', xarConfigVars::get(null, 'Site.Core.TimeZone'));
        xarModVars::set('roles', 'useremailformat', 'text');
        xarModVars::set('roles', 'displayrolelist', false);
        xarModVars::set('roles', 'usereditaccount', true);
        xarModVars::set('roles', 'allowuserhomeedit', false);
        xarModVars::set('roles', 'loginredirect', true);
        xarModVars::set('roles', 'allowexternalurl', false);
        xarModVars::set('roles', 'searchbyemail', false);
        xarModVars::set('roles', 'allowemail', false);
        xarModVars::set('roles', 'requirevalidation', true);
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
        xarModHooks::register('item', 'search', 'GUI', 'roles', 'user', 'search');
        xarModHooks::register('item', 'usermenu', 'GUI', 'roles', 'user', 'usermenu');
        // Enter some default groups and users and put them in a hierarchy
        $rolefields = [
            'itemid' => 0,  // make this explicit, because we are going to reuse the roles we define
            'users' => 0,
            'regdate' => time(),
            'state' => xarRoles::ROLES_STATE_ACTIVE,
            'valcode' => 'createdbysystem',
            'authmodule' => (int) xarMod::getID('roles'),
        ];
        $group = DataObjectFactory::getObject(['name' => 'roles_groups']);
        $rolefields['role_type'] = xarRoles::ROLES_GROUPTYPE;
        xarModVars::set('roles', 'defaultgroup', 0);
        // The top level group Everybody
        $rolefields['name'] = 'Everybody';
        $rolefields['uname'] = 'everybody';
        $rolefields['parentid'] = 0;
        $topid = $group->createItem($rolefields);
        xarModVars::set('roles', 'everybody', $topid);
        xarModVars::set('roles', 'primaryparent', $topid);
        xarModUserVars::set('roles', 'userhome', '[base]', $topid);
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
        xarModVars::set('roles', 'lockdata', serialize($lockdata));
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
        xarModVars::set('roles', 'defaultgroup', $usergroup);
        $user = DataObjectFactory::getObject(['name' => 'roles_users']);
        $rolefields['role_type'] = xarRoles::ROLES_USERTYPE;
        // The Anonymous user
        $rolefields['name'] = 'Anonymous';
        $rolefields['uname'] = 'anonymous';
        $rolefields['parentid'] = $topid;
        $anonid = $user->createItem($rolefields);
        xarConfigVars::set(null, 'Site.User.AnonymousUID', $anonid);
        // The Administrator
        $rolefields['name'] = 'Administrator';
        $rolefields['uname'] = 'admin';
        $rolefields['email'] = 'none@none.com';
        $rolefields['parentid'] = $admingroup;
        $adminid = $user->createItem($rolefields);
        xarModVars::set('roles', 'admin', $adminid);
        // The SiteManager
        $rolefields['name'] = 'SiteManager';
        $rolefields['uname'] = 'manager';
        $rolefields['email'] = 'none@none.com';
        $rolefields['parentid'] = $mgrgroup;
        $mgrid = $user->createItem($rolefields);
        xarModVars::set('roles', 'manager', $mgrid);
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
