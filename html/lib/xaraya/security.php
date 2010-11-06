<?php
/**
 *
 * @package core
 * @subpackage security
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Jim McDonald
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @author  Richard Cave <rcave@xaraya.com>
 * @todo bring back possibility of time authorized keys
 */

/**
 * Notes on security system
 *
 * Special ID and GIDS:
 *  ID -1 corresponds to 'all users', includes unregistered users
 *  GID -1 corresponds to 'all groups', includes unregistered users
 *  ID 0 corresponds to unregistered users
 *  GID 0 corresponds to unregistered users
 *
 */

// @todo Maybe changing this touch to a centralized API would be a good idea?
//Even if in the end it would use touched files too...
if (file_exists(sys::varpath() . '/security/on.touch')) {
    sys::import('xaraya.xarCacheSecurity');
}

// FIXME: Can we reverse this? (i.e. the module loading the files from here?)
//        said another way, can we move the two files to /includes (partially preferably)
sys::import('modules.privileges.class.privileges');
sys::import('modules.roles.class.roles');

/**
 * xarMakeGroup: create an entry in the database for a group
 *
 * This is a wrapper function
 *
 * 
 * @param   string name
 * @return  bool
 */
function xarMakeGroup($name,$uname='') { return xarRoles::makeGroup($name,$uname); }

/**
 * xarMakeUser: create an entry in the database for a user
 *
 * This is a wrapper function
 *
 * 
 * @param  string name
 * @return boolean
 */
function xarMakeUser($name,$uname,$email,$pass='',$dateReg='',$valCode='',$state=3,$authModule= 0)
{
    return xarRoles::makeUser($name,$uname,$email,$pass,$dateReg,$valCode,$state,$authModule);
}

/**
 * xarMakeRoleMemberByName: create a parent-child relationship in the database between two roles
 *
 * This is a wrapper function
 *
 * 
 * @param  string child name
 * @param  string parent name
 * @return boolean
 */
function xarMakeRoleMemberByName($childName, $parentName)
{
    return xarRoles::makeMemberByName($childName, $parentName);
}

/**
 * xarMakeRoleMemberByUname: create a parent-child relationship in the database between two roles
 *
 * This is a wrapper function
 *
 * 
 * @param  string child uname
 * @param  string parent uname
 * @return boolean
 */
function xarMakeRoleMemberByUname($childName, $parentName)
{
    $parent = xarRoles::ufindRole($parentName);
    $child = xarRoles::ufindRole($childName);

    return $parent->addMember($child);
}

/**
 * xarMakeRoleMemberByID: create a parent-child relationship in the database between two roles
 *
 * This is a wrapper function
 *
 * 
 * @param  string child ID
 * @param  string parent ID
 * @return boolean
 */
function xarMakeRoleMemberByID($childId, $parentId)
{
    $parent = xarRoles::getRole($parentId);
    $child = xarRoles::getRole($childId);

    return $parent->addMember($child);
}

/**
 * xarRemoveRoleMemberByID: destroys a parent-child relationship in the database between two roles
 *
 * This is a wrapper function
 *
 * 
 * @param  string child ID
 * @param  string parent ID
 * @return boolean
 */
function xarRemoveRoleMemberByID($childId, $parentId)
{
    $parent = xarRoles::getRole($parentId);
    $child = xarRoles::getRole($childId);

    return $parent->removeMember($child);
}

/**
 * xarRegisterPrivilege: create an entry in the database for a privilege
 *
 * This is a wrapper function
 *
 * 
 * @param  string name
 * @param  integer realm
 * @param  string module
 * @param  string component
 * @param  string instance
 * @param  integer level
 * @param  string description
 * @return boolean
 */
function xarRegisterPrivilege($name,$realm,$module,$component,$instance,$level,$description='')
{
    // Check if the privilege already exists
    $privilege = xarPrivileges::findPrivilege($name);
    if (!$privilege) {
        return xarPrivileges::register($name,$realm,$module,$component,$instance,xarSecurityLevel($level),$description);
    }
    return;
}

/**
 * xarMakePrivilegeMember: create a parent-child relationship in the database between two privileges
 *
 * This is a wrapper function
 *
 * 
 * @param  string childName
 * @param  string  parentName
 * @return boolean
 */
