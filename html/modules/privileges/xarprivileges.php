<?php
/**
 * File: $Id$
 *
 * Purpose of file:  Privileges administration API
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
*/

/**
 * xarMasks: class for the mask repository
 *
 * Represents the repository containing all security masks
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @throws  none
 * @todo    none
*/


class xarMasks
{
    var $dbconn;
    var $privilegestable;
    var $privmemberstable;
    var $maskstable;
    var $modulestable;
    var $modulestatestable;
    var $realmstable;
    var $acltable;
    var $allmasks;
    var $levels;
    var $instancestable;
    var $privsetstable;

    var $privilegeset;

/**
 * xarMasks: constructor for the class
 *
 * Just sets up the db connection and initializes some variables
 * This should really be a static class
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  the masks object
 * @throws  none
 * @todo    none
*/
    function xarMasks() {
        list($this->dbconn) = xarDBGetConn();
        $xartable = xarDBGetTables();
        $this->privilegestable = $xartable['privileges'];
        $this->privmemberstable = $xartable['privmembers'];
        $this->maskstable = $xartable['security_masks'];
        $this->modulestable = $xartable['modules'];
        $this->modulestatestable = $xartable['module_states'];
        $this->realmstable = $xartable['security_realms'];
        $this->acltable = $xartable['security_acl'];
        $this->instancestable = $xartable['security_instances'];
//        $this->privsetstable = $xartable['security_privsets'];

// hack this for display purposes
// probably should be defined elsewhere
        $this->levels = array(0=>'No Access (0)',
                    100=>'Overview (100)',
                    200=>'Read (200)',
                    300=>'Comment (300)',
                    400=>'Moderate (400)',
                    500=>'Edit (500)',
                    600=>'Add (600)',
                    700=>'Delete (700)',
                    800=>'Administer (800)');
    }

/**
 * getmasks: returns all the current masks for a given module and component.
 *
 * Returns an array of all the masks in the masks repository for a given module and component
 * The repository contains an entry for each mask.
 * This function will initially load the masks from the db into an array and return it.
 * On subsequent calls it just returns the array .
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string: module name
 * @param   string: component name
 * @return  array of mask objects
 * @throws  list of exception identifiers which can be thrown
 * @todo    list of things which must be done to comply to relevant RFC
*/
    function getmasks($module = 'All',$component='All') {

        if ($module == '' || $module == 'All') {
            if ($component == '' || $component == 'All') {
                $query = "SELECT * FROM $this->maskstable ORDER BY xar_module, xar_component, xar_name";
            }
            else {
                $query = "SELECT * FROM $this->maskstable
                        WHERE (xar_component = '$component')
                        OR (xar_component = 'All')
                        OR (xar_component = 'None')
                        ORDER BY xar_module, xar_component, xar_name";
            }
        }
        else {
            if ($component == '' || $component == 'All') {
                $query = "SELECT * FROM $this->maskstable
                        WHERE xar_module = '$module' ORDER BY xar_module, xar_component, xar_name";
            }
            else {
            $query = "SELECT *
                    FROM $this->maskstable WHERE (xar_module = '$module')
                    AND ((xar_component = '$component')
                    OR (xar_component = 'All')
                    OR (xar_component = 'None'))
                    ORDER BY xar_module, xar_component, xar_name";
            }
        }
        $result = $this->dbconn->Execute($query);
        if (!$result) return;
        $masks = array();
        while(!$result->EOF) {
            list($sid, $name, $realm, $module, $component, $instance, $level,
                    $description) = $result->fields;
            $pargs = array('sid' => $sid,
                               'name' => $name,
                               'realm' => $realm,
                               'module' => $module,
                               'component' => $component,
                               'instance' => $instance,
                               'level' => $level,
                               'description' => $description);
            array_push($masks, new xarMask($pargs));
            $result->MoveNext();
        }
        return $masks;
    }

/**
 * register: register a mask
 *
 * Creates a mask entry in the masks table
 * This function should be invoked every time a new mask is created
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   array of mask values
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function register($name,$realm,$module,$component,$instance,$level,$description='')
    {
        $nextID = $this->dbconn->genID($this->maskstable);
        $nextIDprep = xarVarPrepForStore($nextID);
        $nameprep = xarVarPrepForStore($name);
        $realmprep = xarVarPrepForStore($realm);
        $moduleprep = xarVarPrepForStore($module);
        $componentprep = xarVarPrepForStore($component);
        $instanceprep = xarVarPrepForStore($instance);
        $levelprep = xarVarPrepForStore($level);
        $descriptionprep = xarVarPrepForStore($description);
        $query = "INSERT INTO $this->maskstable VALUES ($nextIDprep,
                                                '$nameprep',
                                                '$realmprep',
                                                '$moduleprep',
                                                '$componentprep',
                                                '$instanceprep',
                                                $levelprep,
                                                '$descriptionprep')";
        if (!$this->dbconn->Execute($query)) return;
        return true;
    }

/**
 * unregister: unregister a mask
 *
 * Removes a mask entry from the masks table
 * This function should be invoked every time a mask is removed
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string representing a mask name
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function unregister($name)
    {
        $query = "DELETE FROM $this->maskstable WHERE xar_name = '$name'";
        if (!$this->dbconn->Execute($query)) return;
        return true;
    }

/**
 * removeMasks: remove the masks registered by a module form the database
 * *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   module name
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function removemasks($module)
    {
        $query = "DELETE FROM $this->maskstable
              WHERE xar_module = '$module'";
        //Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query)) return;
        return true;
    }

/**
 * winnow: merges two arrays of privileges to a single array of privileges
 *
 * The privileges are compared for implication and the less mighty are discarded
 * This is the way privileges hierarchies are contracted.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   array of privileges objects
 * @param   array of privileges objects
 * @return  array of privileges objects
 * @throws  none
 * @todo    create exceptions for bad input
*/
    function winnow($privs1, $privs2)
    {
        if (!is_array($privs1) || !is_array($privs1)) {
            $msg = xarML('Parameters to winnow need to be arrays');
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException($msg));
            return;
        }
        if ((($privs1 == array()) || ($privs1 == '')) &&
            (($privs2 == array()) || ($privs2 == ''))) return array();

