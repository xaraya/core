<?php
/**
 * Initialise the roles module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 * @author Jan Schrage, John Cox, Gregor Rothfuss
 */

/**
 * Initialise the roles module
 *
 * @access public
 * @return bool
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
        $dbconn->begin();

        $fields = array(
                        'id' => array('type' => 'integer','null' => false,'default' => '0','increment' => true, 'primary_key' => true),
                        'name' => array('type' => 'varchar','size' => 255,'null' => false,'default' => ''),
                        'type' => array('type' => 'integer', 'null' => false, 'default' => '0'),
                        'users' => array('type' => 'integer', 'null' => false, 'default' => '0'),
                        'uname' => array('type' => 'varchar', 'size' => 255, 'null' => false, 'default' => ''),
                        'email' => array('type' => 'varchar', 'size' => 255,'null' => false,'default' => ''),
                        'pass' => array('type' => 'varchar',  'size' => 100, 'null' => false, 'default' => ''),
                        'date_reg' => array('type' => 'integer', 'null' => false, 'default' => '0'),
                        'valcode' => array('type' => 'varchar', 'size' => 35, 'null' => false, 'default' => ''),
                        'state' => array('type' => 'integer', 'null' => false,'default' => '3'),
                        'auth_modid' => array('type' => 'integer', 'unsigneded' => true,'null' => false, 'default' => '0'));
        $query = xarDBCreateTable($tables['roles'],$fields);
        $dbconn->Execute($query);

        // role type is used in all group look-ups (e.g. security checks)
        $index = array('name' => 'i_' . $prefix . '_roles_type',
                       'fields' => array('type')
                       );
        $query = xarDBCreateIndex($tables['roles'], $index);
        $dbconn->Execute($query);

        // username must be unique (for login) + don't allow groupname to be the same either
        $index = array('name' => 'i_' . $prefix . '_roles_uname',
                       'fields' => array('uname'),
                       'unique' => true
                       );
        $query = xarDBCreateIndex($tables['roles'], $index);
        $dbconn->Execute($query);

        // allow identical "real names" here
        $index = array('name' => 'i_' . $prefix . '_roles_name',
                       'fields' => array('name'),
                       'unique' => false
                       );
        $query = xarDBCreateIndex($tables['roles'], $index);
        $dbconn->Execute($query);

        // allow identical e-mail here (???) + is empty for groups !
        $index = array('name' => 'i_' . $prefix . '_roles_email',
                       'fields' => array('email'),
                       'unique' => false
                       );
        $query = xarDBCreateIndex($tables['roles'], $index);
        $dbconn->Execute($query);

        // role state is used in many user lookups
        $index = array('name' => 'i_' . $prefix . '_roles_state',
                       'fields' => array('state'),
                       'unique' => false
                       );
        $query = xarDBCreateIndex($tables['roles'], $index);
        $dbconn->Execute($query);

        $query = xarDBCreateTable($tables['rolemembers'],
                            array('id' => array('type'        => 'integer',
                                 'null'        => true,
                                                           'default'     => null),
                                        'parentid' => array('type'        => 'integer',
                                                                'null'        => true,
                                                                'default'     => null)));
        $dbconn->Execute($query);

        $index = array('name' => 'i_' . $prefix . '_rolememb_uid',
                       'fields' => array('id'),
                       'unique' => false);
        $query = xarDBCreateIndex($tables['rolemembers'], $index);
        $dbconn->Execute($query);

        $index = array('name' => 'i_' . $prefix . '_rolememb_parentid',
                       'fields' => array('parentid'),
                       'unique' => false);
        $query = xarDBCreateIndex($tables['rolemembers'], $index);
        $dbconn->Execute($query);

        // We're done, commit
        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }

    //Database Initialisation successful
    return true;
}

function roles_activate()
{
    //TODO: this stuff is happening here because at install blocks is not yet installed

    // --------------------------------------------------------
    //
    // Create some modvars
    //
    //TODO: improve on this hardwiring
    xarModVars::set('roles', 'defaultauthmodule', xarModGetIDFromName('authsystem')); //Setting a default
    xarModVars::set('roles', 'defaultregmodule', '');
    if (xarModVars::get('roles','itemsperpage')) return true;
    xarModVars::set('roles', 'rolesdisplay', 'tabbed');
    xarModVars::set('roles', 'locale', '');
    xarModVars::set('roles', 'duvsettings', serialize(array()));
    xarModVars::set('roles', 'userhome', 'undefined');
    xarModVars::set('roles', 'userlastlogin', '');
    xarModVars::set('roles', 'passwordupdate', '');
    xarModVars::set('roles', 'usertimezone', xarConfigGetVar('Site.Core.TimeZone'));
    xarModVars::set('roles', 'useremailformat', 'text');
    xarModVars::set('roles', 'displayrolelist', false);
    xarModVars::set('roles', 'usereditaccount', true);
    xarModVars::set('roles', 'allowuserhomeedit', false);
    xarModVars::set('roles', 'loginredirect', true);
    xarModVars::set('roles', 'allowexternalurl', false);
    xarModVars::set('roles', 'usersendemails', false);
    xarModVars::set('roles', 'requirevalidation', true);
    xarModVars::set('roles', 'itemsperpage', 20);

    /*
    // set the current session information to the right anonymous uid
    // TODO: make the setUserInfo a class static in xarSession.php
    xarSession_setUserInfo($role->getID(), 0);
    */

    // --------------------------------------------------------
    // Register block types
    xarModAPIFunc('blocks', 'admin','register_block_type', array('modName' => 'roles','blockType' => 'online'));
    xarModAPIFunc('blocks', 'admin','register_block_type', array('modName' => 'roles','blockType' => 'user'));
    xarModAPIFunc('blocks', 'admin','register_block_type', array('modName' => 'roles','blockType' => 'language'));

    // Register hooks here, init is too soon
    xarModRegisterHook('item', 'search', 'GUI','roles', 'user', 'search');
    xarModRegisterHook('item', 'usermenu', 'GUI','roles', 'user', 'usermenu');

