<?php
/**
 *
 * @package core\security\legacy
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Jim McDonald
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @author  Richard Cave <rcave@xaraya.com>
 * @todo bring back possibility of time authorized keys
 */

/**
 * xarMakeGroup: create an entry in the database for a group
 *
 * This is a wrapper function
 *
 * @fixme   this is no longer used and the makeGroup method doesn't exist (anymore)
 * @uses xarRoles::makeGroup()
 * @deprecated
 * @param   string name
 * @return  bool
 */
function xarMakeGroup($name,$uname='') { return xarRoles::makeGroup($name,$uname); }

/**
 * xarMakeUser: create an entry in the database for a user
 *
 * This is a wrapper function
 *
 * @fixme   this is no longer used and the makeUser method doesn't exist (anymore)
 * @uses xarRoles::makeUser()
 * @deprecated
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
 * @uses xarRoles::makeMemberByName()
 * @deprecated
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
 * @uses xarRoles::makeMemberByUname()
 * @deprecated
 * @param  string child uname
 * @param  string parent uname
 * @return boolean
 */
function xarMakeRoleMemberByUname($childName, $parentName)
{
    return xarRoles::makeMemberByUname($childName, $parentName);
}

/**
 * xarMakeRoleMemberByID: create a parent-child relationship in the database between two roles
 *
 * This is a wrapper function
 *
 * @uses xarRoles::makeMemberByID()
 * @deprecated
 * @param  string child ID
 * @param  string parent ID
 * @return boolean
 */
function xarMakeRoleMemberByID($childId, $parentId)
{
    return xarRoles::makeMemberByID($childId, $parentId);
}

/**
 * xarRemoveRoleMemberByID: destroys a parent-child relationship in the database between two roles
 *
 * This is a wrapper function
 *
 * @uses xarRoles::removeMemberByID()
 * @deprecated
 * @param  string child ID
 * @param  string parent ID
 * @return boolean
 */
function xarRemoveRoleMemberByID($childId, $parentId)
{
    return xarRoles::removeMemberByID($childId, $parentId);
}

/**
 * xarRegisterPrivilege: create an entry in the database for a privilege
 *
 * This is a wrapper function
 *
 * @uses xarPrivileges::register()
 * @deprecated
 * @param  string name
 * @param  integer realm
 * @param  string module
 * @param  string component
 * @param  string instance
 * @param  mixed   $level string or integer - support both and convert as needed in register()
 * @param  string description
 * @return boolean
 */
function xarRegisterPrivilege($name,$realm,$module,$component,$instance,$level,$description='')
{
    return xarPrivileges::register($name,$realm,$module,$component,$instance,$level,$description);
}

/**
 * xarMakePrivilegeMember: create a parent-child relationship in the database between two privileges
 *
 * This is a wrapper function
 *
 * @uses xarPrivileges::makeMember()
 * @deprecated
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
 * @uses xarPrivileges::assign()
 * @deprecated
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
 * @uses xarPrivileges::removeModule()
 * @deprecated
 * @param   string module
 * @return  bool
 */
function xarRemovePrivileges($module)
{
    xarPrivileges::removeModule($module);
}

/**
 * xarDefineInstance: creates an instance definition in the database
 *
 * This is a wrapper function
 *
 * @uses xarPrivileges::defineInstance()
 * @deprecated
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
 * @uses xarPrivileges::removeInstances()
 * @deprecated
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
 * @uses xarRoles::getgroups()
 * @deprecated
 * @return array of strings
 */
function xarGetGroups() { return xarRoles::getgroups(); }

/**
 * xarFindRole: returns a role object by its name
 *
 * This is a wrapper function
 *
 * @uses xarRoles::findRole()
 * @deprecated
 * @param   string name
 * @return  object role
 */
function xarFindRole($name) { return xarRoles::findRole($name);  }
/**
 * @uses xarRoles::ufindRole()
 * @deprecated
 */
function xarUFindRole($name){ return xarRoles::ufindRole($name); }

/**
 * @uses xarRoles::current()
 * @deprecated
 */
