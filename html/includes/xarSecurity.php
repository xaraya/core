<?php
/**
 * File: $Id: s.xarSecurity.php 1.33 03/01/18 11:53:04+01:00 marcel@hsdev.com $
 *
 * Low-level security access mechanism
 *
 * @package security
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Security Access Mechanism
 * @author Jim McDonald
 *
 * @todo bring back possibility of time authorized keys
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
    $tables = array('security_masks' => 'xar' . '_security_masks',
                    'security_acl' => 'xar'. '_security_acl',
                    'privileges' => 'xar'. '_privileges',
                    'privmembers' => 'xar'. '_privmembers',
                    'security_realms' => 'xar'. '_security_realms',
                    'security_instances' => 'xar'. '_security_instances');

    xarDB_importTables($tables);

/**
 * Start the security subsystem
 *
 * @access protected
 * @return bool true
 */

function xarSecurity_init()
{
    return true;
}

/*
 * schemas - holds all component/instance schemas
 * Should wrap this in a static one day, but the information
 * isn't critical so we'll do it later
 */
$schemas = array();



/**
 * xarMakeGroup: create an entry in the database for a group
 *
 * This is a wrapper function
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string name
 * @return  bool
 */
function xarMakeGroup($name)
{
    $roles = new xarRoles();
    return $roles->makeGroup($name);
}

/**
 * xarMakeUser: create an entry in the database for a user
 *
 * This is a wrapper function
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @param  string name
 * @return bool
 */
function xarMakeUser($name,$uname,$email,$pass='',$dateReg='',$valCode='',$state=3,$authModule='')
{
    $roles = new xarRoles();
    return $roles->makeUser($name,$uname,$email,$pass,$dateReg,$valCode,$state,$authModule);
}

/**
 * xarMakeRoleRoot: defines an entry in the database as the root of a role tree
 *
 * This is a wrapper function
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @param  string name
 * @return bool
 */
function xarMakeRoleRoot($name)
{
    $roles = new xarRoles();
    return $roles->isRoot($name);
}

/**
 * xarMakeRoleMemberByName: create a parent-child relationship in the database between two roles
 *
 * This is a wrapper function
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @param  string child name
 * @param  string parent name
 * @return bool
 */
function xarMakeRoleMemberByName($childName, $parentName)
{
    $roles = new xarRoles();
    return $roles->makeMemberByName($childName, $parentName);
}

/**
 * xarMakeRoleMemberByUname: create a parent-child relationship in the database between two roles
 *
 * This is a wrapper function
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @param  string child uname
 * @param  string parent uname
 * @return bool
 */
function xarMakeRoleMemberByUname($childName, $parentName)
{
    $roles = new xarRoles();
    return $roles->makeMember($childName, $parentName);
}

/**
 * xarMakeRoleMemberByID: create a parent-child relationship in the database between two roles
 *
 * This is a wrapper function
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @param  string child ID
 * @param  string parent ID
 * @return bool
 */
function xarMakeRoleMemberByID($childId, $parentId)
{
    $roles = new xarRoles();
    $parent = $roles->getRole($parentId);
    $child = $roles->getRole($childId);

    return $parent->addMember($child);
}

/**
 * xarRegisterPrivilege: create an entry in the database for a privilege
 *
 * This is a wrapper function
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @param  string name
 * @param  integer realm
 * @param  string module
 * @param  string component
 * @param  string instance
 * @param  integer level
 * @param  string description
 * @return bool
 */
function xarRegisterPrivilege($name,$realm,$module,$component,$instance,$level,$description='')
{
    $privileges = new xarPrivileges();
    return $privileges->register($name,$realm,$module,$component,$instance,$level,$description);
}

/**
 * xarMakePrivilegeRoot: defines an entry in the database as the root of a privilege tree
 *
 * This is a wrapper function
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string name
 * @return  bool
 */
function xarMakePrivilegeRoot($name)
{
    $privileges = new xarPrivileges();
    return $privileges->makeEntry($name);
}

/**
 * xarMakePrivilegeMember: create a parent-child relationship in the database between two privileges
 *
 * This is a wrapper function
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @param  string childName
 * @param  string  parentName
 * @return bool
 */
function xarMakePrivilegeMember($childName, $parentName)
{
    $privileges = new xarPrivileges();
    return $privileges->makeMember($childName, $parentName);
}

/**
 * xarAssignPrivilege: assign a privilege to a role
 *
 * This is a wrapper function
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @param  string  privilege name
 * @param  string role name
 * @return bool
 */
function xarAssignPrivilege($privilege,$role)
{
    $privileges = new xarPrivileges();
    return $privileges->assign($privilege,$role);
}

/**
 * xarDefineInstance: creates an instance definition in the database
 *
 * This is a wrapper function
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @param  string module
 * @param  string type
 * @param  string query
 * @param  integer propagate
 * @param  string table2
 * @param  integer childId
 * @param  integer parentId
 * @param  string description
 * @return bool
 */