        $privs1 = array_merge($privs1,$privs2);
        $privs2 = array();
        foreach ($privs1 as $key1 => $priv1) {
            $matched = false;
            foreach ($privs2 as $key2 => $priv2) {
//                echo "Winnowing: ";
//                echo $priv1->getName(). " implies " . $priv2->getName() . ": " . $priv1->implies($priv2) . " | ";
//                echo $priv2->getName(). " implies " . $priv1->getName() . ": " . $priv2->implies($priv1) . " | ";
                if ($priv1->implies($priv2)) {
                    $privs3 = $privs2;
                    $notmoved = true;
                    foreach ($privs3 as $priv3) if($priv3->matchesexactly($priv1)) $notmoved = false;
                    if ($notmoved) $privs2[$key2] = $priv1;
                    else if (!$priv1->matchesexactly($priv2)) array_splice($privs2,$key2);
                    $matched = true;
                }
                elseif ($priv2->implies($priv1) || $priv1->matchesexactly($priv2)) {
                    $matched = true;
                    break;
                }
            }
            if(!$matched) $privs2[] = $priv1;
        }

// done
        return $privs2;
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

    function xarSecurityCheck($mask,$catch=1,$component='', $instance='',$module='',$rolename='')
    {

// get the masks pertaining to the current module and the component requested
        if ($module == '') list($module) = xarRequestGetInfo();
// no need to update / select in database for each block here
//        if ($module == 'blocks') $module = xarModGetVar('blocks','currentmodule');

// I'm a bit lost on this line. Does this var ever get set?
        if ($module == 'blocks' && xarVarIsCached('Security.Variables','currentmodule'))
        $module = xarVarGetCached('Security.Variables','currentmodule');

        $mask =  $this->getMask($mask);
        if (!$mask) {
            if ($component == "") {
                $msg = xarML('Did not find a mask registered for an unspecified component in module ') . $module;
            }
            else {
                $msg = xarML('No masks registered for component ') . $component .
                xarML(' in module ') . $module;
            }
            xarExceptionSet(XAR_USER_EXCEPTION, 'NO_MASK',
                           new DefaultUserException($msg));
            return;
        }

        // insert any instance overrides
        if ($instance != '') $mask->setInstance($instance);

    // check if we already have the irreducible set of privileges for the current user
        if (!xarVarIsCached('Security.Variables','privilegeset') || !empty($rolename)) {

// get the Roles class
            include_once 'modules/roles/xarroles.php';
            $roles = new xarRoles();

// get the uid of the role we will check against
// an empty role means take the current user
            if ($rolename == '') {
                $userID = xarSessionGetVar('uid');
                if (empty($userID)) {
                    $userID = _XAR_ID_UNREGISTERED;
                }
                $role = $roles->getRole($userID);
            }
            else {
                $role = $roles->findRole($rolename);
            }

// get the privileges and test against them
            $privileges = $this->irreducibleset(array('roles' => array($role)));

// leave this as same-page caching for now, until the db cache is finished
        // if this is the current user, save the irreducible set of privileges to cache
            if ($rolename == '') {
                xarVarSetCached('Security.Variables','privilegeset',$privileges);
            }
        } else {
            // get the irreducible set of privileges for the current user from cache
            $privileges = xarVarGetCached('Security.Variables','privilegeset');
        }

        $pass = $this->testprivileges($mask,$privileges,false);

//        $pass = $this->testprivileges($mask,$this->getprivset($role),false);

// check if the exception needs to be caught here or not
        if ($catch && !$pass) {
            $msg = xarML('No privilege for #(1)',$mask->getName());
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException($msg));
        }

// done
        return $pass;
    }


/**
 * forgetprivsets: remove all irreducible set of privileges from the db
 *
 * used to lighten the cache
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function forgetprivsets()
    {
        $query = "DELETE FROM $this->privsetstable";
        if (!$this->dbconn->Execute($query)) return;
        return true;
    }

/**
 * getprivset: get a role's irreducible set of privileges from the db
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   role object
 * @return  array containing the role's ancestors and privileges
 * @throws  none
 * @todo    none
*/
    function getprivset($role)
    {
        $query = "SELECT xar_set FROM $this->privsetstable WHERE xar_uid =" . $role->getID();
        $result = $this->dbconn->Execute($query);
        if (!$result) return;
        if ($result->EOF) {
            $privileges = $this->irreducibleset(array('roles' => array($role)));
            $serprivs = xarVarPrepForStore(serialize($privileges));
            $query = "INSERT INTO $this->privsetstable VALUES (" . $role->getID() . ",'$serprivs')";
            if (!$this->dbconn->Execute($query)) return;
            return $privileges;
        }
        else {
            list($serprivs) = $result->fields;
        }
        return unserialize($serprivs);
    }

/**
 * irreducibleset: assemble a role's irreducible set of privileges
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   array representing the initial node to start from
 * @return  nested array containing the role's ancestors and privileges
 * @throws  none
 * @todo    none
*/
    function irreducibleset($coreset)
    {
        $roles = $coreset['roles'];
        $coreset['privileges'] = array();
        $coreset['children'] = array();
        if (count($roles) == 0) return $coreset;

        $parents = array();
        foreach ($roles as $role) {
            $privs = $role->getAssignedPrivileges();
            $privileges = array();
            foreach ($privs as $priv) {
                $privileges = $this->winnow(array($priv),$privileges);
                $privileges = $this->winnow($priv->getDescendants(),$privileges);
            }
            $coreset['privileges'] = $this->winnow($coreset['privileges'],$privileges);
            $parents = array_merge($parents,$role->getParents());

        }
        $coreset['children'] = $this->irreducibleset(array('roles' => $parents));
        return $coreset;
    }

/**
 * testprivileges: test an irreducible set of privileges against a mask
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   mask object
 * @param   nested array representing the irreducibles set of privileges
 * @param   boolean false (initial test value)
 * @return  boolean false if check fails, privilege object if check succeeds
 * @throws  none
 * @todo    none
*/
    function testprivileges($mask,$privilegeset,$pass)
    {
        $matched = false;
        foreach ($privilegeset['privileges'] as $privilege) {
//        echo "<BR>Comparing " . $privilege->present() . " against " . $mask->present() . ". ";
//        if ($privilege->includes($mask)) echo $privilege->getName() . " wins. ";
//        elseif ($mask->includes($privilege)) echo $mask->getName() . " wins. ";
//        else echo "no match. ";
            if($privilege->implies($mask)) {
                $pass = $privilege;
                $matched = true;
                break;
            }
            elseif ($mask->includes($privilege)) {
                if ($privilege->getLevel() >= $mask->getLevel()) {
                    $pass = $privilege;
                    $matched = true;
                }
            }
            elseif ($privilege->includes($mask)) {
                $matched = true;
                break;
            }
        }
        foreach ($privilegeset['privileges'] as $privilege) {
//            echo "<BR>Comparing " . $privilege->present() . " against " . $mask->present() . " <B>for deny</B>. ";
//            if (($privilege->getLevel() == 0) && ($privilege->includes($mask))) echo $privilege->getName() . " found. ";
//            else echo "not found. ";
            if (($privilege->getLevel() == 0) && ($privilege->includes($mask))) {
            $pass = false;
            $matched = true;
            break;
           }
        }
        if (!$matched && ($privilegeset['children'] != array())) $pass = $this->testprivileges($mask,$privilegeset['children'],$pass);
        return $pass;
    }

