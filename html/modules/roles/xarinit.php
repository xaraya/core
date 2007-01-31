<?php
/**
 * Initialise the roles module
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 * @author Jan Schrage, John Cox, Gregor Rothfuss
 */

/**
 * Initialise the roles module
 *
 * @access public
 * @param none $
 * @returns bool
 * @throws DATABASE_ERROR
 */
function roles_init()
{
    // Get database setup
    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    $sitePrefix = xarDBGetSiteTablePrefix();
    $tables['roles'] = $sitePrefix . '_roles';
    $tables['rolemembers'] = $sitePrefix . '_rolemembers';

    // Create tables inside a transaction
    try {
        $dbconn->begin();
        /**
         * CREATE TABLE xar_roles (
         *    xar_uid int(11) NOT NULL auto_increment,
         *    xar_name varchar(100) NOT NULL default '',
         *    xar_type int(11) NOT NULL default '0',
         *    xar_users int(11) NOT NULL default '0',
         *    xar_uname varchar(100) NOT NULL default '',
         *    xar_email varchar(100) NOT NULL default '',
         *    xar_pass varchar(100) NOT NULL default '',
         *    xar_date_reg datetime NOT NULL default '0000-00-00 00:00:00',
         *    xar_valcode varchar(35) NOT NULL default '',
         *    xar_state int(3) NOT NULL default '0',
         *    xar_auth_modid int(11) NOT NULL default '0',
         *    PRIMARY KEY  (xar_uid)
         * )
         */

        $fields = array(
                        'xar_uid' => array('type' => 'integer','null' => false,'default' => '0','increment' => true, 'primary_key' => true),
                        'xar_name' => array('type' => 'varchar','size' => 255,'null' => false,'default' => ''),
                        'xar_type' => array('type' => 'integer', 'null' => false, 'default' => '0'),
                        'xar_users' => array('type' => 'integer', 'null' => false, 'default' => '0'),
                        'xar_uname' => array('type' => 'varchar', 'size' => 255, 'null' => false, 'default' => ''),
                        'xar_email' => array('type' => 'varchar', 'size' => 255,'null' => false,'default' => ''),
                        'xar_pass' => array('type' => 'varchar',  'size' => 100, 'null' => false, 'default' => ''),
                        'xar_date_reg' => array('type' => 'varchar', 'size' => 100, 'null' => false, 'default' => '0000-00-00 00:00:00'),
                        'xar_valcode' => array('type' => 'varchar', 'size' => 35, 'null' => false, 'default' => ''),
                        'xar_state' => array('type' => 'integer', 'null' => false,'default' => '3'),
                        'xar_auth_modid' => array('type' => 'integer', 'unsigneded' => true,'null' => false, 'default' => '0'));
        $query = xarDBCreateTable($tables['roles'],$fields);
        $dbconn->Execute($query);

        // role type is used in all group look-ups (e.g. security checks)
        $index = array('name' => 'i_' . $sitePrefix . '_roles_type',
                       'fields' => array('xar_type')
                       );
        $query = xarDBCreateIndex($tables['roles'], $index);
        $dbconn->Execute($query);

        // username must be unique (for login) + don't allow groupname to be the same either
        $index = array('name' => 'i_' . $sitePrefix . '_roles_uname',
                       'fields' => array('xar_uname'),
                       'unique' => true
                       );
        $query = xarDBCreateIndex($tables['roles'], $index);
        $dbconn->Execute($query);

        // allow identical "real names" here
        $index = array('name' => 'i_' . $sitePrefix . '_roles_name',
                       'fields' => array('xar_name'),
                       'unique' => false
                       );
        $query = xarDBCreateIndex($tables['roles'], $index);
        $dbconn->Execute($query);

        // allow identical e-mail here (???) + is empty for groups !
        $index = array('name' => 'i_' . $sitePrefix . '_roles_email',
                       'fields' => array('xar_email'),
                       'unique' => false
                       );
        $query = xarDBCreateIndex($tables['roles'], $index);
        $dbconn->Execute($query);

        // role state is used in many user lookups
        $index = array('name' => 'i_' . $sitePrefix . '_roles_state',
                       'fields' => array('xar_state'),
                       'unique' => false
                       );
        $query = xarDBCreateIndex($tables['roles'], $index);
        $dbconn->Execute($query);


        /**
         * CREATE TABLE xar_rolemembers (
         *    xar_uid int(11) NOT NULL default '0',
         *    xar_parentid int(11) NOT NULL default '0'
         * )
         */

        $query = xarDBCreateTable($tables['rolemembers'],
                                  array('xar_uid' => array('type'        => 'integer',
                                                           'null'        => false,
                                                           'default'     => '0',
                                                           'primary_key' => true),
                                        'xar_parentid' => array('type'        => 'integer',
                                                                'null'        => false,
                                                                'default'     => '0',
                                                                'primary_key' => true)));
        $dbconn->Execute($query);

        $index = array('name' => 'i_' . $sitePrefix . '_rolememb_uid',
                       'fields' => array('xar_uid'),
                       'unique' => false);
        $query = xarDBCreateIndex($tables['rolemembers'], $index);
        $dbconn->Execute($query);

        $index = array('name' => 'i_' . $sitePrefix . '_rolememb_parentid',
                       'fields' => array('xar_parentid'),
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

    // only go through this once
    // --------------------------------------------------------
    //
    // Create some modvars
    //
    //TODO: improve on this hardwiring
    xarModVars::set('roles', 'defaultauthmodule', xarModGetIDFromName('authsystem')); //Setting a default
    xarModVars::set('roles', 'defaultregmodule', '');
    if (xarModGetVar('roles','itemsperpage')) return true;
    xarModVars::set('roles', 'rolesdisplay', 'tabbed');
    xarModVars::set('roles', 'locale', '');
    xarModVars::set('roles', 'userhome', '');
    xarModVars::set('roles', 'userlastlogin', '');
    xarModVars::set('roles', 'primaryparent', 1);
    xarModVars::set('roles', 'usertimezone', xarConfigGetVar('Site.Core.TimeZone'));
    xarModVars::set('roles', 'setuserhome',false);
    xarModVars::set('roles', 'setprimaryparent', false);
    xarModVars::set('roles', 'setpasswordupdate',false);
    xarModVars::set('roles', 'setuserlastlogin',false);
    xarModVars::set('roles', 'setusertimezone',false);
    xarModVars::set('roles', 'defaultgroup', 'Users');
    xarModVars::set('roles', 'displayrolelist', false);
    xarModVars::set('roles', 'usereditaccount', true);
    xarModVars::set('roles', 'allowuserhomeedit', false);
    xarModVars::set('roles', 'loginredirect', true);
    xarModVars::set('roles', 'allowexternalurl', false);
    xarModVars::set('roles', 'usersendemails', false);
    xarModVars::set('roles', 'requirevalidation', true);
    $lockdata = array('roles' => array( array('uid' => 4,
                                              'name' => 'Administrators',
                                              'notify' => TRUE)),
                                  'message' => '',
                                  'locked' => 0,
                                  'notifymsg' => '');
    xarModVars::set('roles', 'lockdata', serialize($lockdata));

    xarModVars::set('roles', 'itemsperpage', 20);
    // save the uids of the default roles for later
    $role = xarFindRole('Everybody');
    xarModVars::set('roles', 'everybody', $role->getID());
    $role = xarFindRole('Anonymous');
    xarConfigSetVar('Site.User.AnonymousUID', $role->getID());
    // set the current session information to the right anonymous uid
    // TODO: make the setUserInfo a class static in xarSession.php
    xarSession_setUserInfo($role->getID(), 0);
    $role = xarFindRole('Admin');
    if (!isset($role)) {
      $role=xarUFindRole('Admin');
    }
    xarModVars::set('roles', 'admin', $role->getID());

    // --------------------------------------------------------
    //
    // Register block types
    //
    xarModAPIFunc('blocks', 'admin','register_block_type', array('modName' => 'roles','blockType' => 'online'));
    xarModAPIFunc('blocks', 'admin','register_block_type', array('modName' => 'roles','blockType' => 'user'));
    xarModAPIFunc('blocks', 'admin','register_block_type', array('modName' => 'roles','blockType' => 'language'));

    // Register hooks here, init is too soon
    xarModRegisterHook('item', 'search', 'GUI','roles', 'user', 'search');
    xarModRegisterHook('item', 'usermenu', 'GUI','roles', 'user', 'usermenu');

//    xarModAPIFunc('modules', 'admin', 'enablehooks', array('callerModName' => 'roles', 'hookModName' => 'roles'));
//    xarModAPIFunc('modules','admin','enablehooks',array('callerModName' => 'roles', 'hookModName' => 'dynamicdata'));

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
                xarModVars::set('authentication', 'allowregistration', xarModGetVar('roles', 'allowregistration'));
                xarModVars::set('authentication', 'requirevalidation', xarModGetVar('roles', 'requirevalidation'));
                xarModVars::set('authentication', 'itemsperpage', xarModGetVar('roles', 'rolesperpage'));
                xarModVars::set('authentication', 'uniqueemail', xarModGetVar('roles', 'uniqueemail'));
                xarModVars::set('authentication', 'askwelcomeemail', xarModGetVar('roles', 'askwelcomeemail'));
                xarModVars::set('authentication', 'askvalidationemail', xarModGetVar('roles', 'askvalidationemail'));
                xarModVars::set('authentication', 'askdeactivationemail', xarModGetVar('roles', 'askdeactivationemail'));
                xarModVars::set('authentication', 'askpendingemail', xarModGetVar('roles', 'askpendingemail'));
                xarModVars::set('authentication', 'askpasswordemail', xarModGetVar('roles', 'askpasswordemail'));
                xarModVars::set('authentication', 'defaultgroup', xarModGetVar('roles', 'defaultgroup'));
                xarModVars::set('authentication', 'lockouttime', 15);
                xarModVars::set('authentication', 'lockouttries', 3);
                xarModVars::set('authentication', 'minage', xarModGetVar('roles', 'minage'));
                xarModVars::set('authentication', 'disallowednames', xarModGetVar('roles', 'disallowednames'));
                xarModVars::set('authentication', 'disallowedemails', xarModGetVar('roles', 'disallowedemails'));
                xarModVars::set('authentication', 'disallowedips', xarModGetVar('roles', 'disallowedips'));

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
