<?php
/**
 * Initialise the roles module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @author Jan Schrage, John Cox, Gregor Rothfuss
 */

/**
 * Initialise the roles module
 *
 * @access public
 * @param none $
 * @returns bool
 * @raise DATABASE_ERROR
 */
function roles_init()
{
    // Get database setup
    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    $sitePrefix = xarDBGetSiteTablePrefix();
    $tables['roles'] = $sitePrefix . '_roles';
    $tables['rolemembers'] = $sitePrefix . '_rolemembers';
    // prefix_roles
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
     *    xar_auth_module varchar(100) NOT NULL default '',
     *    PRIMARY KEY  (xar_uid)
     * )
     */

    $query = xarDBCreateTable($tables['roles'],
        array('xar_uid' => array('type' => 'integer',
                'null' => false,
                'default' => '0',
                'increment' => true,
                'primary_key' => true),
            'xar_name' => array('type' => 'varchar',
                'size' => 255,
                'null' => false,
                'default' => ''),
            'xar_type' => array('type' => 'integer',
                'null' => false,
                'default' => '0'),
            'xar_users' => array('type' => 'integer',
                'null' => false,
                'default' => '0'),
            'xar_uname' => array('type' => 'varchar',
                'size' => 255,
                'null' => false,
                'default' => ''),
            'xar_email' => array('type' => 'varchar',
                'size' => 255,
                'null' => false,
                'default' => ''),
            'xar_pass' => array('type' => 'varchar',
                'size' => 100,
                'null' => false,
                'default' => ''),
            'xar_date_reg' => array('type' => 'varchar',
                'size' => 100,
                'null' => false,
                'default' => '0000-00-00 00:00:00'),
            'xar_valcode' => array('type' => 'varchar',
                'size' => 35,
                'null' => false,
                'default' => ''),
            'xar_state' => array('type' => 'integer',
                'null' => false,
                'default' => '3'),
            'xar_auth_module' => array('type' => 'varchar',
                'size' => 100,
                'null' => false,
                'default' => '')));

    if (!$dbconn->Execute($query)) return;

    // role type is used in all group look-ups (e.g. security checks)
    $index = array('name' => 'i_' . $sitePrefix . '_roles_type',
        'fields' => array('xar_type')
        );
    $query = xarDBCreateIndex($tables['roles'], $index);
    $result = &$dbconn->Execute($query);
    if (!$result) return;
    // username must be unique (for login) + don't allow groupname to be the same either
    $index = array('name' => 'i_' . $sitePrefix . '_roles_uname',
        'fields' => array('xar_uname'),
        'unique' => true
        );
    $query = xarDBCreateIndex($tables['roles'], $index);
    $result = &$dbconn->Execute($query);
    if (!$result) return;
    // allow identical "real names" here
    $index = array('name' => 'i_' . $sitePrefix . '_roles_name',
        'fields' => array('xar_name'),
        'unique' => false
        );
    $query = xarDBCreateIndex($tables['roles'], $index);
    $result = &$dbconn->Execute($query);
    if (!$result) return;
    // allow identical e-mail here (???) + is empty for groups !
    $index = array('name' => 'i_' . $sitePrefix . '_roles_email',
        'fields' => array('xar_email'),
        'unique' => false
        );
    $query = xarDBCreateIndex($tables['roles'], $index);
    $result = &$dbconn->Execute($query);
    if (!$result) return;
    // role state is used in many user lookups
    $index = array('name' => 'i_' . $sitePrefix . '_roles_state',
        'fields' => array('xar_state'),
        'unique' => false
        );
    $query = xarDBCreateIndex($tables['roles'], $index);
    $result = &$dbconn->Execute($query);
    if (!$result) return;

    // prefix_rolemembers
    /**
     * CREATE TABLE xar_rolemembers (
     *    xar_uid int(11) NOT NULL default '0',
     *    xar_parentid int(11) NOT NULL default '0'
     * )
     */

    $query = xarDBCreateTable($tables['rolemembers'],
        array('xar_uid' => array('type' => 'integer',
                'null' => false,
                'default' => '0'),
            'xar_parentid' => array('type' => 'integer',
                'null' => false,
                'default' => '0')));
    if (!$dbconn->Execute($query)) return;

    $index = array('name' => 'i_' . $sitePrefix . '_rolememb_id',
        'fields' => array('xar_uid','xar_parentid'),
        'unique' => true);
    $query = xarDBCreateIndex($tables['rolemembers'], $index);
    if (!$dbconn->Execute($query)) return;

    $index = array('name' => 'i_' . $sitePrefix . '_rolememb_uid',
        'fields' => array('xar_uid'),
        'unique' => false);
    $query = xarDBCreateIndex($tables['rolemembers'], $index);
    if (!$dbconn->Execute($query)) return;

    $index = array('name' => 'i_' . $sitePrefix . '_rolememb_parentid',
        'fields' => array('xar_parentid'),
        'unique' => false);
    $query = xarDBCreateIndex($tables['rolemembers'], $index);
    if (!$dbconn->Execute($query)) return;
    //Database Initialisation successful
    return true;
}