/**
 * getMask: gets a single mask
 *
 * Retrieves a single mask from the Masks repository
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string
 * @return  mask object
 * @throws  none
 * @todo    none
*/
    function getMask($name)
    {
        // check if we already have the definition of this mask
        if (!xarVarIsCached('Security.Masks',$name)) {
//Set up the query and get the data from the xarmasks table
            $query = "SELECT * FROM $this->maskstable
                        WHERE xar_name= '$name'";
            $result = $this->dbconn->Execute($query);
            if (!$result) return;
            if ($result->EOF) return false;

// reorganize the data into an array and create the masks object
            list($sid, $name, $realm, $module, $component, $instance, $level,$description) = $result->fields;
            $result->Close();
            $pargs = array('sid' => $sid,
                           'name' => $name,
                           'realm' => $realm,
                           'module' => $module,
                           'component' => $component,
                           'instance' => $instance,
                           'level' => $level,
                           'description'=>$description);
// done
            xarVarSetCached('Security.Masks',$name,$pargs);
        } else {
            $pargs = xarVarGetCached('Security.Masks',$name);
        }
        return new xarMask($pargs);
    }
}


/**
 * xarPrivileges: class for the privileges repository
 *
 * Represents the repository containing all privileges
 * The constructor is the constructor of the parent object
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @throws  none
 * @todo    none
*/

