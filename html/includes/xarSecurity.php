<?php
/**
 * File: $Id: s.xarSecurity.php 1.33 03/01/18 11:53:04+01:00 marcel@hsdev.com $
 *
 * Low-level security access mechanism
 *
 * @package security
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.org
 * @author Jim McDonald
*/

/**
 * Notes on security system
 *
 * Special UID and GIDS:
 *  UID -1 corresponds to 'all users', includes unregistered users
 *  GID -1 corresponds to 'all groups', includes unregistered users
 *  UID 0 corresponds to unregistered users
 *  GID 0 corresponds to unregistered users
 *
 */

//'All' and 'unregistered' for user and group permissions
define('_XARSEC_ALL', '-1');
define('_XARSEC_UNREGISTERED', '0');
/*
 * Defines for access levels
 */
define('ACCESS_INVALID', -1);
define('ACCESS_NONE', 0);
define('ACCESS_OVERVIEW', 100);
define('ACCESS_READ', 200);
define('ACCESS_COMMENT', 300);
define('ACCESS_MODERATE', 400);
define('ACCESS_EDIT', 500);
define('ACCESS_ADD', 600);
define('ACCESS_DELETE', 700);
define('ACCESS_ADMIN', 800);


	include_once 'modules/privileges/xarprivileges.php';
	include_once 'modules/roles/xarroles.php';
    $tables = array('masks' => 'xar' . '_masks',
    				'acl' => 'xar'. '_acl',
    				'privileges' => 'xar'. '_privileges',
    				'privmembers' => 'xar'. '_privmembers',
    				'realms' => 'xar'. '_realms',
//    				'roles' => 'xar'. '_roles',
//    				'rolemembers' => 'xar'. '_rolemembers',
    				'instances' => 'xar'. '_instances');

    xarDB_importTables($tables);

/**
 * Start the security subsystem
 *
 * @access protected
 * @return bool true
 */

function xarSecurity_init()
{


//    $systemPrefix = xarDBGetSystemTablePrefix();

    // Add tables
//    $tables = array('masks' => $systemPrefix . '_masks');

    return true;
}

/*
 * schemas - holds all component/instance schemas
 * Should wrap this in a static one day, but the information
 * isn't critical so we'll do it later
 */
$schemas = array();



/**
 * makeGroup: create an entry in the database for a group
 *
 * This is a wrapper function
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   name string
 * @return  boolean
 * @throws  none
 * @todo    none
*/

	function makeGroup($name) {
			$roles = new xarRoles();
			return $roles->makeGroup($name);
	}

/**
 * makeUser: create an entry in the database for a user
 *
 * This is a wrapper function
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   name string
 * @return  boolean
 * @throws  none
 * @todo    none
*/

	function makeUser($name,$uname,$email,$pass='') {
			$roles = new xarRoles();
			return $roles->makeUser($name,$uname,$email,$pass);
	}

/**
 * makeRoleRoot: defines an entry in the database as the root of a role tree
 *
 * This is a wrapper function
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   name string
 * @return  boolean
 * @throws  none
 * @todo    none
*/

	function makeRoleRoot($name) {
			$roles = new xarRoles();
			return $roles->isRoot($name);
	}

/**
 * makeRoleMember: create a parent-child relationship in the database between two roles
 *
 * This is a wrapper function
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   child name string
 * @param   parent name string
 * @return  boolean
 * @throws  none
 * @todo    none
*/

	function makeRoleMember($childname, $parentname) {
			$roles = new xarRoles();
			return $roles->makeMember($childname, $parentname);
	}

/**
 * xarRegisterPrivilege: create an entry in the database for a privilege
 *
 * This is a wrapper function
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   name string
 * @param   list of strings
 * @return  boolean
 * @throws  none
 * @todo    none
*/

	function xarRegisterPrivilege($name,$realm,$module,$component,$instance,$level,$description='') {
			$privileges = new xarPrivileges();
			return $privileges->register($name,$realm,$module,$component,$instance,$level,$description);
	}

/**
 * makePrivilegeRoot: defines an entry in the database as the root of a privilege tree
 *
 * This is a wrapper function
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   name string
 * @return  boolean
 * @throws  none
 * @todo    none
*/

	function makePrivilegeRoot($name) {
			$privileges = new xarPrivileges();
			return $privileges->makeEntry($name);
	}

/**
 * makePrivilegeMember: create a parent-child relationship in the database between two privileges
 *
 * This is a wrapper function
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   child name string
 * @param   parent name string
 * @return  boolean
 * @throws  none
 * @todo    none
*/

	function makePrivilegeMember($childname, $parentname) {
			$privileges = new xarPrivileges();
			return $privileges->makePrivilegeMember($childname, $parentname);
	}

