<?php
/**
 * File: $Id: s.xarinit.php 1.27 03/01/17 15:18:04-08:00 rcave@lxwdev-1.schwabfoundation.org $
 *
 * Short description of purpose of file
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Jan Schrage, John Cox, Gregor Rothfuss
 * @todo need the dynamic users menu
 * @todo needs dyanamic data interface
 * @todo ensure all mod vars are set
 */

/**
 * Initialise the users module
 *
 * @access public
 * @param none $
 * @returns bool
 * @raise DATABASE_ERROR
 */
function roles_init()
{
    // Get database setup
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

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
    // Initialisation successful
    return true;
}

function roles_activate()
{
    // Set up an initial value for module variables.
    xarModSetVar('roles', 'welcomeemail', 'Your account is now active.  Thank you, and welcome to our community.');
    xarModSetVar('roles', 'rolesperpage', 20);
    xarModSetVar('roles', 'allowregistration', 1);
    xarModSetVar('roles', 'requirevalidation', 1);
    xarModSetVar('roles', 'defaultgroup', 'Users');
    xarModSetVar('roles', 'confirmationtitle', 'Confirmation Email for %%username%%');
    xarModSetVar('roles', 'welcometitle', 'Welcome to %%sitename%%');
    $lockdata = array('roles' => array( array('uid' => 4,
                                              'name' => 'Administrators',
                                              'notify' => TRUE)
                                       ),
                      'message' => '',
                      'locked' => 0,
                      'notifymsg' => '');
    xarModSetVar('roles', 'lockdata', serialize($lockdata));
    // Unfortunately, crappy format here, and not to PEAR Standardards
    // But I need the line break to come into play without the tab.
    $confirmationemail = 'Your account has been created for %%sitename%% and needs to be activated.  You can either do this now, or on the first time that you log in.  If you perfer to do it now, then you will need to follow this link:

%%link%%

Here are the details that were provided.

IP Address of the person creating that account: %%ipaddr%%
User Name:  %%username%%
Password:  %%password%%

Validation Code to activate your account:  %%valcode%%

If you did not create this account, then do nothing.  The account will be deemed inactive after a period of time and deleted from our records.  You will recieve no further emails from us.

Thank you,

%%siteadmin%%';

    xarModSetVar('roles', 'confirmationemail', $confirmationemail);

    $names = 'Admin
Root
Linux';
    $disallowednames = serialize($names);
    xarModSetVar('roles', 'disallowednames', $disallowednames);

    $emails = 'none@none.com
president@whitehouse.gov';
    $disallowedemails = serialize($emails);
    xarModSetVar('roles', 'disallowedemails', $disallowedemails);
    
    $remindertitle = 'Replacement login information for %%name%% at %%sitename%%';
    $reminderemail = '%%name%%,

Here is your new password for %%sitename%%. You may now login to %%siteurl%% using the following username and password:

username: %%username%%
password: %%password%%

-- %%siteadmin%%';

    xarModSetVar('roles', 'reminderemail', $reminderemail);
    xarModSetVar('roles', 'remindertitle', $remindertitle);
    
    //Send notifications values
    xarModSetVar('roles', 'askwelcomeemail', 1);
    xarModSetVar('roles', 'askvalidationemail', 1);
    xarModSetVar('roles', 'askdeactivationemail', 1);
    xarModSetVar('roles', 'askpendingemail', 1);
    xarModSetVar('roles', 'askpasswordemail', 1);
    xarModSetVar('roles', 'rolesdisplay', 'tabbed');
	//Set validation email
    $validationtitle = 'Validate your account %%name%% at %%sitename%%';
    $validationemail = '%%name%%,
    
Your account must be validated again because your e-mail address has changed or an administrator has unvalidated it.
You can either do this now, or on the next time that you log in. 
If you prefer to do it now, then you will need to follow this link :
%%link%%
Validation Code to activate your account:  %%valcode%%
			    
You will receive an email has soon as your account is activated again.

%%siteadmin%%%';
    xarModSetVar('roles', 'validationemail', $deactivationemail);
    xarModSetVar('roles', 'validationtitle', $deactivationtitle);
   	//Set desactivation email
    $deactivationtitle = '%%name%% deactivated at %%sitename%%';
    $deactivationemail = '%%name%%,

Your account was deactivated by the administrator.
If you want to know the reason, contact %%adminmail%%
You will receive an email as soon as your account is activated again.

%%siteadmin%%%';
    xarModSetVar('roles', 'deactivationemail', $deactivationemail);
    xarModSetVar('roles', 'deactivationtitle', $deactivationtitle);
    
    //Set pending email
    $pendingtitle = 'Pending state of %%name%% at %%sitename%%';
    $pendingemail = '%%name%%,

Your account is pending. 
You\'ll have to wait for the explicit approval of the administrator to log again.

If you want to know the reason, contact %%adminmail%%
You will receive an email has soon as your account is activated again.

%%siteadmin%%%
    ';
    xarModSetVar('roles', 'deactivationemail', $pendingemail);
    xarModSetVar('roles', 'deactivationtitle', $pendingtitle);

    $passwordtitle = 'Your password at %%sitename%% has been changed';
    $passwordemail = '%%name%%,
	
Your password has been changed by an administrator.
You can now login at %%link%% with those information :
Login : %%username%%
Password : %%pass%%

%%siteadmin%%';
	    
    xarModSetVar('roles', 'passwordemail', $passwordemail);
    xarModSetVar('roles', 'passwordtitle', $passwordtitle);
    
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
    // This won't work because the dynamicdata hooks aren't registered yet when this is
    // called at installation --> put in xarinit.php of dynamicdata instead
    // xarModAPIFunc('modules','admin','enablehooks',
    // array('callerModName' => 'roles', 'hookModName' => 'dynamicdata'));
    return true;
}