class xarPrivileges extends xarMasks
{

/**
 * defineInstance: define how a module's instances are registered
 *
 * Creates an entry in the instances table
 * This function should be invoked at module initialisation time
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   array of values to register instance
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function defineInstance($module,$type,$instances,$propagate=0,$table2='',$childID='',$parentID='',$description='')
    {
        foreach($instances as $instance) {
            $nextID = $this->dbconn->genID($this->instancestable);
            $nextIDprep = xarVarPrepForStore($nextID);
            $moduleprep = xarVarPrepForStore($module);
            $typeprep = xarVarPrepForStore($type);
            $headerprep = xarVarPrepForStore($instance['header']);
            $queryprep = xarVarPrepForStore($instance['query']);
            $limitprep = xarVarPrepForStore($instance['limit']);
            $propagateprep = xarVarPrepForStore($propagate);
            $table2prep = xarVarPrepForStore($table2);
            $childIDprep = xarVarPrepForStore($childID);
            $parentIDprep = xarVarPrepForStore($parentID);
            $descriptionprep = xarVarPrepForStore($description);
            $query = "INSERT INTO $this->instancestable
                                                    VALUES ($nextIDprep,
                                                    '$moduleprep',
                                                    '$typeprep',
                                                    '$headerprep',
                                                    '$queryprep',
                                                    '$limitprep',
                                                    $propagateprep,
                                                    '$table2prep',
                                                    '$childIDprep',
                                                    '$parentIDprep',
                                                    '$descriptionprep')";
            if (!$this->dbconn->Execute($query)) return;
        }
        return true;
    }

/**
 * removeInstances: remove the instances registered by a module form the database
 * *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   module name
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function removeInstances($module)
    {
        $query = "DELETE FROM $this->instancestable
              WHERE xar_module = '$module'";
        //Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query)) return;
        return true;
    }

/**
 * register: register a privilege
 *
 * Creates an entry in the privileges table
 * This function should be invoked every time a new instance is created
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   array of privilege values
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function register($name,$realm,$module,$component,$instance,$level,$description='')
    {
        $nextID = $this->dbconn->genID($this->privilegestable);
        $nextIDprep = xarVarPrepForStore($nextID);
        $nameprep = xarVarPrepForStore($name);
        $realmprep = xarVarPrepForStore($realm);
        $moduleprep = xarVarPrepForStore($module);
        $componentprep = xarVarPrepForStore($component);
        $instanceprep = xarVarPrepForStore($instance);
        $levelprep = xarVarPrepForStore($level);
        $descriptionprep = xarVarPrepForStore($description);
        $query = "INSERT INTO $this->privilegestable VALUES ($nextIDprep,
                                                '$nameprep',
                                                '$realmprep',
                                                '$moduleprep',
                                                '$componentprep',
                                                '$instanceprep',
                                                $levelprep,
                                                '$descriptionprep')";
        if (!$this->dbconn->Execute($query)) return;
        return true;
    }

/**
 * assign: assign a privilege to a user/group
 *
 * Creates an entry in the acl table
 * This is a convenience function that can be used by module developers
 * Note the input params are strings to make it easier.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string
 * @param   string
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function assign($privilegename,$rolename)
    {

// get the ID of the privilege to be assigned
        $privilege = $this->findPrivilege($privilegename);
        $privid = $privilege->getID();

// get the Roles class
        $roles = new xarRoles();

// find the role for the assignation and get its ID
        $role = $roles->findRole($rolename);
        $roleid = $role->getID();

// Add the assignation as an entry to the acl table
        $query = "INSERT INTO $this->acltable VALUES ($roleid,
                                                $privid)";
        if (!$this->dbconn->Execute($query)) return;

// empty the privset cache
//        $this->forgetprivsets();

        return true;
    }

/**
 * getprivileges: returns all the current privileges.
 *
 * Returns an array of all the privileges in the privileges repository
 * The repository contains an entry for each privilege.
 * This function will initially load the privileges from the db into an array and return it.
 * On subsequent calls it just returns the array .
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  array of privileges
 * @throws  none
 * @todo    none
*/
    function getprivileges() {
    if ((!isset($allprivileges)) || count($allprivileges)==0) {
            $query = "SELECT p.xar_pid,
                        p.xar_name,
                        p.xar_realm,
                        p.xar_module,
                        p.xar_component,
                        p.xar_instance,
                        p.xar_level,
                        p.xar_description,
                        pm.xar_parentid
                        FROM $this->privilegestable p INNER JOIN $this->privmemberstable pm
                        ON p.xar_pid = pm.xar_pid
                        ORDER BY p.xar_name";

            $result = $this->dbconn->Execute($query);
            if (!$result) return;

            $privileges = array();
            while(!$result->EOF) {
                list($pid, $name, $realm, $module, $component, $instance, $level,
                        $description,$parentid) = $result->fields;
                $privileges[] = array('pid' => $pid,
                                   'name' => $name,
                                   'realm' => $realm,
                                   'module' => $module,
                                   'component' => $component,
                                   'instance' => $instance,
                                   'level' => $level,
                                   'description' => $description,
                                   'parentid' => $parentid);
                $result->MoveNext();
            }
            $allprivileges = $privileges;
            return $privileges;
        }
        else {
            return $allprivileges;
        }
    }

/**
 * gettoplevelprivileges: returns all the current privileges that have no parent.
 *
 * Returns an array of all the privileges in the privileges repository
 * that are top level entries, i.e. have no parent
 * This function will initially load the privileges from the db into an array and return it.
 * On subsequent calls it just returns the array .
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string $arg indicates what types of elements to get
 * @return  array of privileges
 * @throws  none
 * @todo    none
*/
    function gettoplevelprivileges($arg) {
//    if ((!isset($alltoplevelprivileges)) || count($alltoplevelprivileges)==0) {
        if($arg == "all") {
             $fromclause = "FROM $this->privilegestable p,$this->privmemberstable pm
                        WHERE p.xar_pid = pm.xar_pid
                        AND pm.xar_parentid = 0
                        ORDER BY p.xar_name";
        }
        elseif ($arg == "assigned"){
             $fromclause = "FROM $this->privilegestable p,$this->privmemberstable pm,
                            $this->acltable acl
                            WHERE p.xar_pid = pm.xar_pid
                            AND p.xar_pid = acl.xar_permid
                            AND pm.xar_parentid = 0
                            ORDER BY p.xar_name";
        }
            $query = "SELECT p.xar_pid,
                        p.xar_name,
                        p.xar_realm,
                        p.xar_module,
                        p.xar_component,
                        p.xar_instance,
                        p.xar_level,
                        p.xar_description,
                        pm.xar_parentid ";
            $query .= $fromclause;
            $result = $this->dbconn->Execute($query);
            if (!$result) return;

            $privileges = array();
            $pids = array();
            while(!$result->EOF) {
                list($pid, $name, $realm, $module, $component, $instance, $level,
                        $description,$parentid) = $result->fields;
                $thisone = $pid;
                if (!in_array($thisone,$pids)){
                    $pids[] = $thisone;
                    $privileges[] = array('pid' => $pid,
                                       'name' => $name,
                                       'realm' => $realm,
                                       'module' => $module,
                                       'component' => $component,
                                       'instance' => $instance,
                                       'level' => $level,
                                       'description' => $description,
                                       'parentid' => $parentid);
                }
                $result->MoveNext();
            }
            $alltoplevelprivileges = $privileges;
            return $privileges;
//        }
//        else {
//            return $alltoplevelprivileges;
//        }
    }

/**
 * getrealms: returns all the current realms.
 *
 * Returns an array of all the realms in the realms table
 * They are used to populate dropdowns in displays
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  array of realm ids and names
 * @throws  none
 * @todo    this isn't really the right place for this function
*/
    function getrealms() {
    if ((!isset($allrealms)) || count($allrealms)==0) {
            $query = "SELECT xar_rid,
                            xar_name
                        FROM $this->realmstable";

            $result = $this->dbconn->Execute($query);
            if (!$result) return;

// add some extra lines we want
            $realms = array();
//          $realms[] = array('rid' => -2,
//                             'name' => ' ');
            $realms[] = array('rid' => -1,
                               'name' => 'All');
//          $realms[] = array('rid' => 0,
//                             'name' => 'None');

// add the realms from the database
// TODO: maybe remove the key, don't really need it
            $ind = 2;
            while(!$result->EOF) {
                list($rid, $name) = $result->fields;
                $realms[] = array('rid' => $rid,
                                   'name' => $name);
                $result->MoveNext();
            }
            $allrealms = $realms;
            return $realms;
        }
        else {
            return $allrealms;
        }
    }

/**
 * getmodules: returns all the current modules.
 *
 * Returns an array of all the modules in the modules table
 * They are used to populate dropdowns in displays
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  array of module ids and names
 * @throws  none
 * @todo    this isn't really the right place for this function
*/
    function getmodules() {
    if ((!isset($allmodules)) || count($allmodules)==0) {
            $query = "SELECT modules.xar_id,
                        modules.xar_name
                        FROM $this->modulestable AS modules LEFT JOIN $this->modulestatestable AS states
                        ON modules.xar_regid = states.xar_regid
                        WHERE states.xar_state = 3
                        ORDER BY modules.xar_name";

            $result = $this->dbconn->Execute($query);
            if (!$result) return;

// add some extra lines we want
            $modules = array();
//          $modules[] = array('id' => -2,
//                             'name' => ' ');
            $modules[] = array('id' => -1,
                               'name' => 'All');
//          $modules[] = array('id' => 0,
//                             'name' => 'None');

// add the modules from the database
// TODO: maybe remove the key, don't really need it
            while(!$result->EOF) {
                list($mid, $name) = $result->fields;
                $modules[] = array('id' => $mid,
                                   'name' => ucfirst($name));
                $result->MoveNext();
            }
            $allmodules = $modules;
            return $modules;
        }
        else {
            return $allmodules;
        }
    }

/**
 * getcomponents: returns all the current components of a module.
 *
 * Returns an array of all the components that have been registered for a given module.
 * The components correspond to masks in the masks table. Each one can be used to
 * construct a privilege's xarSecurityCheck.
 * They are used to populate dropdowns in displays
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string with module name
 * @return  array of component ids and names
 * @throws  none
 * @todo    this isn't really the right place for this function
*/
    function getcomponents($module) {
        $query = "SELECT DISTINCT xar_component
                    FROM $this->instancestable
                    WHERE xar_module= '$module'
                    ORDER BY xar_component";

        $result = $this->dbconn->Execute($query);
        if (!$result) return;

        $components = array();
        if ($module ==''){
            $components[] = array('id' => -2,
                               'name' => 'All');
        }
        elseif(count($result->fields) == 0) {
            $components[] = array('id' => -1,
                               'name' => 'All');
//          $components[] = array('id' => 0,
//                             'name' => 'None');
        }
        else {
            $components[] = array('id' => -1,
                               'name' => 'All');
//          $components[] = array('id' => 0,
//                             'name' => 'None');
            $ind = 2;
            while(!$result->EOF) {
                list($name) = $result->fields;
                if (($name != 'All') && ($name != 'None')){
                    $ind = $ind + 1;
                    $components[] = array('id' => $name,
                                       'name' => $name);
                }
                $result->MoveNext();
            }
        }
        return $components;
    }

/**
 * getinstances: returns all the current instances of a module.
 *
 * Returns an array of all the instances that have been defined for a given module.
 * The instances for each module are registered at initialization.
 * They are used to populate dropdowns in displays
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string with module name
 * @return  array of instance ids and names for the module
 * @throws  none
 * @todo    this isn't really the right place for this function
*/
    function getinstances($module, $component) {


        if ($component =="All") {
            $componentstring = "";
        }
        else {
            $componentstring = "AND ";
        }
        $query = "SELECT xar_header, xar_query, xar_limit
                    FROM $this->instancestable
                    WHERE xar_module= '$module' AND xar_component= '$component'
                     ORDER BY xar_component,xar_iid";

        $instances = array();
        $result = $this->dbconn->Execute($query);
        if (!$result) return;

        while(!$result->EOF) {
            list($header,$selection,$limit) = $result->fields;

// Check if an external instance wizard is requested, if so redirect using the URL in the 'query' part
// This is indicated by the keyword 'external' in the 'header' of the instance definition
            if ($header == 'external') {
                return array('external' => 'yes',
                             'target'   => $selection);
            }

// check if the query is there
            if ($selection =='') {
                $msg = xarML('A query is missing in component ' . $component . ' of module '. $module);
                xarExceptionSet(XAR_USER_EXCEPTION, 'NO_QUERY',
                               new DefaultUserException($msg));
                return;
            }

            $result1 = $this->dbconn->Execute($selection);
            if (!$result1) return;

            $dropdown = array();
            if ($module ==''){
                $dropdown[] = array('id' => -2,
                                   'name' => '');
            }
            elseif($result->EOF) {
                $dropdown[] = array('id' => -1,
                                   'name' => 'All');
    //          $dropdown[] = array('id' => 0,
    //                             'name' => 'None');
            }
            else {
                $dropdown[] = array('id' => -1,
                                   'name' => 'All');
    //          $dropdown[] = array('id' => 0,
    //                             'name' => 'None');
            }
            while(!$result1->EOF) {
                list($dropdownline) = $result1->fields;
                if (($dropdownline != 'All') && ($dropdownline != 'None')){
                    $dropdown[] = array('id' => $dropdownline,
                                       'name' => $dropdownline);
                }
                $result1->MoveNext();
            }

            if (count($dropdown) > $limit) {
                $type = "manual";
            }
            else {
                $type = "dropdown";
            }
            $instances[] = array('header' => $header,
                                'dropdown' => $dropdown,
                                'type' => $type
                                );
            $result->MoveNext();
        }

        return $instances;
    }