function xarDefineInstance($module,$type,$query,$propagate=0,$table2='',$childId='',$parentId='',$description='')
{
    $privileges = new xarPrivileges();

    return $privileges->defineInstance($module,$type,$query,$propagate,$table2,$childId,$parentId,$description);
}

/**
 * xarRemoveInstances: removes the instances registered by a module from the database
 *
 * This is a wrapper function
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string module
 * @return  bool
 */
function xarRemoveInstances($module)
{
    $privileges = new xarPrivileges();
    return $privileges->removeInstances($module);
}

/**
 * xarGetGroups: returns an array of all the groups in the database
 *
 * This is a wrapper function
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @return array of strings
 */
function xarGetGroups()
{
    $roles = new xarRoles();
    return $roles->getgroups();
}

/* xarFindRole: returns a role object by its name
 *
 * This is a wrapper function
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string name
 * @return  object role
 */
function xarFindRole($name)
{
    $roles = new xarRoles();
    return $roles->findRole($name);
}

/**
 * xarSecurityCheck: check a role's privileges against the masks of a component
 *
 * Checks the current group or user's privileges against a component
 * This function should be invoked every time a security check needs to be done
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @param  string mask
 * @param  integer showException
 * @param  string component
 * @param  string instance
 * @param  string module
 * @param  string role
 * @return bool
 */
function xarSecurityCheck($mask,$showException=1,$component='',$instance='',$module='',$role='')
{
    global $installing;

    if(isset($installing) && ($installing == true)) {
       return true;

    } else {
       $masks = new xarMasks();
       return $masks->xarSecurityCheck($mask,$showException,$component, $instance,$module,$role);
    }
}

/**
 * xarRegisterMask: wrapper function for registering a mask
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @param  string name
 * @param  integer realm
 * @param  string module
 * @param  string component
 * @param  string instance
 * @param  integer level
 * @param  string description
 * @return bool
 */
function xarRegisterMask($name,$realm,$module,$component,$instance,$level,$description='')
{
    global $installing;

    if(isset($installing) && ($installing == true)) {
        return true;
    } else {
        $masks = new xarMasks();
        return $masks->register($name,$realm,$module,$component,$instance,$level,$description);
    }
}

/**
 * xarUnregisterMask: wrapper function for unregistering a mask
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @param  string name
 * @return bool
 */
function xarUnregisterMask($name)
{
    $masks = new xarMasks();
    return $masks->unregister($name);
}

/**
 * xarRemoveMasks: removes the masks registered by a module from the database
 *
 * This is a wrapper function
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string module
 * @return  bool
 */
function xarRemoveMasks($module)
{
    $privileges = new xarPrivileges();
    return $privileges->removeMasks($module);
}


/**
 * see if a user is authorised to carry out a particular task
 *
 * @access public
 * @param  integer realm the realm to authorize
 * @param  string component the component to authorize
 * @param  string instance the instance to authorize
 * @param  integer level the level of access required
 * @param  integer userId  user id to check for authorisation
 * @return bool
 * @raise DATABASE_ERROR
 */
function xarSecAuthAction($testRealm, $testComponent, $testInstance, $testLevel, $userId = NULL)
{
    $msg = xarML('This call needs to be converted to the Xaraya security system');
    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DEPRECATED_API',
                    new SystemException($msg));
    return true;
}

/**
 * Generate an authorisation key
 *
 * The authorisation key is used to confirm that actions requested by a
 * particular user have followed the correct path.  Any stage that an
 * action could be made (e.g. a form or a 'delete' button) this function
 * must be called and the resultant string passed to the client as either
 * a GET or POST variable.  When the action then takes place it first calls
 * xarSecConfirmAuthKey() to ensure that the operation has
 * indeed been manually requested by the user and that the key is valid
 *
 * @access public
 * @param string modName the module this authorisation key is for (optional)
 * @return string an encrypted key for use in authorisation of operations
 * @todo bring back possibility of extra security by using date (See code)
 */
function xarSecGenAuthKey($modName = NULL)
{
    if (empty($modName)) {
        list($modName) = xarRequestGetInfo();
    }

    // Date gives extra security but leave it out for now
    // $key = xarSessionGetVar('rand') . $modName . date ('YmdGi');
    $key = xarSessionGetVar('rand') . strtolower($modName);

    // Encrypt key
    $authid = md5($key);

    // Return encrypted key
    return $authid;
}

/**
 * Confirm an authorisation key is valid
 *
 * See description of xarSecGenAuthKey for information on
 * this function
 *
 * @access public
 * @param string authIdVarName
 * @return bool true if the key is valid, false if it is not
 * @todo bring back possibility of time authorized keys
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
 *
 * @access public
 * @param string component the component to add
 * @param string schema the security schema to add
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
 * Get authorisation information for this user
 *
 * @access private
 * @param  integer userId
 * @return array two-element array of user and group permissions
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
    $realmtable = $xartable['security_realms'];

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