function xarCurrentRole()
{
    return xarRoles::current();
}

/**
 * @uses xarRoles::isParent()
 * @deprecated
 */
function xarIsParent($name1, $name2)
{
    return xarRoles::isParent($name1, $name2);
}

/**
 * @uses xarRoles::isAncestor()
 * @deprecated
 */
function xarIsAncestor($name1, $name2)
{
    return xarRoles::isAncestor($name1, $name2);
}

/**
 * xarTree: creates a tree object
 *
 * This is a wrapper function
 *
 * @uses xarTreeRenderer
 * @deprecated
 * @param   string name
 * @return  object role
 * @todo    what is this doing here?
 * @fixme   ithis seems to be implemented via Javascript now in roles & privileges
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
 * @uses xarPrivileges::external()
 * @deprecated
 * @param   integer pid,level
 * @param   strings pid,name,realm,module,component
 * @param   array instance
 * @return  boolean
 */
function xarReturnPrivilege($pid,$name,$realm,$module,$component,$instance,$level)
{
    return xarPrivileges::external($pid,$name,$realm,$module,$component,$instance,$level);
}

/**
 * xarSecurityLevel: gets a security level based on its name.
 *
 * This is a wrapper function
 *
 * @uses xarSecurity::getLevel()
 * @deprecated
 * @param   integer levelname
 * @return  security level
 */
function xarSecurityLevel($levelname)
{
    return xarSecurity::getLevel($levelname);
}

/**
 * xarPrivExists: checks whether a privilege exists.
 *
 * @uses xarSecurity::hasPrivilege()
 * @deprecated
 * @param   string name of privilege
 * @return  boolean
 */
function xarPrivExists($name)
{
    return xarSecurity::hasPrivilege($name);
}

/**
 * xarMaskExists: checks whether a mask exists.
 *
 * @uses xarSecurity::hasMask()
 * @deprecated
 * @param   string name of mask
 * @param   string module of mask
 * @return  bool
 */
function xarMaskExists($name,$module="All",$component="All")
{
    return xarSecurity::hasMask($name, $module, $component);
}

/**
 * xarSecurityCheck: check a role's privileges against the masks of a component
 *
 * Checks the current group or user's privileges against a component
 * This function should be invoked every time a security check needs to be done
 *
 * @uses xarSecurity::check()
 * @deprecated
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
 * @uses xarMasks::register()
 * @deprecated
 * @param  string  $name
 * @param  integer $realm
 * @param  string  $module
 * @param  string  $component
 * @param  string  $instance
 * @param  mixed   $level string or integer - support both and convert as needed in register()
 * @param  string  $description
 * @return boolean
 */
function xarRegisterMask($name,$realm,$module,$component,$instance,$level,$description='')
{
    return xarMasks::register($name,$realm,$module,$component,$instance,$level,$description);
}

/**
 * xarUnregisterMask: wrapper function for unregistering a mask
 *
 * @uses xarMasks::unregister()
 * @deprecated
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
 * @uses xarMasks::removemasks()
 * @deprecated
 * @param   string module
 * @return  bool
 */
function xarRemoveMasks($module)
{
    return xarMasks::removemasks($module);
}

/**
 * Generate an authorisation key
 *
 * @uses xarSec::genAuthKey()
 * @deprecated
 * @param string modName the module this authorisation key is for (optional)
 * @return string an encrypted key for use in authorisation of operations
 * @todo bring back possibility of extra security by using date (See code)
 */
function xarSecGenAuthKey($modName = NULL)
{
    return xarSec::genAuthKey($modName);
}

/**
 * Confirm an authorisation key is valid
 *
 * @uses xarSec::confirmAuthKey()
 * @deprecated
 * @param string authIdVarName
 * @return boolean true if the key is valid, false if it is not
 * @throws ForbiddenOperationException
 * @todo bring back possibility of time authorized keys
 */
function xarSecConfirmAuthKey($modName=NULL, $authIdVarName='authid', $catch=false)
{
    return xarSec::confirmAuthKey($modName, $authIdVarName, $catch);
}