/**
 * Upgrade the users module from an old version
 *
 * @access public
 * @param oldVersion $
 * @returns bool
 * @raise DATABASE_ERROR
 */
function roless_upgrade($oldVersion)
{
    // Upgrade dependent on old version number
    switch ($oldVersion) {
        case 1.01:
	        //Send notifications values
		    xarModSetVar('roles', 'askwelcomeemail', 1);
		    xarModSetVar('roles', 'askvalidationemail', 1);
		    xarModSetVar('roles', 'askdeactivationemail', 1);
		    xarModSetVar('roles', 'askpendingemail', 1);
		    xarModSetVar('roles', 'askpasswordemail', 1);
		    xarModSetVar('roles', 'rolesdisplay', 'tabbed');
			//Set validation email
    $validationtitle = 'Validate your account %%name%% at %%sitename%%';
    $validationemail = '%%name%%,
    
Your account must be validated again because your e-mail address has changed or an administrator has unvalidated it.
You can either do this now, or on the next time that you log in. 
If you prefer to do it now, then you will need to follow this link :
%%link%%
Validation Code to activate your account:  %%valcode%%
			    
You will receive an email has soon as your account is activated again.

%%siteadmin%%%';
    xarModSetVar('roles', 'validationemail', $deactivationemail);
    xarModSetVar('roles', 'validationtitle', $deactivationtitle);
   	//Set desactivation email
    $deactivationtitle = '%%name%% deactivated at %%sitename%%';
    $deactivationemail = '%%name%%,

Your account was deactivated by the administrator.
If you want to know the reason, contact %%adminmail%%
You will receive an email as soon as your account is activated again.

%%siteadmin%%%';
    xarModSetVar('roles', 'deactivationemail', $deactivationemail);
    xarModSetVar('roles', 'deactivationtitle', $deactivationtitle);
    
    //Set pending email
    $pendingtitle = 'Pending state of %%name%% at %%sitename%%';
    $pendingemail = '%%name%%,

Your account is pending. 
You\'ll have to wait for the explicit approval of the administrator to log again.

If you want to know the reason, contact %%adminmail%%
You will receive an email has soon as your account is activated again.

%%siteadmin%%%
    ';
    xarModSetVar('roles', 'deactivationemail', $pendingemail);
    xarModSetVar('roles', 'deactivationtitle', $pendingtitle);

    $passwordtitle = 'Your password at %%sitename%% has been changed';
    $passwordemail = '%%name%%,
	
Your password has been changed by an administrator.
You can now login at %%link%% with those information :
Login : %%username%%
Password : %%pass%%

%%siteadmin%%';
	    
    xarModSetVar('roles', 'passwordemail', $passwordemail);
    xarModSetVar('roles', 'passwordtitle', $passwordtitle);
            break;
        case 1.02:   
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
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

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