function xarMakePrivilegeMember($childName, $parentName)
{
    return xarPrivileges::makeMember($childName, $parentName);
}

/**
 * xarAssignPrivilege: assign a privilege to a role
 *
 * This is a wrapper function
 *
 * 
 * @param  string  privilege name
 * @param  string role name
 * @return boolean
 */
function xarAssignPrivilege($privilege,$role)
{
    return xarPrivileges::assign($privilege,$role);
}

/**
 * xarRemovePrivileges: removes the privileges registered by a module from the database
 *
 * This is a wrapper function
 *
 * 
 * @param   string module
 * @return  bool
 */
function xarRemovePrivileges($module)
{
    // Get the pids for the module
    $modulePrivileges = xarPrivileges::findPrivilegesForModule($module);
    foreach ($modulePrivileges as $modulePrivilege) {
        $modulePrivilege->remove();
    }
}

/**
 * xarDefineInstance: creates an instance definition in the database
 *
 * This is a wrapper function
 *
 * 
 * @param  string module
 * @param  string type
 * @param  string query
 * @param  integer propagate
 * @param  string table2
 * @param  integer childId
 * @param  integer parentId
 * @param  string description
 * @return boolean
 */
function xarDefineInstance($module,$type,$query,$propagate=0,$table2='',$childId='',$parentId='',$description='')
{
    return xarPrivileges::defineInstance($module,$type,$query,$propagate,$table2,$childId,$parentId,$description);
}

/**
 * xarRemoveInstances: removes the instances registered by a module from the database
 *
 * This is a wrapper function
 *
 * 
 * @param   string module
 * @return  bool
 */
function xarRemoveInstances($module)
{
    return xarPrivileges::removeInstances($module);
}

/**
 * xarGetGroups: returns an array of all the groups in the database
 *
 * This is a wrapper function
 *
 * 
 * @return array of strings
 */
function xarGetGroups() { return xarRoles::getgroups(); }

/**
 * xarFindRole: returns a role object by its name
 *
 * This is a wrapper function
 *
 * 
 * @param   string name
 * @return  object role
 */
function xarFindRole($name) { return xarRoles::findRole($name);  }
function xarUFindRole($name){ return xarRoles::ufindRole($name); }

function xarCurrentRole()
{
    return xarRoles::getRole(xarSessionGetVar('role_id'));
}

function xarIsParent($name1, $name2)
{
    $role1 = xarRoles::findRole($name1);
    $role2 = xarRoles::ufindRole($name2);
    if (is_object($role1) && is_object($role2)) {
        return $role2->isParent($role1);
    }
    return false;
}

function xarIsAncestor($name1, $name2)
{
    $role1 = xarRoles::findRole($name1);
    $role2 = xarRoles::ufindRole($name2);
    if (is_object($role1) && is_object($role2)) {
        return $role2->isAncestor($role1);
    }
    return false;
}

/**
 * xarTree: creates a tree object
 *
 * This is a wrapper function
 *
 * 
 * @param   string name
 * @return  object role
 * @todo    what is this doing here?
 */
function xarTree()
{
    // Since the class xarTreeRenderer exists in both roles and privileges this can lead to errors.
    sys::import('modules.roles.xartreerenderer');
    $tree = new xarTreeRenderer();
    return $tree;
}

/**
 * xarReturnPrivilege: stores a privilege from an external wizard in the repository.
 *
 * This is a wrapper function
 *
 * 
 * @param   integer pid,level
 * @param   strings pid,name,realm,module,component
 * @param   array instance
 * @return  boolean
 */
function xarReturnPrivilege($pid,$name,$realm,$module,$component,$instance,$level)
{
    return xarMod::apiFunc('privileges','admin','returnprivilege',array(
        'pid' => $pid,
        'name' => $name,
        'realm' => $realm,
        'module' => $module,
        'component' => $component,
        'instance' => $instance,
        'level' => $level));
}

/**
 * xarSecurityLevel: gets a security level based on its name.
 *
 * This is a wrapper function
 *
 * 
 * @param   integer levelname
 * @return  security level
 */
