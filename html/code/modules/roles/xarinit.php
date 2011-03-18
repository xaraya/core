<?php
/**
 * Initialise the roles module
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 *
 * @author Jan Schrage
 * @author Gregor Rothfuss
 * @author John Cox
 */

/**
 * Initialise the roles module
 *
 * @access public
 * @return boolean
 * @throws DATABASE_ERROR
 */
function roles_init()
{
    $dbconn =& xarDB::getConn();
    $tables =& xarDB::getTables();

    $prefix = xarDB::getPrefix();
    $tables['roles'] = $prefix . '_roles';
    $tables['rolemembers'] = $prefix . '_rolemembers';

    // Create tables inside a transaction
    try {
        $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
        $dbconn->begin();

        $fields = array(
                        'id' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'increment' => true, 'primary_key' => true),
                        'name' => array('type' => 'varchar','size' => 254,'null' => false, 'charset' => $charset),
                        'itemtype' => array('type' => 'integer', 'unsigned' => true, 'null' => false),
                        'users' => array('type' => 'integer', 'null' => false, 'default' => '0'),
                        'uname' => array('type' => 'varchar', 'size' => 254, 'null' => false, 'charset' => $charset),
                        'email' => array('type' => 'varchar', 'size' => 254,'null' => true, 'charset' => $charset),
                        'pass' => array('type' => 'varchar',  'size' => 254, 'null' => true, 'charset' => $charset),
                        'date_reg' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'default' => '0'),
                        'valcode' => array('type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset),
                        'state' => array('type' => 'integer', 'unsigned' => true, 'size' => 'tiny', 'null' => false,'default' => '3'),
                        'auth_module_id' => array('type' => 'integer', 'unsigned' => true, 'unsigned' => true, 'null' => false));
        $query = xarDBCreateTable($tables['roles'],$fields);
        $dbconn->Execute($query);

        // role type is used in all group look-ups (e.g. security checks)
        $index = array('name' => $prefix . '_roles_itemtype',
                       'fields' => array('itemtype')
                       );
        $query = xarDBCreateIndex($tables['roles'], $index);
        $dbconn->Execute($query);

        // username must be unique (for login) + don't allow groupname to be the same either
        $index = array('name' => $prefix . '_roles_uname',
                       'fields' => array('uname'),
                       'unique' => true
                       );
        $query = xarDBCreateIndex($tables['roles'], $index);
        $dbconn->Execute($query);

        // allow identical "real names" here
        $index = array('name' => $prefix . '_roles_name',
                       'fields' => array('name'),
                       'unique' => false
                       );
        $query = xarDBCreateIndex($tables['roles'], $index);
        $dbconn->Execute($query);

        // allow identical e-mail here (???) + is empty for groups !
        $index = array('name' => $prefix . '_roles_email',
                       'fields' => array('email'),
                       'unique' => false
                       );
        $query = xarDBCreateIndex($tables['roles'], $index);
        $dbconn->Execute($query);

        // role state is used in many user lookups
        $index = array('name' => $prefix . '_roles_state',
                       'fields' => array('state'),
                       'unique' => false
                       );
        $query = xarDBCreateIndex($tables['roles'], $index);
        $dbconn->Execute($query);

        $query = xarDBCreateTable($tables['rolemembers'],
                            array('role_id' => array('type'        => 'integer',
                                                'unsigned'     => true,
                                                'null'        => true,
                                                'primary_key' => true),
                                        'parent_id' => array('type'        => 'integer',
                                                            'unsigned'     => true,
                                                            'null'        => true,
                                                            'primary_key' => true)));
        $dbconn->Execute($query);

        $index = array('name' => $prefix . '_rolememb_id',
                       'fields' => array('role_id'),
                       'unique' => false);
        $query = xarDBCreateIndex($tables['rolemembers'], $index);
        $dbconn->Execute($query);

        $index = array('name' => $prefix . '_rolememb_parentid',
                       'fields' => array('parent_id'),
                       'unique' => false);
        $query = xarDBCreateIndex($tables['rolemembers'], $index);
        $dbconn->Execute($query);

        // We're done, commit
        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }

    // --------------------------------------------------------
    //
    // Create some modvars
    //
    xarConfigVars::set(null, 'Site.User.DebugAdmins', array('admin'));

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
    xarModVars::set('roles', 'allowemail', false);
    xarModVars::set('roles', 'requirevalidation', true);
    
    //Database Initialisation successful
    return true;
}

function roles_activate()
{
    // --------------------------------------------------------
    // Register block types
    xarMod::apiFunc('blocks', 'admin','register_block_type', array('modName' => 'roles','blockType' => 'online'));
    xarMod::apiFunc('blocks', 'admin','register_block_type', array('modName' => 'roles','blockType' => 'user'));
    xarMod::apiFunc('blocks', 'admin','register_block_type', array('modName' => 'roles','blockType' => 'language'));

    // Register hooks here, init is too soon
    xarModRegisterHook('item', 'search', 'GUI','roles', 'user', 'search');
    xarModRegisterHook('item', 'usermenu', 'GUI','roles', 'user', 'usermenu');

    // --------------------------------------------------------
    //
    // Enter some default groups and users and put them in a hierarchy
    //
    $rolefields = array(
                    'itemid' => 0,  // make this explicit, because we are going to reuse the roles we define
                    'users' => 0,
                    'regdate' => time(),
                    'state' => xarRoles::ROLES_STATE_ACTIVE,
                    'valcode' => 'createdbysystem',
                    'authmodule' => (int)xarMod::getID('roles'),
    );
    $group = DataObjectMaster::getObject(array('name' => 'roles_groups'));
    $rolefields['role_type'] = xarRoles::ROLES_GROUPTYPE;
    xarModVars::set('roles', 'defaultgroup', 0);

    // The top level group Everybody
    $rolefields['name'] = 'Everybody';
    $rolefields['uname'] = 'everybody';
    $rolefields['parentid'] = 0;
    $topid = $group->createItem($rolefields);
    xarModVars::set('roles', 'everybody', $topid);
    xarModVars::set('roles', 'primaryparent', $topid);
    xarModUserVars::set('roles', 'userhome', '[base]',$topid);

    // The Administrators group
    $rolefields['name'] = 'Administrators';
    $rolefields['uname'] = 'administrators';
    $rolefields['parentid'] = $topid;
    $admingroup = $group->createItem($rolefields);
    $lockdata = array('roles' => array( array('id' => $admingroup,
                                              'name' => $rolefields['name'],
                                              'notify' => TRUE)),
                                              'message' => '',
                                              'locked' => 0,
                                              'notifymsg' => '');
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

    $user = DataObjectMaster::getObject(array('name' => 'roles_users'));
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

    // Installation complete; check for upgrades
    return roles_upgrade('2.0.0');
}

/**
 * Upgrade this module from an old version
 *
 * @param oldVersion
 * @return boolean true on success, false on failure
 */
function roles_upgrade($oldversion)
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
 *
 * @return boolean
 */
function roles_delete()
{
  //this module cannot be removed
  return false;
}
?>