/**
 * xarAssignPrivilege: assign a privilege to a role
 *
 * This is a wrapper function
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   privilege name string
 * @param   role name string
 * @return  boolean
 * @throws  none
 * @todo    none
*/

	function xarAssignPrivilege($privilege,$role) {
			$privileges = new xarPrivileges();
			return $privileges->assign($privilege,$role);
	}

/**
 * xarDefineInstance: creates an instance definition in the database
 *
 * This is a wrapper function
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   list of strings
 * @return  boolean
 * @throws  none
 * @todo    none
*/

	function xarDefineInstance($module,$table1,$valuefield,$displayfield,$propagate=0,$table2='',$childID='',$parentID='',$description='') {
			$privileges = new xarPrivileges();
			return $privileges->defineInstance($module,$table1,$valuefield,$displayfield,$propagate,$table2,$childID,$parentID,$description);
	}

/**
 * xarSecurityCheck: check a role's privileges against the masks of a component
 *
 * Checks the current group or user's privileges against a component
 * This function should be invoked every time a security check needs to be done
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   component string
 * @return  boolean
 * @throws  none
 * @todo    none
*/

	function xarSecurityCheck($component,$showexception=1,$instancetype='',$instance='',$role='',$module='')
	{
		global $installing;

		if(isset($installing) && ($installing == true)) {
			return true;
		}
		else {
			$masks = new xarMasks();
			return $masks->xarSecurityCheck($component,$showexception,$instancetype,
			$instance,$role,$module);
		}
	}

/**
 * xarRegisterMask: wrapper function for registering a mask
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   component string
 * @return  boolean
 * @throws  none
 * @todo    none
*/

	function xarRegisterMask($name,$realm,$module,$component,$instance,$level,$description='')
	{
		global $installing;

		if(isset($installing) && ($installing == true)) {
			return true;
		}
		else {
			$masks = new xarMasks();
			return $masks->register($name,$realm,$module,$component,$instance,$level,$description);
		}
	}

/**
 * see if a user is authorised to carry out a particular task
 * @access public
 * @param realm the realm to authorize
 * @param component the component to authorize
 * @param instance the instance to authorize
 * @param level the level of access required
 * @param uid user id to check for authorisation
 * @returns bool
 * @return true if authorised, false if not
 * @raise DATABASE_ERROR
 */
function xarSecAuthAction($testRealm, $testComponent, $testInstance, $testLevel, $userId = NULL)
{
  return true;
  // FIXME: <marco> BAD_PARAM?

    if (empty($userId)) {
        $userId = xarSessionGetVar('uid');
        if (empty($userId)) {
            $userId = 0;
        }
    }
    if (!xarVarIsCached('Permissions', $userId)) {
        // First time here - get auth info
        $perms = xarSec__getAuthInfo($userId);
        if (!isset($perms) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
            return; // throw back
        }
        if ((count($perms[0]) == 0) &&
            (count($perms[1]) == 0)) {
                // No permissions
                return;
        }
        xarVarSetCached('Permissions', $userId, $perms);
    }

    list($userPerms, $groupPerms) = xarVarGetCached('Permissions', $userId);

    // Get user access level
    $userlevel = xarSec__getLevel($userPerms, $testRealm, $testComponent, $testInstance);

    // User access level is override, so return that if it exists
    if ( $userlevel > ACCESS_INVALID ) {
        // user has explicitly defined access level for this
        // realm/component/instance combination
        if ( $userlevel >= $testLevel ) {
            // permission is granted to user
            return true;
        } else {
            // permission is prohibited to user, so group perm
            // doesn't matter
            return false;
        }
    }

    // User access level not defined. Now check group access level
    $grouplevel = xarSec__getLevel($groupPerms, $testRealm, $testComponent, $testInstance);
    if ($grouplevel >= $testLevel) {
        // permission is granted to associated group
        return true;
    }

    // No access granted
    return false;
}

/**
 * generate an authorisation key
 * <br>
 * The authorisation key is used to confirm that actions requested by a
 * particular user have followed the correct path.  Any stage that an
 * action could be made (e.g. a form or a 'delete' button) this function
 * must be called and the resultant string passed to the client as either
 * a GET or POST variable.  When the action then takes place it first calls
 * <code>xarSecConfirmAuthKey()</code> to ensure that the operation has
 * indeed been manually requested by the user and that the key is valid
 *
 * @public
 * @param modName the module this authorisation key is for (optional)
 * @returns string
 * @return an encrypted key for use in authorisation of operations
 */
function xarSecGenAuthKey($modName = NULL)
{
    if (empty($modName)) {
        list($modName) = xarRequestGetInfo();
    }

// Date gives extra security but leave it out for now
//    $key = xarSessionGetVar('rand') . $modName . date ('YmdGi');
    $key = xarSessionGetVar('rand') . strtolower($modName);

    // Encrypt key
    $authid = md5($key);

    // Return encrypted key
    return $authid;
}