//    xarModAPIFunc('modules', 'admin', 'enablehooks', array('callerModName' => 'roles', 'hookModName' => 'roles'));

    // --------------------------------------------------------
    //
    // Enter some default groups and users and put them in a hierarchy
    //
    $rolefields = array(
                    'itemid' => 0,  // make this explicit, because we are going to reuse the roles we define
                    'users' => 0,
                    'regdate' => time(),
                    'state' => ROLES_STATE_ACTIVE,
                    'valcode' => 'createdbysystem',
                    'authmodule' => xarMod::getID('roles'),
    );
    $group = DataObjectMaster::getObject(array('name' => 'roles_groups'));
    $rolefields['role_type'] = ROLES_GROUPTYPE;
    xarModVars::set('roles', 'defaultgroup', 0);

    // The top level group Everybody
    $rolefields['name'] = 'Everybody';
    $rolefields['uname'] = 'everybody';
    $rolefields['parentid'] = 0;
    $topid = $group->createItem($rolefields);
    xarRoles::isRoot('Everybody');
    xarModVars::set('roles', 'everybody', $topid);
    xarModVars::set('roles', 'primaryparent', $topid);
    xarModUserVars::set('roles', 'userhome', '[base]',$topid);

    // The Administrators group
    $rolefields['name'] = 'Administrators';
    $rolefields['uname'] = 'administrators';
    $rolefields['parentid'] = $topid;
    $admingroup = $group->createItem($rolefields);
    $lockdata = array('roles' => array( array('uid' => $admingroup,
                                              'name' => $rolefields['name'],
                                              'notify' => TRUE)),
                                              'message' => '',
                                              'locked' => 0,
                                              'notifymsg' => '');
    xarModVars::set('roles', 'lockdata', serialize($lockdata));

    // The Users group group
    $rolefields['name'] = 'Users';
    $rolefields['uname'] = 'users';
    $rolefields['parentid'] = $topid;
    $usergroup = $group->createItem($rolefields);
    xarModVars::set('roles', 'defaultgroup', $usergroup);

    $user = DataObjectMaster::getObject(array('name' => 'roles_users'));
    $rolefields['role_type'] = ROLES_USERTYPE;

        // The Anonymous user
    $rolefields['name'] = 'Anonymous';
    $rolefields['uname'] = 'anonymous';
    $rolefields['parentid'] = $topid;
    $id = $user->createItem($rolefields);
    xarConfigSetVar('Site.User.AnonymousUID', $topid);

    // The Administrator
    $rolefields['name'] = 'Administrator';
    $rolefields['uname'] = 'admin';
    $rolefields['parentid'] = $admingroup;
    $adminid = $user->createItem($rolefields);
    xarModVars::set('roles', 'admin', $adminid);

    // The Myself user
    $rolefields['name'] = 'Myself';
    $rolefields['uname'] = 'myself';
    $rolefields['parentid'] = $topid;
    $user->createItem($rolefields);

    return true;
}

/**
 * Upgrade the roles module from an old version
 *
 * @access public
 * @param oldVersion $
 * @returns bool
 * @throws DATABASE_ERROR
 */