function roles_activate()
{
    // only go through this once
    if (xarModGetVar('roles','rolesperpage')) return true;

    // Set up an initial value for module variables.
    xarModSetVar('roles', 'rolesperpage', 20);
    xarModSetVar('roles', 'allowregistration', 1);
    xarModSetVar('roles', 'requirevalidation', 1);
    xarModSetVar('roles', 'defaultgroup', 'Users');
    //Send notifications values
    xarModSetVar('roles', 'askwelcomeemail', 1);
    xarModSetVar('roles', 'askvalidationemail', 1);
    xarModSetVar('roles', 'askdeactivationemail', 1);
    xarModSetVar('roles', 'askpendingemail', 1);
    xarModSetVar('roles', 'askpasswordemail', 1);
    xarModSetVar('roles', 'uniqueemail', 1);
    //Default Display
    xarModSetVar('roles', 'rolesdisplay', 'tabbed');
    //Default User Locale
    xarModSetVar('roles', 'locale', '');

    $lockdata = array('roles' => array( array('uid' => 4,
                                              'name' => 'Administrators',
                                              'notify' => TRUE)),
                                  'message' => '',
                                  'locked' => 0,
                                  'notifymsg' => '');
    xarModSetVar('roles', 'lockdata', serialize($lockdata));
    // Unfortunately, crappy format here, and not to PEAR Standardards
    // But I need the line break to come into play without the tab.

/*---------------------------------------------------------------
* Set disallowed names
*/
    $names = 'Admin
Root
Linux';
    $disallowednames = serialize($names);
    xarModSetVar('roles', 'disallowednames', $disallowednames);

    $emails = 'none@none.com
president@whitehouse.gov';
    $disallowedemails = serialize($emails);
    xarModSetVar('roles', 'disallowedemails', $disallowedemails);

/*---------------------------------------------------------------
* Set disallowed IPs
*/
    $ips = '';
    $disallowedips = serialize($ips);
    xarModSetVar('roles', 'disallowedips', $disallowedips);

    xarModSetVar('roles', 'minage', 13);
    // Register blocks
    if (!xarModAPIFunc('blocks',
            'admin',
            'register_block_type',
            array('modName' => 'roles',
                'blockType' => 'login'))) return;

    if (!xarModAPIFunc('blocks',
            'admin',
            'register_block_type',
            array('modName' => 'roles',
                'blockType' => 'online'))) return;

    if (!xarModAPIFunc('blocks',
            'admin',
            'register_block_type',
            array('modName' => 'roles',
                'blockType' => 'user'))) return;

    if (!xarModAPIFunc('blocks',
            'admin',
            'register_block_type',
            array('modName' => 'roles',
                'blockType' => 'language'))) return;
    // Register Hooks
    if (!xarModRegisterHook('item', 'search', 'GUI',
            'roles', 'user', 'search')) {
        return false;
    }
    if (!xarModRegisterHook('item', 'usermenu', 'GUI',
            'roles', 'user', 'usermenu')) {
        return false;
    }

    xarModAPIFunc('modules', 'admin', 'enablehooks',
        array('callerModName' => 'roles', 'hookModName' => 'roles'));
    xarModAPIFunc('modules','admin','enablehooks',
		array('callerModName' => 'roles', 'hookModName' => 'dynamicdata'));

    return true;
}

/**
 * Upgrade the roles module from an old version
 *
 * @access public
 * @param oldVersion $
 * @returns bool
 * @raise DATABASE_ERROR
 */
function roles_upgrade($oldVersion)
{
    // Upgrade dependent on old version number
    switch ($oldVersion) {
        case 1.01:
            break;
        case 2.0:
            // Code to upgrade from version 2.0 goes here
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
 * @raise DATABASE_ERROR
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

    $query = xarDBDropTable($tables['roles']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    $query = xarDBDropTable($tables['rolemembers']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;
    // Delete any module variables
    xarModDelVar('roles', 'rolesperpage');
    xarModDelVar('roles', 'disallowednames');
    xarModDelVar('roles', 'disallowedemails');

    /**
     * Remove instances and masks
     */
    // Remove Masks and Instances
    xarRemoveMasks('roles');
    xarRemoveInstances('roles');
    // Deletion successful
    return true;
}

?>