    function getprivilegefast($pid){
        foreach($this->getprivileges() as $privilege){
            if ($privilege['pid'] == $pid) return $privilege;
        }
        return false;
    }

    function getsubprivileges($pid){
        $subprivileges = array();
        $ind = 0;
        foreach($this->getprivileges() as $subprivilege){
            if ($subprivilege['parentid'] == $pid) {
                $ind = $ind + 1;
                $subprivileges[$ind] = $subprivilege;
            }
        }
        return $subprivileges;
    }

/**
 * returnPrivilege: adds or modifies a privilege coming from an external wizard .
 *
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   strings with pid, name, realm, module, component, instances and level
 * @return  mixed pid if OK, void if not
*/
    function returnPrivilege($pid,$name,$realm,$module,$component,$instances,$level) {

        $instance = "";
        foreach ($instances as $inst) {
            $instance .= $inst . ":";
        }
        if ($instance =="") {
            $instance = "All";
        }
        else {
            $instance = substr($instance,0,strlen($instance)-1);
        }

        if($pid==0) {
            $pargs = array('name' => $name,
                        'realm' => $realm,
                        'module' => $module,
                        'component' => $component,
                        'instance' => $instance,
                        'level' => $level,
                        'parentid' => 0
                        );
            $priv = new xarPrivilege($pargs);
            if ($priv->add()) {
                return $priv->getID();
            }
            return;
        }
        else {
            $privs = new xarPrivileges();
            $priv = $privs->getPrivilege($pid);
            $priv->setName($name);
            $priv->setRealm($realm);
            $priv->setModule($module);
            $priv->setComponent($component);
            $priv->setInstance($instance);
            $priv->setLevel($level);
            if ($priv->update()) {
                return $priv->getID();
            }
            return;
        }
    }

/**
 * getPrivilege: gets a single privilege
 *
 * Retrieves a single privilege object from the Privileges repository
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   integer
 * @return  privilege object
 * @throws  none
 * @todo    none
*/
    function getPrivilege($pid)
    {
        $query = "SELECT *
                  FROM $this->privilegestable
                  WHERE xar_pid = $pid";
        //Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query);
        if (!$result) return;
        if (!$result->EOF) {
            list($pid,$name,$realm,$module,$component,$instance,$level,$description) = $result->fields;
            $pargs = array('pid'=>$pid,
                           'name'=>$name,
                           'realm'=>$realm,
                           'module'=>$module,
                           'component'=>$component,
                           'instance'=>$instance,
                           'level'=>$level,
                           'description'=>$description,
                           'parentid'=>0);
            return new xarPrivilege($pargs);
        }
        return;
    }

/**
 * findPrivilege: finds a single privilege based on its name
 *
 * Retrieves a single privilege object from the Privileges repository
 * This is a convenience class for module developers
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string
 * @return  privilege object
 * @throws  none
 * @todo    none
*/
    function findPrivilege($name)
    {
        $query = "SELECT *
                  FROM $this->privilegestable
                  WHERE xar_name = '$name'";
        //Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query);
        if (!$result) return;
        if (!$result->EOF) {
            list($pid,$name,$realm,$module,$component,$instance,$level,$description) = $result->fields;
            $pargs = array('pid'=>$pid,
                           'name'=>$name,
                           'realm'=>$realm,
                           'module'=>$module,
                           'component'=>$component,
                           'instance'=>$instance,
                           'level'=>$level,
                           'description'=>$description,
                           'parentid'=>0);
            return new xarPrivilege($pargs);
        }
        return;
    }

/**
 * makeMember: makes a privilege a child of another privilege
 *
 * Creates an entry in the privmembers table
 * This is a convenience class for module developers
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string
 * @param   string
 * @return  boolean
 * @throws  none
 * @todo    create exceptions for bad input
*/
    function makeMember($childname,$parentname)
    {
// get the data for the parent object
        $query = "SELECT *
                  FROM $this->privilegestable
                  WHERE xar_name = '$parentname'";
        //Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query);
        if (!$result) return;

// create the parent object
        list($pid,$name,$realm,$module,$component,$instance,$level,$description) = $result->fields;
        $pargs = array('pid'=>$pid,
                        'name'=>$name,
                        'realm'=>$realm,
                        'module'=>$module,
                        'component'=>$component,
                        'instance'=>$instance,
                        'level'=>$level,
                        'description'=>$description,
                        'parentid'=>0);
        $parent =  new xarPrivilege($pargs);

// get the data for the child object
        $query = "SELECT *
                  FROM $this->privilegestable
                  WHERE xar_name = '$childname'";
        //Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query);
        if (!$result) return;

// create the child object
        list($pid,$name,$realm,$module,$component,$instance,$level,$description) = $result->fields;
        $pargs = array('pid'=>$pid,
                        'name'=>$name,
                        'realm'=>$realm,
                        'module'=>$module,
                        'component'=>$component,
                        'instance'=>$instance,
                        'level'=>$level,
                        'description'=>$description,
                        'parentid'=>0);
        $child =  new xarPrivilege($pargs);

// done
        return $parent->addMember($child);
    }

/**
 * makeEntry: defines a top level entry of the privileges hierarchy
 *
 * Creates an entry in the privmembers table
 * This is a convenience class for module developers
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string
 * @return  boolean
 * @throws  none
 * @todo    create exceptions for bad input
*/
    function makeEntry($rootname)
    {
// get the data for the root object
        $query = "SELECT xar_pid
                  FROM $this->privilegestable
                  WHERE xar_name = '$rootname'";
        //Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query);
        if (!$result) return;

// create the entry
        list($pid) = $result->fields;
        $query = "INSERT INTO $this->privmemberstable
                VALUES ($pid,0)";
        //Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query)) return;

// done
        return true;
    }

}