/**
 * confirm an authorisation key is valid
 * <br>
 * See description of <code>xarSecGenAuthKey</code> for information on
 * this function
 * @public
 * @returns bool
 * @return true if the key is valid, false if it is not
 */
function xarSecConfirmAuthKey($authIdVarName = 'authid')
{
    list($modName) = xarRequestGetInfo();
    $authid = xarRequestGetVar($authIdVarName);

    // Regenerate static part of key
    $partkey = xarSessionGetVar('rand') . strtolower($modName);

// Not using time-sensitive keys for the moment
//    // Key life is 5 minutes, so search backwards and forwards 5
//    // minutes to see if there is a match anywhere
//    for ($i=-5; $i<=5; $i++) {
//        $testdate  = mktime(date('G'), date('i')+$i, 0, date('m') , date('d'), date('Y'));
//
//        $testauthid = md5($partkey . date('YmdGi', $testdate));
//        if ($testauthid == $authid) {
//            // Match
//
//            // We've used up the current random
//            // number, make up a new one
//            srand((double)microtime()*1000000);
//            xarSessionSetVar('rand', rand());
//
//            return true;
//        }
//    }
    if ((md5($partkey)) == $authid) {
        // Match - generate new random number for next key and leave happy
        srand((double)microtime()*1000000);
        xarSessionSetVar('rand', rand());

        return true;
    }
    // Not found, assume invalid
        $msg = xarML('Invalid authorization key for modifying item');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
}

/*
 * Register an instance schema with the security
 * system
 * @access public
 * @param component the component to add
 * @param schema the security schema to add
 *
 * Will fail if an attempt is made to overwrite an existing schema
 */
function xarSecAddSchema($component, $schema)
{
    $schemas = array();

    if (!empty($schemas[$component])) {
        return false;
    }

    $schemas[$component] = $schema;

    return true;
}

// PRIVATE FUNCTIONS

/**
 * get authorisation information for this user
 * @access public
 * @returns array
 * @return two-element array of user and group permissions
 * @raise DATABASE_ERROR
 */
function xarSec__getAuthInfo($userId)
{
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    // Tables we use
    $userpermtable = $xartable['user_perms'];
    $groupmembershiptable = $xartable['group_membership'];
    $grouppermtable = $xartable['group_perms'];
    $realmtable = $xartable['realms'];

    // Empty arrays
    $userPerms = array();
    $groupPerms = array();

    $userIds[] = -1;
    // Set userId infos
    $userIds[] = $userId;

    // FIXME: <marco> This still be an undocumented feature.
    $vars['Active User'] = $userId;
    $userIds = implode(',', $userIds);

    // Get user permissions
    $query = "SELECT xar_realm,
                     xar_component,
                     xar_instance,
                     xar_level
              FROM $userpermtable
              WHERE xar_pid IN (" . xarVarPrepForStore($userIds) . ")
              ORDER by xar_sequence";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    while(!$result->EOF) {
        list($realm, $component, $instance, $level) = $result->fields;
        $result->MoveNext();

        // Fix component and instance to auto-insert '.*'
        $component = preg_replace('/^$/', '.*', $component);
        $component = preg_replace('/^:/', '.*:', $component);
        $component = preg_replace('/::/', ':.*:', $component);
        $component = preg_replace('/:$/', ':.*', $component);
        $instance = preg_replace('/^$/', '.*', $instance);
        $instance = preg_replace('/^:/', '.*:', $instance);
        $instance = preg_replace('/::/', ':.*:', $instance);
        $instance = preg_replace('/:$/', ':.*', $instance);

        $userPerms[] = array('realm'     => $realm,
                             'component' => $component,
                             'instance'  => $instance,
                             'level'     => $level);
    }

    // Get all groups that user is in
    $query = "SELECT xar_gid
              FROM $groupmembershiptable
              WHERE xar_uid IN (" . xarVarPrepForStore($userIds) . ")";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $usergroups[] = -1;
    if (empty($userId)) {
       // Anonymous user
       $usergroups[] = 0;
    }
    while(!$result->EOF) {
        list($gid) = $result->fields;
        $result->MoveNext();

        $usergroups[] = $gid;
    }
    $usergroups = implode(',', $usergroups);

    // Get all group permissions
    $query = "SELECT xar_realm,
                     xar_component,
                     xar_instance,
                     xar_level
              FROM $grouppermtable
              WHERE xar_gid IN (" . xarVarPrepForStore($usergroups) . ")
              ORDER by xar_sequence";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    while(!$result->EOF) {
        list($realm, $component, $instance, $level) = $result->fields;
        $result->MoveNext();

        // Fix component and instance to auto-insert '.*' where
        // there is nothing there
        $component = preg_replace('/^$/', '.*', $component);
        $component = preg_replace('/^:/', '.*:', $component);
        $component = preg_replace('/::/', ':.*:', $component);
        $component = preg_replace('/:$/', ':.*', $component);
        $instance = preg_replace('/^$/', '.*', $instance);
        $instance = preg_replace('/^:/', '.*:', $instance);
        $instance = preg_replace('/::/', ':.*:', $instance);
        $instance = preg_replace('/:$/', ':.*', $instance);

        // Search/replace of special names
        // TODO: <marco> Document this and maibe do a if(isset($vars[$res[1]])), don't you think?
        while (preg_match("/<([^>]+)>/", $instance, $res)) {
            $instance = preg_replace("/<([^>]+)>/", $vars[$res[1]], $instance, 1);
        }

        $groupPerms[] = array('realm'     => $realm,
                              'component' => $component,
                              'instance'  => $instance,
                              'level'     => $level);
    }

    return array($userPerms, $groupPerms);
}

