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
	var $realmstable;
	var $acltable;
	var $allmasks;
	var $levels;
	var $instancestable;

/**
 * xarMasks: constructor for the class
 *
 * Just sets up the db connection and initializes some variables
 * This should really be a static class
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param	none
 * @return  the masks object
 * @throws  none
 * @todo    none
*/
	function xarMasks() {
		list($this->dbconn) = xarDBGetConn();
		$xartable = xarDBGetTables();
		$this->privilegestable = $xartable['privileges'];
		$this->privmemberstable = $xartable['privmembers'];
		$this->maskstable = $xartable['masks'];
		$this->modulestable = $xartable['modules'];
		$this->realmstable = $xartable['realms'];
		$this->acltable = $xartable['acl'];
		$this->instancestable = $xartable['instances'];

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
				$query = "SELECT * FROM $this->maskstable ORDER BY xar_component, xar_name";
			}
			else {
				$query = "SELECT * FROM $this->maskstable
						WHERE (xar_component = '$component')
						OR (xar_component = 'All')
						OR (xar_component = 'None')
						ORDER BY xar_component, xar_namexar_name";
			}
		}
		else {
			if ($component == '' || $component == 'All') {
				$query = "SELECT * FROM $this->maskstable
						WHERE xar_module = '$module' ORDER BY xar_component, xar_name";
			}
			else {
			$query = "SELECT *
					FROM $this->maskstable WHERE (xar_module = '$module')
					AND ((xar_component = '$component')
					OR (xar_component = 'All')
					OR (xar_component = 'None'))
					ORDER BY xar_component, xar_name";
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
 	function winnow($perms1, $perms2)
	{
		if ((($perms1 == array()) || ($perms1 == '')) &&
			(($perms2 == array()) || ($perms2 == ''))) return array();
		if ($perms1 == array()) return $perms2;
		if ($perms2 == array()) return $perms1;

		foreach ($perms1 as $perm1) {
			$isimplied = false;
			foreach ($perms2 as $key=>$perm2) {
				if ($perm2->isEqual($perm1)) {
					$isimplied = true;
					break;
				}
				else if ($perm1->implies($perm2)) {
					array_splice($perms2,$key);
					array_push($perms2,$perm1);
					$isimplied = true;
					break;
				}
				else if ($perm2->implies($perm1)) {
					$isimplied = true;
					break;
				}
			}
			if (!$isimplied) array_push($perms2, $perm1);
		}

// done
		return $perms2;
	}

/**
 * trump: merges two arrays of privileges to a single array of privileges
 *
 * The privileges are compared for implication and the less recent are discarded.
 * The less recent are assumed to be in the first array
 * This is the way privileges hierarchies in role hierarchies are contracted.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   array of privileges objects
 * @param   array of privileges objects
 * @return  array of privileges objects
 * @throws  none
 * @todo    create exceptions for bad input
*/
 	function trump($perms1, $perms2)
	{
		if ((($perms1 == array()) || ($perms1 == '')) &&
			(($perms2 == array()) || ($perms2 == ''))) return array();
		if ($perms1 == array()) return $perms2;
		if ($perms2 == array()) return $perms1;

		foreach ($perms1 as $perm1) {
			$isimplied = false;
			foreach ($perms2 as $key=>$perm2) {
				if (($perm2->isEqual($perm1)) ||
					($perm1->implies($perm2)) ||
					($perm2->implies($perm1))) {
					array_splice($perms1,$key);
					array_push($perms1,$perm2);
					$isimplied = true;
					break;
				}
			if (!$isimplied) array_push($perms1, $perm2);
			}
		}

// done
		return $perms1;
	}

/**
 * securitycheck: check a role's privileges against the masks of a component
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

	function securitycheck($component,$showexception=1,$instancetype='', $instance='',$rolename='',$module='')
	{

// get the masks pertaining to the current module and the component requested
		if ($module == '') list($module) = xarRequestGetInfo();
		$masks =  $this->getMasks($module, $component);
		if ($masks == array()) {
			$msg = xarML('No masks registered for component name: ') . $component .
			xarML(' in module: ') . $module . xarML('. Check if the component and module names are correct.');
			xarExceptionSet(XAR_USER_EXCEPTION, 'NO_COMPONENT',
						   new DefaultUserException($msg));
        	return;
		}

// get the Roles class
		include_once 'modules/roles/xarroles.php';
    	$roles = new xarRoles();

// get the pid of the user we will check against
// an empty role means take the current user
// TODO: what if the id is a group?
		if ($rolename == '') {
			$userID = xarSessionGetVar('uid');
			if (empty($userID)) {
				$userID = _XARSEC_UNREGISTERED;
			}
			$role = $roles->getRole($userID);
		}
		else {
			$role = $roles->findRole($role);
		}

// get the inherited ancestors of the role
		$ancestors = $role->getAncestors();

// set up an array to hold the privileges
		$irreducibleset = array();

// if there are ancestors, look for their privileges
		if (count($ancestors) >0) {

// need to process the last ones first
			$ancestors = array_reverse($ancestors);
// set up a temporary array to hold results
			$final = array();

// begin with the guy at the top of the pyramid
			$top = $ancestors[0]->getLevel();

// begin processing an ancestor
			foreach ($ancestors as $ancestor) {

// get the ancestors assigned privileges
				$privs = $ancestor->getAssignedPrivileges();
				$privileges = array();
// for each one winnow the  assigned privileges and then the inherited
				foreach ($privs as $priv) {
					$privileges = $this->winnow(array($priv),$privileges);
					$privileges = $this->winnow($priv->getDescendants(),$privileges);
				}

// add some info on the group they belong to and stick it all in an array
				$groupname = $ancestor->getName();
				$grouplevel = $ancestor->getLevel();
				array_push($final,array('privileges'=>$privileges,
									'name'=>$groupname,
									'level'=>$grouplevel));
			}

// winnow all privileges of a given level above the role
				foreach ($final as $step) {
				if ($step['level']) {
					$irreducibleset = $this->winnow($irreducibleset,$step['privileges']);
				}

// or trump the previous privileges with those of a lower level
// TODO: this is a bug.Probably should winnow the lowerlevel and THEN trump against
// the higher level
				else {
					$irreducibleset = $this->trump($irreducibleset,$step['privileges']);
					$top = $step['level'];
				}
			}
		}

// get the assigned privileges and winnow them
			$roleprivileges = $role->getAssignedPrivileges();
			$roleprivileges = $this->winnow($roleprivileges,$roleprivileges);
// trump them against the accumulated privileges from higher levels
		$irreducibleset = $this->trump($irreducibleset,$roleprivileges);

// check each privilege from the irreducible set
		$pass = false;
		foreach ($irreducibleset as $chiave) {

// check against each mask defined for the component
			foreach ($masks as $mask) {
//			echo "Security check: " . $chiave->getName() . " " . $mask->getName() . " " .$chiave->implies($mask);
				if ($chiave->implies($mask)) {

// found a privilege that admits: return the privilege
				return $chiave;
				}
			}
		}
// nothing found: return false
// check if the exception needs to be caught here or not
		if ($showexception) {
        $msg = xarML('No privilege for modifying this item');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
		}
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
//Set up the query and get the data from the xarmasks table
		$query = "SELECT * FROM $this->maskstable
					WHERE xar_masks.xar_name= '$name'";
		$result = $this->dbconn->Execute($query);
		if (!$result) return;
//		if ($result->EOF) return array();

// reorganize the data into an array and create the masks object
		list($sid, $name, $realm, $module, $component, $instance, $level,$description) = $result->fields;
		$pargs = array('sid' => $sid,
							'name' => $name,
						   'realm' => $realm,
						   'module' => $module,
						   'component' => $component,
						   'instance' => $instance,
						   'level' => $level,
						   'description'=>$description);

// done
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
	function defineInstance($module,$table1,$valuefield,$displayfield,$propagate=0,$table2='',$childID='',$parentID='',$description='')
	{
		$nextID = $this->dbconn->genID($this->instancestable);
		$nextIDprep = xarVarPrepForStore($nextID);
		$moduleprep = xarVarPrepForStore($module);
		$table1prep = xarVarPrepForStore($table1);
		$valueprep = xarVarPrepForStore($valuefield);
		$displayprep = xarVarPrepForStore($displayfield);
		$propagateprep = xarVarPrepForStore($propagate);
		$table2prep = xarVarPrepForStore($table2);
		$childIDprep = xarVarPrepForStore($childID);
		$parentIDprep = xarVarPrepForStore($parentID);
		$descriptionprep = xarVarPrepForStore($description);
		$query = "INSERT INTO $this->instancestable VALUES ($nextIDprep,
												'$moduleprep',
												'$table1prep',
												'$valueprep',
												'$displayprep',
												$propagateprep,
												'$table2prep',
												'$childIDprep',
												'$parentIDprep',
												'$descriptionprep')";
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
		$nextID = $this->dbconn->genID($this->maskstable);
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
			$query = "SELECT xar_privileges.xar_pid,
						xar_privileges.xar_name,
						xar_privileges.xar_realm,
						xar_privileges.xar_module,
						xar_privileges.xar_component,
						xar_privileges.xar_instance,
						xar_privileges.xar_level,
						xar_privileges.xar_description,
						xar_privmembers.xar_parentid
						FROM $this->privilegestable INNER JOIN $this->privmemberstable
						ON xar_privileges.xar_pid = xar_privmembers.xar_pid
						ORDER BY xar_privileges.xar_name";

			$result = $this->dbconn->Execute($query);
			if (!$result) return;

			$privileges = array();
			$ind = 0;
			while(!$result->EOF) {
				list($pid, $name, $realm, $module, $component, $instance, $level,
						$description,$parentid) = $result->fields;
				$ind = $ind + 1;
				$privileges[$ind] = array('pid' => $pid,
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
 * @param   none
 * @return  array of privileges
 * @throws  none
 * @todo    none
*/
    function gettoplevelprivileges() {
	if ((!isset($alltoplevelprivileges)) || count($alltoplevelprivileges)==0) {
			$query = "SELECT xar_privileges.xar_pid,
						xar_privileges.xar_name,
						xar_privileges.xar_realm,
						xar_privileges.xar_module,
						xar_privileges.xar_component,
						xar_privileges.xar_instance,
						xar_privileges.xar_level,
						xar_privileges.xar_description,
						xar_privmembers.xar_parentid
						FROM $this->privilegestable INNER JOIN $this->privmemberstable
						ON xar_privileges.xar_pid = xar_privmembers.xar_pid
						WHERE xar_privmembers.xar_parentid = 0
						ORDER BY xar_privileges.xar_name";

			$result = $this->dbconn->Execute($query);
			if (!$result) return;

			$privileges = array();
			$ind = 0;
			while(!$result->EOF) {
				list($pid, $name, $realm, $module, $component, $instance, $level,
						$description,$parentid) = $result->fields;
				$ind = $ind + 1;
				$privileges[$ind] = array('pid' => $pid,
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
			$alltoplevelprivileges = $privileges;
			return $privileges;
		}
		else {
			return $alltoplevelprivileges;
		}
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
			$query = "SELECT xar_realms.xar_rid,
						xar_realms.xar_name
						FROM $this->realmstable";

			$result = $this->dbconn->Execute($query);
			if (!$result) return;

// add some extra lines we want
			$realms = array();
			$realms[0] = array('rid' => -2,
							   'name' => ' ');
			$realms[1] = array('rid' => -1,
							   'name' => 'All');
			$realms[2] = array('rid' => 0,
							   'name' => 'None');

// add the realms from the database
// TODO: maybe remove the key, don't really need it
			$ind = 2;
			while(!$result->EOF) {
				list($rid, $name) = $result->fields;
				$ind = $ind + 1;
				$realms[$ind] = array('rid' => $rid,
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
			$query = "SELECT xar_id,
						xar_name
						FROM $this->modulestable
						ORDER BY xar_name";

			$result = $this->dbconn->Execute($query);
			if (!$result) return;

// add some extra lines we want
			$modules = array();
			$modules[0] = array('id' => -2,
							   'name' => ' ');
			$modules[1] = array('id' => -1,
							   'name' => 'All');
			$modules[2] = array('id' => 0,
							   'name' => 'None');

// add the modules from the database
// TODO: maybe remove the key, don't really need it
			$ind = 2;
			while(!$result->EOF) {
				list($mid, $name) = $result->fields;
				$ind = $ind + 1;
				$modules[$ind] = array('id' => $mid,
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
 * construct a privilege's securitycheck.
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
		$query = "SELECT xar_masks.xar_sid,
					xar_masks.xar_component
					FROM $this->maskstable
					WHERE xar_masks.xar_module= '$module'
					ORDER BY xar_component";

		$result = $this->dbconn->Execute($query);
		if (!$result) return;

		$components = array();
		if ($module ==''){
			$components[1] = array('id' => -2,
							   'name' => '');
		}
		elseif(count($result->fields) == 0) {
			$components[1] = array('id' => -1,
							   'name' => 'All');
			$components[2] = array('id' => 0,
							   'name' => 'None');
		}
		else {
			$components[1] = array('id' => -1,
							   'name' => 'All');
			$components[2] = array('id' => 0,
							   'name' => 'None');
			$ind = 2;
			while(!$result->EOF) {
				list($mid, $name) = $result->fields;
				if (($name != 'All') && ($name != 'None')){
					$ind = $ind + 1;
					$components[$ind] = array('id' => $mid,
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
    function getinstances($module) {
		$query = "SELECT xar_instancetable1,
					xar_instancevaluefield,
					xar_instancedisplayfield
					FROM $this->instancestable
					WHERE xar_module= '$module'";

		$result = $this->dbconn->Execute($query);
		if (!$result) return;

		$instances = array();
		if ($module ==''){
			$instances[1] = array('id' => -2,
							   'name' => '');
		}
		elseif($result->EOF) {
			$instances[1] = array('id' => -1,
							   'name' => 'All');
			$instances[2] = array('id' => 0,
							   'name' => 'None');
		}
		else {
			list($table, $valuefield, $displayfield) = $result->fields;
			$query = "SELECT $valuefield,
						$displayfield
						FROM $table
						ORDER BY $displayfield";

			$result = $this->dbconn->Execute($query);
			if (!$result) return;

			$instances[1] = array('id' => -1,
							   'name' => 'All');
			$instances[2] = array('id' => 0,
							   'name' => 'None');
			$ind = 2;
			while(!$result->EOF) {
				list($id, $name) = $result->fields;
				if (($name != 'All') && ($name != 'None')){
					$ind = $ind + 1;
					$instances[$ind] = array('id' => $id,
									   'name' => $name);
				}
				$result->MoveNext();
			}
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
 * maketrees: create an array of all the privilege trees
 *
 * Makes a tree representation of each privileges tree
 * Returns an array of the trees
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  private
 * @param   none
 * @return  array of trees
 * @throws  none
 * @todo    none
*/
	function maketrees() {
		$trees = array();
		foreach ($this->gettoplevelprivileges() as $entry) {
			array_push($trees,$this->maketree($this->getPrivilege($entry['pid'])));
		}
		return $trees;
	}

/**
 * maketree: make a tree of privileges
 *
 * Makes a tree representation of a privileges hierarchy
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  private
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
	function maketree($privilege) {
		return $this->addbranches(array('parent'=>$this->getprivilegefast($privilege->getID())));
	}

/**
 * addbranches: given an initial tree node, add on the branches
 *
 * Adds branches to a tree representation of privileges
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  private
 * @param   tree node
 * @return  tree node
 * @throws  none
 * @todo    none
*/
	function addbranches($node){
		$object = $node['parent'];
		$node['expanded'] = false;
		$node['selected'] = false;
		$node['children'] = array();
		foreach($this->getsubprivileges($object['pid']) as $subnode){
			array_push($node['children'],$this->addbranches(array('parent'=>$subnode)));
		}
		return $node;
	}

/**
 * drawtrees: create an array of tree drawings
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  private
 * @param   none
 * @return  array of tree drawings
 * @throws  none
 * @todo    none
*/
	function drawtrees(){
		$drawntrees = array();
		foreach($this->maketrees() as $tree){
			array_push($drawntrees,array('tree'=>$this->drawtree($tree)));
		}
		return $drawntrees;
	}

/**
 * drawtree: create a crude html drawing of the privileges tree
 *
 * We use the data from maketree to create a tree layout
 * This should be in a template or at least in the xaradmin file, but it's easier here
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  private
 * @param   array representing an initial node
 * @return  none
 * @throws  none
 * @todo    none
*/

// some variables we'll need to hold drawing info
var $html;
var $nodeindex;
var $indent;
var $level;

// convenience variables to hold strings referring to pictures
var $el = '<img src="modules/privileges/xarimages/el.gif" style="vertical-align: middle">';
var $tee = '<img src="modules/privileges/xarimages/T.gif" style="vertical-align: middle">';
var $aye = '<img src="modules/privileges/xarimages/I.gif" style="vertical-align: middle">';
var $bar = '<img src="modules/privileges/xarimages/s.gif" style="vertical-align: middle">';
var $emptybox = '<img class="box" src="modules/privileges/xarimages/k1.gif" style="vertical-align: middle">';
var $expandedbox = '<img class="box" src="modules/privileges/xarimages/k2.gif" style="vertical-align: middle">';
var $blank = '<img src="modules/privileges/xarimages/blank.gif" style="vertical-align: middle">';
var $collapsedbox = '<img class="box" src="modules/privileges/xarimages/k3.gif" style="vertical-align: middle">';

// we'll use this to check whether a group has already been processed
var	$alreadydone;

function drawtree($node) {

	$this->html = '<div name="PrivilegesTree" id="PrivilegesTree">';
	$this->nodeindex = 0;
	$this->indent = array();
	$this->level = 0;
	$this->alreadydone = array();

	$this->drawbranch($node);
	$this->html .= '</div>';
	return $this->html;
}

/**
 * drawbranch: draw a branch of the privileges tree
 *
 * This is a recursive function
 * This should be in a template or at least in the xaradmin file, but it's easier here
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  private
 * @param   array representing a tree node
 * @return  none
 * @throws  none
 * @todo    none
*/

function drawbranch($node){

	$this->level = $this->level + 1;
	$this->nodeindex = $this->nodeindex + 1;
	$object = $node['parent'];

// check if we've aleady processed this entry
	if (in_array($object['pid'],$this->alreadydone)) {
		$drawchildren = false;
		$node['children'] = array();
	}
	else {
		$drawchildren = true;
		array_push($this->alreadydone,$object['pid']);
	}

// is this a branch?
	$isbranch = count($node['children'])>0 ? true : false;

// now begin adding rows to the string
	$this->html .= '<div class="xarbranch" id="x' . $this->nodeindex . '" style="align: left">';

// this table holds the index, the tree drawing gifs and the info about the privilege
	$this->html .= '<div style="position: relative;">';
	$this->html .= $this->drawindent();
	if (count($node['children']) > 0) {
		if ($this->nodeindex != 1){
			$lastindent = array_pop($this->indent);
			if ($lastindent == $this->el) {
				array_push($this->indent,$this->blank . $this->blank);
			}
			else {
				array_push($this->indent,$this->aye . $this->blank);
			}
			$this->html .= $this->bar;
		}
		$this->html .= $this->expandedbox;
	}
	else {
		if ($this->nodeindex != 1){
			$this->html .= $this->bar;
		}
		$this->html .= $this->emptybox;
	}
	$this->html .=  '<span name="titletext" style="padding-left: 1em">';

// draw the name of the object and make a link
	if($object['pid'] < 3) {
		$this->html .= '<b>' . $object['name'] . '</b>: ';
	}
	else {
		$this->html .= '<a href="' .
					xarModURL('privileges',
						 'admin',
						 'modifyprivilege',
						 array('pid'=>$object['pid'])) .' ">' .$object['name'] . '</a>: &nbsp;';
	}
	$this->html .= count($this->getsubprivileges($object['pid'])) . ' components</span>';

// this next table holds the Delete, Users and Privileges links

// toggle the tree
	$this->html .=  '<span name="togglelink" style="text-align:center; position:absolute; right: 15em">';
	if(count($this->getsubprivileges($object['pid'])) == 0) {
		$this->html .= '&nbsp;';
	}
	else {
		$this->html .= '<a href="javascript:xarTree_exec(\''. $object['name'] .'\',2);" title="Expand or collapse this tree">
			&nbsp;Toggle&nbsp;
			</a>';
	}
	$this->html .= '</span>';

// don't allow deletion of certain privileges
	$this->html .=  '<span name="deletelink" style="text-align:center; position:absolute; right: 10em">';
	if($object['pid'] < 3) {
		$this->html .= '&nbsp;';
	}
	else {
		$this->html .= '<a href="' .
			xarModURL('privileges',
				 'admin',
				 'deleteprivilege',
				 array('pid'=>$object['pid'])) .
				 '" title="Delete this Privilege">&nbsp;Delete&nbsp;</a>';
	}
	$this->html .= '</span>';

// offer to show the users/groups of this group
	$this->html .=  '<span name="userslink" style="text-align:center; position:absolute; right: 1em">';
	$this->html .= '<a href="' .
			xarModURL('privileges',
				 'admin',
				 'viewroles',
				 array('pid'=>$object['pid'])) .
				 '" title="Show the Groups/Users this Privilege is assigned to">&nbsp;Groups/Users</a>';

// close the html row
	$this->html .= '</span></div>';

// we've finished this row; now do the children of this privilege
	$this->html .= $isbranch ? '<div class="xarleaf" id="x' . $this->nodeindex . '" >' : '';
	$ind=0;
	foreach($node['children'] as $subnode){
		$ind = $ind + 1;

// if this is the last child, get ready to draw an "L", otherwise a sideways "T"
		if ($ind == count($node['children'])) {
			array_push($this->indent,$this->el);
		}
		else {
			array_push($this->indent,$this->tee);
		}

// draw this child
		$this->drawbranch($subnode);

// we're done; remove the indent string
		array_pop($this->indent);
	}
		$this->level = $this->level - 1;

// write the closing tags
	$this->html .= $isbranch ? '</div>' : '';
// close the html row
	$this->html .= '</div>';

}

/**
 * drawindent: draws the graphic part of the tree
 *
 * A helper funtion to output a HTML string containing the pictures for
 * a line of the tree
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  string
 * @throws  none
 * @todo    none
*/

function drawindent() {
	$html = '';
	foreach ($this->indent as $column) {$html .= $column;}
	return $html;
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
 * @param	array of values
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
		$this->acltable = $xartable['acl'];

        $this->sid          = $sid;
        $this->name         = $name;
        $this->realm        = $realm;
        $this->module     	= $module;
        $this->component    = $component;
        $this->instance     = $instance;
        $this->level        = $level;
        $this->description  = $description;
    }

/**
 * implies: compares two masks
 *
 * Checks whether this mask is mighter than $mask
 * Returns true if it is.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   mask
 * @return  boolean
 * @throws  none
 * @todo    none
*/
	function implies($mask) {
		if (
			($this->getRealm() == 'All') ||
			($this->getRealm() == 'None') && ($mask->getRealm() != 'All')
			)
		{$xRealm = true;}
		else {$xRealm = false;}

		if (
			($this->getModule() == $mask->getModule()) ||
//			($mask->getModule() == 'All') ||
			($this->getModule() == 'All') && ($mask->getModule() != 'None')
		)
		{$xModule = true;}
		else {$xModule = false;}

		if (
			($this->getComponent() == $mask->getComponent()) ||
//			($mask->getComponent() == 'All') ||
			($this->getComponent() == 'All') && ($mask->getComponent() != 'None')
		)
		{$xComponent = true;}
		else {$xComponent = false;}

		if (
			($this->getInstance() == 'All') ||
			($this->getInstance() == 'None') && ($mask->getInstance() != 'All')
			)
		{$xInstance = true;}
		else {$xInstance = false;}

		$xLevel = $this->getLevel() >= $mask->getLevel();

		$implies = $xRealm && $xModule && $xComponent && $xInstance && $xLevel;

//		echo $this->getName() . " implies " . $mask->getName() . ": " . $implies;

		return $implies;
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
 * @param	array of values
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
		$this->acltable = $xartable['acl'];

        $this->pid          = $pid;
        $this->name         = $name;
        $this->realm        = $realm;
        $this->module     	= $module;
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
			return false;
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
		if (!result) return;

// use the index to get the privileges object created from the repository
		list($pid) = $result->fields;
		$this->pid = $pid;
		$perms = new xarPrivileges();
		$parentperm = $perms->getprivilege($this->parentid);

// make this privilege a child of its parent
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
		$query = 	"UPDATE " . $this->privilegestable .
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
		if (!result) return;

// remove this child from all the parents
		$perms = new xarPrivileges();
		while(!$result->EOF) {
			list($parentid) = $result->fields;
			$parentperm = $perms->getPrivilege($parentid);
			$parentperm->removeMember($this);
			$result->MoveNext();
		}
		return true;
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
		$query = "SELECT xar_roles.xar_pid,
					xar_roles.xar_name,
					xar_roles.xar_type,
					xar_roles.xar_uname,
					xar_roles.xar_email,
					xar_roles.xar_pass,
					xar_roles.xar_url,
					xar_roles.xar_auth_module
					FROM $this->rolestable INNER JOIN $this->acltable
					ON xar_roles.xar_pid = xar_acl.xar_partid
					WHERE xar_acl.xar_permid = $this->pid";
//Execute the query, bail if an exception was thrown
		$result = $this->dbconn->Execute($query);
		if (!$result) return;

// make objects from the db entries retrieved
		include_once 'modules/roles/xarroles.php';
		$roles = array();
//		$ind = 0;
		while(!$result->EOF) {
			list($pid,$name,$type,$uname,$email,$pass,$url,$auth_module) = $result->fields;
//			$ind = $ind + 1;
			$role = new xarRole(array('pid' => $pid,
							   'name' => $name,
							   'type' => $type,
							   'uname' => $uname,
							   'email' => $email,
							   'pass' => $pass,
							   'url' => $url,
							   'auth_module' => $auth_module,
							   'parentid' => 0));
			$result->MoveNext();
			array_push($roles, $role);
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

// if this is the root return an empty array
		if ($this->getID() == 1) return $parents;

// if this is a user just perform a SELECT on the rolemembers table
		$query = "SELECT xar_privileges.*, xar_privmembers.xar_parentid
					FROM $this->privilegestable INNER JOIN $this->privmemberstable
					ON xar_privileges.xar_pid = xar_privmembers.xar_parentid
					WHERE xar_privmembers.xar_pid = " . $this->getID();
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
		$query = "SELECT xar_privileges.*, xar_privmembers.xar_parentid
					FROM $this->privilegestable INNER JOIN $this->privmemberstable
					ON xar_privileges.xar_pid = xar_privmembers.xar_pid
					WHERE xar_privmembers.xar_parentid = " . $this->getID();
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
				array_push($children,$descendant);
			}
		}

//done
		$descendants = array();
		$children = $masks->winnow($descendants,$children);
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
}
?>