/**
 * xarMask: class for the mask object
 *
 * Represents a single security mask
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @throws  none
 * @todo    none
*/

  class xarMask
{
    var $sid;           //the id of this privilege
    var $name;          //the name of this privilege
    var $realm;         //the realm of this privilege
    var $module;        //the module of this privilege
    var $component;     //the component of this privilege
    var $instance;      //the instance of this privilege
    var $level;         //the access level of this privilege
    var $description;   //the long description of this privilege

    var $dbconn;
    var $privilegestable;
    var $privmemberstable;

/**
 * xarMask: constructor for the class
 *
 * Creates a security mask
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   array of values
 * @return  mask
 * @throws  none
 * @todo    none
*/

    function xarMask($pargs)
    {
        extract($pargs);

        list($this->dbconn) = xarDBGetConn();
        $xartable = xarDBGetTables();
        $this->privilegestable = $xartable['privileges'];
        $this->privmemberstable = $xartable['privmembers'];
        $this->rolestable = $xartable['roles'];
        $this->acltable = $xartable['security_acl'];

        $this->sid          = $sid;
        $this->name         = $name;
        $this->realm        = $realm;
        $this->module       = $module;
        $this->component    = $component;
        $this->instance     = $instance;
        $this->level        = $level;
        $this->description  = $description;
    }

    function present() {
        $display = $this->getName();
        $display .= "-" . strtolower($this->getLevel());
        $display .= ":" . strtolower($this->getRealm());
        $display .= ":" . strtolower($this->getModule());
        $display .= ":" . strtolower($this->getComponent());
        $display .= ":" . strtolower($this->getInstance());
        return $display;
    }

/**
 * normalize: creates a "normalized" array representing a mask
 *
 * Returns an array of strings representing a mask
 * The array can be used for comparisons with other masks
 * The function optionally adds "all"'s to the end of a normalized mask representation
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  array of strings
 * @throws  none
 * @todo    none
*/
    function normalize($adds=0) {
        $normalform = array();
        $normalform[] = strtolower($this->getLevel());
        $normalform[] = strtolower($this->getRealm());
        $normalform[] = strtolower($this->getModule());
        $normalform[] = strtolower($this->getComponent());
        if (strtolower($this->getInstance()) == "All") {
            $normalform[] = strtolower($this->getInstance());
        }
        else {
            $normalform = array_merge($normalform,explode(':',strtolower($this->getInstance())));
        }
        for ($i=0;$i<$adds;$i++) $normalform[] = 'all';
        return $normalform;
    }

/**
 * canonical: returns 2 normalized privileges or masks that can be compared
 *
 * Returns two normalized privileges or masks with an equal number of elements
 * The 2 can be compared element by element
 * The function does a tiny bit of error checking to make sure the 2 are well formed.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   mask object
 * @return  array 2 normalized masks
 * @throws  none
 * @todo    none
*/
    function canonical($mask)
    {
        $p1 = $this->normalize();
        $p2 = $mask->normalize();

        return array($p1,$p2);
    }

/**
 * matches: checks the structure of one privilege against another
 *
 * Checks whether two privileges, or a privilege and a mask, are equal
 * in all respects except for the access level
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   mask object
 * @return  boolean
 * @throws  none
 * @todo    none
*/

    function matches($mask)
    {
        list($p1,$p2) = $this->canonical($mask);
        $match = true;
        for ($i=1;$i<count($p1);$i++) $match = $match && ($p1[$i]==$p2[$i]);
//        echo $this->present() . $mask->present() . $match;exit;
        return $match;
    }

/**
 * matches: checks the structure of one privilege against another
 *
 * Checks whether two privileges, or a privilege and a mask, are equal
 * in all respects
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   mask object
 * @return  boolean
 * @throws  none
 * @todo    none
*/

    function matchesexactly($mask)
    {
        $match = $this->matches($mask);
        return $match && ($this->getLevel() == $mask->getLevel());
    }

/**
 * includes: checks the structure of one privilege against another
 *
 * Checks a mask has the same or larger range than another mask
 *
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   mask object
 * @return  boolean
 * @throws  none
 * @todo    none
*/

    function includes($mask)
    {
        list($p1,$p2) = $this->canonical($mask);
        $match = true;

// match realm, module and component. bail if no match.
        for ($i=1;$i<4;$i++) $match = $match && (($p1[$i]==$p2[$i]) || ($p1[$i] == 'all'));

        if($match) {
// now match the instances
            if(count($p1) != count($p2)) {
                if(count($p1) > count($p2)) {
                    $p = $p2;
                    $p2 = $mask->normalize(count($p1) - count($p2));
                }
                else {
                    $p = $p1;
                    $p1 = $this->normalize(count($p2) - count($p1));
                }
                if (count($p) != 5) {
                    $msg = xarML('#(1) and #(2) do not have the same instances. #(3) | #(4) | #(5)',$mask->getName(),$this->getName(),implode(',',$p2),implode(',',$p1),$this->present() . "|" . $mask->present());
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                   new SystemException($msg));
                }
            }
            for ($i=4;$i<count($p1);$i++) $match = $match && (($p1[$i]==$p2[$i]) || ($p1[$i] == 'all'));
        }
        return $match;
    }

/**
 * implies: checks the structure of one privilege against another
 *
 * Checks a mask has the same or larger range, and the same or higher access right,
 * than another mask
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   mask object
 * @return  boolean
 * @throws  none
 * @todo    none
*/

    function implies($mask)
    {
        $match = $this->includes($mask);
        return $match && ($this->getLevel() >= $mask->getLevel()) && ($mask->getLevel() > 0);
    }

    function getID()              {return $this->sid;}
    function getName()            {return $this->name;}
    function getRealm()           {return $this->realm;}
    function getModule()          {return $this->module;}
    function getComponent()       {return $this->component;}
    function getInstance()        {return $this->instance;}
    function getLevel()           {return $this->level;}
    function getDescription()     {return $this->description;}

    function setName($var)        {$this->name = $var;}
    function setRealm($var)       {$this->realm = $var;}
    function setModule($var)      {$this->module = $var;}
    function setComponent($var)   {$this->component = $var;}
    function setInstance($var)    {$this->instance = $var;}
    function setLevel($var)       {$this->level = $var;}
    function setDescription($var) {$this->description = $var;}

}