function xarSecurityLevel($levelname)
{
    return xarMasks::xarSecLevel($levelname);
}

/**
 * xarPrivExists: checks whether a privilege exists.
 *
 *
 * 
 * @param   string name of privilege
 * @return  boolean
 */
function xarPrivExists($name)
{
    $priv = xarPrivileges::findPrivilege($name);
    if ($priv) return true;
    else return false;
}

/**
 * xarMaskExists: checks whether a mask exists.
 *
 *
 * 
 * @param   string name of mask
 * @param   string module of mask
 * @return  bool
 */
function xarMaskExists($name,$module="All",$component="All")
{
    if ($mask == "All") $mask = 0;
    $mask = xarSecurity::getMask($name,$module,$component,true);
    if ($mask) return true;
    else return false;
}

/**
 * xarSecurityCheck: check a role's privileges against the masks of a component
 *
 * Checks the current group or user's privileges against a component
 * This function should be invoked every time a security check needs to be done
 *
 * 
 * @param  string  $mask
 * @param  integer $showException
 * @param  string  $component
 * @param  string  $instance
 * @param  string  $module
 * @param  string  $role
 * @return boolean
 */
function xarSecurityCheck($mask, $showException=1, $component='', $instance='', $module='', $role='',$pnrealm=0,$pnlevel=0)
{
    $installing = xarCoreCache::getCached('installer','installing');
    if(isset($installing) && ($installing == true)) {
       return true;
    }
    else {
        sys::import('modules.privileges.class.security');
       return xarSecurity::check($mask, $showException, $component, $instance, $module, $role,$pnrealm,$pnlevel);
    }
}

/**
 * xarRegisterMask: wrapper function for registering a mask
 *
 * 
 * @param  string  $name
 * @param  integer $realm
 * @param  string  $module
 * @param  string  $component
 * @param  string  $instance
 * @param  integer $level
 * @param  string  $description
 * @return boolean
 */
function xarRegisterMask($name,$realm,$module,$component,$instance,$level,$description='')
{
    return xarMasks::register($name,$realm,$module,$component,$instance,xarSecurityLevel($level),$description);
}

/**
 * xarUnregisterMask: wrapper function for unregistering a mask
 *
 * 
 * @param  string name
 * @return boolean
 */
function xarUnregisterMask($name)
{
    return xarMasks::unregister($name);
}

/**
 * xarRemoveMasks: removes the masks registered by a module from the database
 *
 * This is a wrapper function
 *
 * 
 * @param   string module
 * @return  bool
 */
function xarRemoveMasks($module)
{
    if ($module == "All") {
        $modid = xarSecurity::PRIVILEGES_ALL;
    } elseif ($module == null) {
        $modid = null;
    } else {
        $modid = xarMod::getID($module);
    }
    return xarMasks::removeMasks($modid);
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
 * 
 * @param string modName the module this authorisation key is for (optional)
 * @return string an encrypted key for use in authorisation of operations
 * @todo bring back possibility of extra security by using date (See code)
 */
function xarSecGenAuthKey($modName = NULL)
{
    if (empty($modName)) {
        list($modName) = xarController::$request->getInfo();
    }

    // Date gives extra security but leave it out for now
    // $key = xarSessionGetVar('rand') . $modName . date ('YmdGi');
    $key = xarSessionGetVar('rand') . strtolower($modName);

    // Encrypt key
    $authid = md5($key);

    // Tell xarCache not to cache this page
    xarCache::noCache();

    // Return encrypted key
    return $authid;
}

/**
 * Confirm an authorisation key is valid
 *
 * See description of xarSecGenAuthKey for information on
 * this function
 *
 * 
 * @param string authIdVarName
 * @return boolean true if the key is valid, false if it is not
 * @throws ForbiddenOperationException
 * @todo bring back possibility of time authorized keys
 */
function xarSecConfirmAuthKey($modName=NULL, $authIdVarName='authid', $catch=false)
{
    if(!isset($modName)) list($modName) = xarController::$request->getInfo();
    $authid = xarController::getVar($authIdVarName);

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
    if ($catch) throw new ForbiddenOperationException();
    else return false;
}

?>