/**
 * calculate security level for a test item
 * @access private
 * @param perms array of permissions to test against
 * @param testrealm realm of item under test
 * @param testcomponent component of item under test
 * @param testinstanc instance of item under test
 * @returns int
 * @return matching security level
 */
function xarSec__getLevel($perms, $testRealm, $testComponent, $testInstance)
{
    // FIXME: <marco> BAD_PARAM?

    $level = ACCESS_INVALID;

    // If we get a test component or instance purely consisting of ':' signs
    // then it counts as blank
    $testComponent = preg_replace('/^:*$/', '', $testComponent);
    $testInstance = preg_replace('/^:*$/', '', $testInstance);

    // Test for generic permission
    if ((empty($testComponent)) &&
        (empty($testInstance))) {
        // Looking for best permission
        foreach ($perms as $perm) {
            // Confirm generic realm, or this particular realm
            if (($perm['realm'] != 0) && ($perm['realm'] != $testRealm)) {
                continue;
            }

            if ($perm['level'] > $level) {
                $level = $perm['level'];
            }
        }
        return $level;
    }

    // Test for generic instance
    if (empty($testInstance)) {
        // Looking for best permission
        foreach ($perms as $perm) {
            // Confirm generic realm, or this particular realm
            if (($perm['realm'] != 0) && ($perm['realm'] != $testRealm)) {
                continue;
            }

            // Confirm that component matches
            if (preg_match("/^$perm[component]$/", $testComponent)) {
                if ($perm['level'] > $level) {
                    $level = $perm['level'];
                }
            }
        }
        return $level;
    }


    // Normal permissions check
    foreach ($perms as $perm) {

        // Confirm generic realm, or this particular realm
        if (($perm['realm'] != 0) && ($perm['realm'] != $testRealm)) {
            continue;
        }

        if (($testComponent != '') && ($testInstance != '')) {
            // Confirm that component and instance match
            if (!((ereg("^$perm[component]$", $testComponent)) &&
                  (ereg("^$perm[instance]$", $testInstance)))) {
                continue;
            }
        } elseif (($testComponent == '') && ($testInstance != '')) {
            // Confirm that instance matches
            if (!ereg("^$perm[instance]$", $testInstance)) {
                continue;
            }
        }

        // We have a match - set the level and quit
        $level = $perm['level'];
        break;

    }
    return($level);
}

/* Get list of schemas
 * @access
 * @param none
 * @return schemas
 */
// FIXME: <marco> Who use this?
// <niceguyeddie> -- used for the schema breakdown in permissions module.
//function getinstanceschemainfo()
function xarSec__getBlocksInstanceSchemaInfo()
{
    // FIXME: <marco> Exceptions

    $schemas = array();
    static $gotschemas = 0;

    if ($gotschemas == 0) {
        // Get all module schemas
        xarSec__getModulesInstanceSchemaInfo();

        // Get all block schemas
        xarBlock_loadAll();

        $gotschemas = 1;
    }

    return $schemas;
}

/* Get instance information from modules
 * @access
 * @param none
 */
// FIXME: <marco> Who use this?
// <niceguyeddie> -- Used for the schema breakdown for permissions.
//function getmodulesinstanceschemainfo()
function xarSec__getModulesInstanceSchemaInfo()
{

    $moddir = opendir('modules/');
    while ($modName = readdir($moddir)) {
        $osfile = 'modules/' . xarVarPrepForOS($modName) . '/xarversion.php';
        @include $osfile;

        // pnAPI compatibility
        if (!file_exists($osfile)) {
           $osfile2 = 'modules/' . xarVarPrepForOS($modName) . '/pnversion.php';
           @include $osfile2;
        }

        if (!empty($modversion['securityschema'])) {
            foreach ($modversion['securityschema'] as $component => $instance) {
                xarSecAddSchema($component, $instance);
            }
        }
        $modversion['securityschema'] = '';
    }
    closedir($moddir);
}

?>