function roles_upgrade($oldVersion)
{
    // Upgrade dependent on old version number
    switch ($oldVersion) {
        case '1.01':
            break;
        case '1.1.1':
            // is there an authentication module?
            $regid = xarModGetIDFromName('authentication');

            if (isset($regid)) {
                // remove the login block type and block from roles
                $result = xarModAPIfunc('blocks', 'admin', 'delete_type', array('module' => 'roles', 'type' => 'login'));

                // install the authentication module
                if (!xarModAPIFunc('modules', 'admin', 'initialise', array('regid' => $regid))) return;
                    // Activate the module
                if (!xarModAPIFunc('modules', 'admin', 'activate', array('regid' => $regid))) return;

                // create the new authentication modvars
                // TODO: dont do this here, but i dont know how to do it otherwise, since apparently the
                //       roles values are needed
                xarModVars::set('authentication', 'allowregistration', xarModVars::get('roles', 'allowregistration'));
                xarModVars::set('authentication', 'requirevalidation', xarModVars::get('roles', 'requirevalidation'));
                xarModVars::set('authentication', 'itemsperpage', xarModVars::get('roles', 'rolesperpage'));
                xarModVars::set('authentication', 'uniqueemail', xarModVars::get('roles', 'uniqueemail'));
                xarModVars::set('authentication', 'askwelcomeemail', xarModVars::get('roles', 'askwelcomeemail'));
                xarModVars::set('authentication', 'askvalidationemail', xarModVars::get('roles', 'askvalidationemail'));
                xarModVars::set('authentication', 'askdeactivationemail', xarModVars::get('roles', 'askdeactivationemail'));
                xarModVars::set('authentication', 'askpendingemail', xarModVars::get('roles', 'askpendingemail'));
                xarModVars::set('authentication', 'askpasswordemail', xarModVars::get('roles', 'askpasswordemail'));
                xarModVars::set('authentication', 'defaultgroup', xarModVars::get('roles', 'defaultgroup'));
                xarModVars::set('authentication', 'lockouttime', 15);
                xarModVars::set('authentication', 'lockouttries', 3);
                xarModVars::set('authentication', 'minage', xarModVars::get('roles', 'minage'));
                xarModVars::set('authentication', 'disallowednames', xarModVars::get('roles', 'disallowednames'));
                xarModVars::set('authentication', 'disallowedemails', xarModVars::get('roles', 'disallowedemails'));
                xarModVars::set('authentication', 'disallowedips', xarModVars::get('roles', 'disallowedips'));

                // delete the old roles modvars
                xarModDelVar('roles', 'allowregistration');
                xarModDelVar('roles', 'requirevalidation');
                xarModDelVar('roles', 'rolesperpage');
                xarModDelVar('roles', 'uniqueemail');
                xarModDelVar('roles', 'askwelcomeemail');
                xarModDelVar('roles', 'askvalidationemail');
                xarModDelVar('roles', 'askdeactivationemail');
                xarModDelVar('roles', 'askpendingemail');
                xarModDelVar('roles', 'askpasswordemail');
                xarModDelVar('roles', 'defaultgroup');
                xarModDelVar('roles', 'lockouttime');
                xarModDelVar('roles', 'lockouttries');
                xarModDelVar('roles', 'minage');
                xarModDelVar('roles', 'disallowednames');
                xarModDelVar('roles', 'disallowedemails');
                xarModDelVar('roles', 'disallowedips');

                // create one new roles modvar
                xarModVars::set('roles', 'defaultauthmodule', xarModGetIDFromName('authentication'));
            } else {
                throw new Exception('I could not load the authentication module. Please make it available and try again');
            }
            break;
        case '1.1.1':
            $roles_objects = array('role','user','group');
            $existing_objects  = xarModApiFunc('dynamicdata','user','getobjects');
            foreach($existing_objects as $objectid => $objectinfo) {
                if(in_array($objectinfo['name'], $roles_objects)) {
                    // KILL
                    if(!xarModApiFunc('dynamicdata','admin','deleteobject', array('objectid' => $objectid))) return;
                }
            }
            if (!xarModAPIFunc('roles','admin','createobjects')) return;
            break;

    }
    // Update successful
    return true;
}

/**
 * Delete the roles module
 *
 * @access public
 * @param none $
 * @returns bool
 * @throws DATABASE_ERROR
 */
function roles_delete()
{
    // this module cannot be removed
    return false;

    /**
     * Drop the tables
     */
    // Get database information
    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    try {
        $dbconn->begin();
        // drop roles table
        $query = xarDBDropTable($tables['roles']);
        $dbconn->Execute($query);

        // drop role_members table
        $query = xarDBDropTable($tables['rolemembers']);
        $dbconn->Execute($query);

        /**
         * Remove modvars, instances and masks
         */
        xarModDelAllVars('roles');
        xarRemoveMasks('roles');
        xarRemoveInstances('roles');

        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }

    // Deletion successful
    return true;
}
?>