/**
 * xarPrivilege: class for the privileges object
 *
 * Represents a single privileges object
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @throws  none
 * @todo    none
*/

class xarPrivilege extends xarMask
{

    var $pid;           //the id of this privilege
    var $name;          //the name of this privilege
    var $realm;         //the realm of this privilege
    var $module;        //the module of this privilege
    var $component;     //the component of this privilege
    var $instance;      //the instance of this privilege
    var $level;         //the access level of this privilege
    var $description;   //the long description of this privilege
    var $parentid;      //the pid of the parent of this privilege

    var $dbconn;
    var $privilegestable;
    var $privmemberstable;

/**
 * xarPrivilege: constructor for the class
 *
 * Just sets up the db connection and initializes some variables
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   array of values
 * @return  the privilege object
 * @throws  none
 * @todo    none
*/
    function xarPrivilege($pargs)
    {
        extract($pargs);

        list($this->dbconn) = xarDBGetConn();
        $xartable = xarDBGetTables();
        $this->privilegestable = $xartable['privileges'];
        $this->privmemberstable = $xartable['privmembers'];
        $this->rolestable = $xartable['roles'];
        $this->acltable = $xartable['security_acl'];

// CHECKME: pid and description are undefined when adding a new privilege
        if (empty($pid)) {
            $pid = 0;
        }
        if (empty($description)) {
            $description = '';
        }

        $this->pid          = $pid;
        $this->name         = $name;
        $this->realm        = $realm;
        $this->module       = $module;
        $this->component    = $component;
        $this->instance     = $instance;
        $this->level        = $level;
        $this->description  = $description;
        $this->parentid     = $parentid;
    }

/**
 * add: add a new privileges object to the repository
 *
 * Creates an entry in the repository for a privileges object that has been created
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
   function add(){

        if(empty($this->name)) {
            $msg = xarML('You must enter a name.',
                        'privileges');
            xarExceptionSet(XAR_USER_EXCEPTION,
                        'DUPLICATE_DATA',
                         new DefaultUserException($msg));
            xarSessionSetVar('errormsg', _MODARGSERROR);
            return false;
        }


// Confirm that this privilege name does not already exist
        $query = "SELECT COUNT(*) FROM $this->privilegestable
              WHERE xar_name = '$this->name'";

        $result = $this->dbconn->Execute($query);
        if (!$result) return;

        list($count) = $result->fields;

        if ($count == 1) {
            $msg = xarML('This entry already exists.',
                        'privileges');
            xarExceptionSet(XAR_USER_EXCEPTION,
                        'DUPLICATE_DATA',
                         new DefaultUserException($msg));
            xarSessionSetVar('errormsg', _GROUPALREADYEXISTS);
            return;
        }

// set up the variables for inserting the object into the repository
            $nextId = $this->dbconn->genID($this->privilegestable);

            $nextIdprep = xarVarPrepForStore($nextId);
            $nameprep = xarVarPrepForStore($this->name);
            $realmprep = xarVarPrepForStore($this->realm);
            $moduleprep = xarVarPrepForStore($this->module);
            $componentprep = xarVarPrepForStore($this->component);
            $instanceprep = xarVarPrepForStore($this->instance);
            $levelprep = xarVarPrepForStore($this->level);

// create the insert query
        $query = "INSERT INTO $this->privilegestable
                    (xar_pid, xar_name, xar_realm, xar_module, xar_component, xar_instance, xar_level)
                  VALUES ($nextIdprep, '$nameprep', '$realmprep', '$moduleprep', '$componentprep', '$instanceprep', $levelprep)";
        //Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query)) return;

// the insert created a new index value
// retrieve the value
        $query = "SELECT MAX(xar_pid) FROM $this->privilegestable";
        //Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query);
        if (!$result) return;

// use the index to get the privileges object created from the repository
        list($pid) = $result->fields;
        $this->pid = $pid;

// make this privilege a child of its parent
        If($this->parentid !=0) {
            $perms = new xarPrivileges();
            $parentperm = $perms->getprivilege($this->parentid);
            $parentperm->addMember($this);
        }
// create this privilege as an entry in the repository
        return $this->makeEntry();
    }

/**
 * makeEntry: sets up a privilege without parents
 *
 * Sets up a privilege as a root entry (no parent)
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    check to make sure the child is not a parent of the parent
*/
    function makeEntry() {

        $query = "INSERT INTO $this->privmemberstable
                VALUES (" . $this->getID() . ",0)";
        //Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query)) return;
        return true;
    }

/**
 * addMember: adds a privilege to a privilege
 *
 * Make a privilege a member of another privilege.
 * A privilege can have any number of parents or children..
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   privilege object
 * @return  boolean
 * @throws  none
 * @todo    check to make sure the child is not a parent of the parent
*/
    function addMember($member) {

        $query = "INSERT INTO $this->privmemberstable
                VALUES (" . $member->getID() . "," . $this->getID() . ")";
        //Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query)) return;

// empty the privset cache
//        $privileges = new xarPrivileges();
//        $privileges->forgetprivsets();

        return true;
    }

/**
 * removeMember: removes a privilege from a privilege
 *
 * Removes a privilege as an entry of another privilege.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function removeMember($member) {

        $query = "DELETE FROM $this->privmemberstable
              WHERE xar_pid=" . $member->getID() .
              " AND xar_parentid=" . $this->getID();
        //Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query)) return;

// empty the privset cache
//        $privileges = new xarPrivileges();
//        $privileges->forgetprivsets();

        return true;
    }

/**
 * update: updates a privilege in the repository
 *
 * Updates a privilege in the privileges repository
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function update()
    {
        $query =    "UPDATE " . $this->privilegestable .
                    " SET " .
                    "xar_name = '$this->name'," .
                    "xar_realm = '$this->realm'," .
                    "xar_module = '$this->module'," .
                    "xar_component = '$this->component'," .
                    "xar_instance = '$this->instance'," .
                    "xar_level = $this->level" .
                    " WHERE xar_pid = " . $this->getID();

        //Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query)) return;
        return true;
    }

/**
 * remove: deletes a privilege in the repository
 *
 * Deletes a privilege's entry in the privileges repository
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function remove(){

// set up the DELETE query
        $query = "DELETE FROM $this->privilegestable
              WHERE xar_pid=" . $this->pid;
//Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query)) return;

// set up a query to get all the parents of this child
        $query = "SELECT xar_parentid FROM $this->privmemberstable
              WHERE xar_pid=" . $this->getID();
        //Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query);
        if (!$result) return;

// remove this child from all the parents
        $perms = new xarPrivileges();
        while(!$result->EOF) {
            list($parentid) = $result->fields;
            if ($parentid != 0) {
                $parentperm = $perms->getPrivilege($parentid);
                $parentperm->removeMember($this);
            }
            $result->MoveNext();
        }
        return true;
    }

/**
 * isassigned: check if the current privilege is assigned to a role
 *
 * This function looks at the acl table and returns true if the current privilege.
 * is assigned to a given role .
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   role object
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function isassigned($role)
    {
        $query = "SELECT xar_partid FROM $this->acltable WHERE
                xar_partid = " . $role->getID() .
                " AND xar_permid = " . $this->getID();
        $result = $this->dbconn->Execute($query);
        if (!$result) return;
        return !$result->EOF;
    }

/**
 * getRoles: returns an array of roles
 *
 * Returns an array of roles this privilege is assigned to
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function getRoles(){

// set up a query to select the roles this privilege
// is linked to in the acl table
        $query = "SELECT r.xar_uid,
                    r.xar_name,
                    r.xar_type,
                    r.xar_uname,
                    r.xar_email,
                    r.xar_pass,
                    r.xar_auth_module
                    FROM $this->rolestable r, $this->acltable acl
                    WHERE r.xar_uid = acl.xar_partid
                    AND acl.xar_permid = $this->pid";
//Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query);
        if (!$result) return;

// make objects from the db entries retrieved
        include_once 'modules/roles/xarroles.php';
        $roles = array();
//      $ind = 0;
        while(!$result->EOF) {
            list($uid,$name,$type,$uname,$email,$pass,$auth_module) = $result->fields;
//          $ind = $ind + 1;
            $role = new xarRole(array('uid' => $uid,
                               'name' => $name,
                               'type' => $type,
                               'uname' => $uname,
                               'email' => $email,
                               'pass' => $pass,
                               'auth_module' => $auth_module,
                               'parentid' => 0));
            $result->MoveNext();
            $roles[] = $role;
        }
// done
        return $roles;
    }

/**
 * removeRole: removes a role
 *
 * Removes a role this privilege is assigned to
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   role object
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function removeRole($role) {

// use the equivalent method from the roles object
        return $role->removePrivilege($this);
    }

/**
 * getParents: returns the parent objects of a privilege
 *
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  array of privilege objects
 * @throws  none
 * @todo    none
*/
    function getParents()
    {
// create an array to hold the objects to be returned
        $parents = array();

// if this is a user just perform a SELECT on the rolemembers table
        $query = "SELECT p.*, pm.xar_parentid
                    FROM $this->privilegestable p INNER JOIN $this->privmemberstable pm
                    ON p.xar_pid = pm.xar_parentid
                    WHERE pm.xar_pid = " . $this->getID();
        $result = $this->dbconn->Execute($query);
        if (!$result) return;

// collect the table values and use them to create new role objects
        $ind = 0;
            while(!$result->EOF) {
            list($pid,$name,$realm,$module,$component,$instance,$level,$description,$parentid) = $result->fields;
            $pargs = array('pid'=>$pid,
                            'name'=>$name,
                            'realm'=>$realm,
                            'module'=>$module,
                            'component'=>$component,
                            'instance'=>$instance,
                            'level'=>$level,
                            'description'=>$description,
                            'parentid' => $parentid);
            $ind = $ind + 1;
            array_push($parents, new xarPrivilege($pargs));
            $result->MoveNext();
            }
// done
        return $parents;
    }

/**
 * getAncestors: returns all objects in the privileges hierarchy above a privilege
 *
 * The returned privileges are automatically winnowed
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  array of privilege objects
 * @throws  none
 * @todo    none
*/
    function getAncestors()
    {
// if this is the root return an empty array
        if ($this->getID() == 1) return array();

// start by getting an array of the parents
        $parents = $this->getParents();

//Get the parent field for each parent
        $masks = new xarMasks();
        while (list($key, $parent) = each($parents)) {
            $ancestors = $parent->getParents();
            foreach ($ancestors as $ancestor) {
                array_push($parents,$ancestor);
            }
        }

//done
        $ancestors = array();
        $parents = $masks->winnow($ancestors,$parents);
        return $ancestors;
    }

/**
 * getChildren: returns the child objects of a privilege
 *
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  array of privilege objects
 * @throws  none
 * @todo    none
*/
    function getChildren()
    {
// create an array to hold the objects to be returned
        $children = array();

// if this is a user just perform a SELECT on the rolemembers table
        $query = "SELECT p.*, pm.xar_parentid
                    FROM $this->privilegestable p INNER JOIN $this->privmemberstable pm
                    ON p.xar_pid = pm.xar_pid
                    WHERE pm.xar_parentid = " . $this->getID();
        $result = $this->dbconn->Execute($query);
        if (!$result) return;

// collect the table values and use them to create new role objects
            while(!$result->EOF) {
            list($pid,$name,$realm,$module,$component,$instance,$level,$description,$parentid) = $result->fields;
            $pargs = array('pid'=>$pid,
                            'name'=>$name,
                            'realm'=>$realm,
                            'module'=>$module,
                            'component'=>$component,
                            'instance'=>$instance,
                            'level'=>$level,
                            'description'=>$description,
                            'parentid' => $parentid);
            array_push($children, new xarPrivilege($pargs));
            $result->MoveNext();
            }
// done
        return $children;
    }

/**
 * getDescendants: returns all objects in the privileges hierarchy below a privilege
 *
 * The returned privileges are automatically winnowed
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  array of privilege objects
 * @throws  none
 * @todo    none
*/
    function getDescendants()
    {
// start by getting an array of the parents
        $children = $this->getChildren();

//Get the child field for each child
        $masks = new xarMasks();
        while (list($key, $child) = each($children)) {
            $descendants = $child->getChildren();
            foreach ($descendants as $descendant) {
                $children[] =$descendant;
            }
        }

//done
        $descendants = array();
        $descendants = $masks->winnow($descendants,$children);
        return $descendants;
    }

/**
 * isEqual: checks whether two privileges are equal
 *
 * Two privilege objects are considered equal if they have the same pid.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function isEqual($privilege)
    {
        return $this->getID() == $privilege->getID();
    }

/**
 * getID: returns the ID of this privilege
 *
 * This overrides the method of the same name in the parent class
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function getID()              {return $this->pid;}

/**
 * isEmpty: returns the type of this privilege
 *
 * This methods returns true if the privilege is an empty container
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function isEmpty()              {return $this->module == 'empty';}
}
?>
