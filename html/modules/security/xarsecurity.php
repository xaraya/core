<?php
/**
 * File: $Id$
 *
 * Purpose of file:  Security administration API
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage Security Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
*/

/**
 * xarSchemas: class for the schema repository
 *
 * Represents the repository containing all security schemas
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @throws  none
 * @todo    none
*/
class xarSchemas
{
	var $dbconn;
	var $permissionstable;
	var $permmemberstable;
	var $schemastable;
	var $modulestable;
	var $realmstable;
	var $acltable;
	var $allschemas;
	var $levels;

/**
 * xarSchemas: constructor for the class
 *
 * Just sets up the db connection and initializes some variables
 * This should really be a static class
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param	none
 * @return  the schemas object
 * @throws  none
 * @todo    none
*/
	function xarSchemas() {
		list($this->dbconn) = xarDBGetConn();
		$xartable = xarDBGetTables();
		$this->permissionstable = $xartable['permissions'];
		$this->permmemberstable = $xartable['permmembers'];
		$this->schemastable = $xartable['schemas'];
		$this->modulestable = $xartable['modules'];
		$this->realmstable = $xartable['realms'];
		$this->acltable = $xartable['acl'];
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
 * getschemas: returns all the current schemas.
 *
 * Returns an array of all the schemas in the schemas repository
 * The repository contains an entry for each schema.
 * This function will initially load the schemas from the db into an array and return it.
 * On subsequent calls it just returns the array .
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  array of schemas
 * @throws  list of exception identifiers which can be thrown
 * @todo    list of things which must be done to comply to relevant RFC
*/
    function getschemas($module = 'All') {

	if ((!isset($allschemas)) || count($allschemas)==0) {
			if ($module == '' || $module == 'All') {
				$query = "SELECT * FROM $this->schemastable";
			}
			else {
				$query = "SELECT *
						FROM $this->schemastable WHERE xar_module = '$module'
						ORDER BY xar_name";
			}

			$result = $this->dbconn->Execute($query);
			if (!$result) return;

			$schemas = array();
			$ind = 0;
			while(!$result->EOF) {
				list($sid, $name, $realm, $module, $component, $instance, $level,
						$description) = $result->fields;
				$ind = $ind + 1;
				$schemas[$ind] = array('sid' => $sid,
								   'name' => $name,
								   'realm' => $realm,
								   'module' => $module,
								   'component' => $component,
								   'instance' => $instance,
								   'level' => $this->levels[$level],
								   'description' => $description);
				$result->MoveNext();
			}
			$allschemas = $schemas;
			return $schemas;
		}
		else {
			return $allschemas;
		}
    }

/**
 * register: register a schema
 *
 * Creates a schema entry in the schemas table
 * This function should be invoked every time a new instance is created
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   schema
 * @return  boolean
 * @throws  none
 * @todo    none
*/
/*	function register($schema)
	{
		$query = "INSERT INTO xar_schemas VALUES ($schema->getID(),
												'',
												'$schema->getRealm()',
												'$schema->getModule()',
												'$schema->getComponent()',
												'$schema->getInstance()',
												$schema->getLevel(),
												'$schema->getDescription()')";
		if (!$this->dbconn->Execute($query)) return;
		return true;
	}
*/

/**
 * register: register a schema
 *
 * Creates a schema entry in the schemas table
 * This function should be invoked every time a new instance is created
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   array of schema values
 * @return  boolean
 * @throws  none
 * @todo    none
*/
	function register($name,$realm,$module,$component,$instance,$level,$description='')
	{
		$nextID = $this->dbconn->genID($this->schemastable);
		$nextIDprep = xarVarPrepForStore($nextID);
		$nameprep = xarVarPrepForStore($name);
		$realmprep = xarVarPrepForStore($realm);
		$moduleprep = xarVarPrepForStore($module);
		$componentprep = xarVarPrepForStore($component);
		$instanceprep = xarVarPrepForStore($instance);
		$levelprep = xarVarPrepForStore($level);
		$descriptionprep = xarVarPrepForStore($description);
		$query = "INSERT INTO $this->schemastable VALUES ($nextIDprep,
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
 * winnow: merges two arrays of permissions to a single array of permissions
 *
 * The permissions are compared for implication and the less mighty are discarded
 * This is the way permissions hierarchies are contracted.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   array of permissions objects
 * @param   array of permissions objects
 * @return  array of permissions objects
 * @throws  none
 * @todo    create exceptions for bad input
*/
 	function winnow($perms1, $perms2)
	{
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
 * trump: merges two arrays of permissions to a single array of permissions
 *
 * The permissions are compared for implication and the less recent are discarded.
 * The less recent are assumed to be in the first array
 * This is the way permissions hierarchies in participant hierarchies are contracted.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   array of permissions objects
 * @param   array of permissions objects
 * @return  array of permissions objects
 * @throws  none
 * @todo    create exceptions for bad input
*/
 	function trump($perms1, $perms2)
	{
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
			}
			if (!$isimplied) array_push($perms1, $perm2);
		}

// done
		return $perms1;
	}

/**
 * challenge: challenge a participant against a component
 *
 * Checks the current group or user's permissions against a component
 * This function should be invoked every time a a permissions check needs to be done
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   component string
 * @return  boolean
 * @throws  none
 * @todo    none
*/

	function challenge($schemaname,$participant='')
	{
//	return $this->getSchema($schemaname);;

	//Get the inherited permissions
		$ancestors = $participant->getAncestors();

// set up an array to hold the permissions
		$irreducibleset = array();

// if there are ancestors, look for their permissions
		if (count($ancestors) >0) {

// need to process the last ones first
			$ancestors = array_reverse($ancestors);

// set up a temporary array to hold results
			$final = array();

// begin with the guy at the top of the pyramid
			$top = $ancestors[0]->getLevel();

// begin processing an ancestor
			foreach ($ancestors as $ancestor) {

// get the ancestors assigned permissions
				$perms = $ancestor->getAssignedPermissions();
				$permissions = array();
// for each one winnow the  assigned permissions and then the inherited
				foreach ($perms as $perm) {
					$permissions = $this->winnow(array($perm),$permissions);
					$permissions = $this->winnow($perm->getAncestors(),$permissions);
				}

// add some info on the group they belong to and stick it all in an array
				$groupname = $ancestor->getName();
				$grouplevel = $ancestor->getLevel();
				array_push($final,array('permissions'=>$permissions,
									'name'=>$groupname,
									'level'=>$grouplevel));
			}

// winnow all permissions of a given level above the participant
				foreach ($final as $step) {
				if ($step['level']) {
					$irreducibleset = $this->winnow($irreducibleset,$step['permissions']);
				}

// or trump the previous permissions with those of a lower level
// TODO: this is a bug.Probably should winnow the lowerlevel and THEN trump against
// the higher level
				else {
					$irreducibleset = $this->trump($irreducibleset,$step['permissions']);
					$top = $step['level'];
				}
			}
		}

// get the assigned permissions and winnow them
		$participantpermissions = $participant->getAssignedPermissions();
		$partpermissions = $this->winnow($participantpermissions,$participantpermissions);

// trump them against the accumulated permissions from higher levels
		$irreducibleset = $this->trump($irreducibleset,$participantpermissions);

// check against the schema
		$pass = false;
		foreach ($irreducibleset as $chiave) {
			if ($chiave->implies($this->getSchema($schemaname))) {

// found a permission that admits: return the permission
			return $chiave;
			}
		}
// nothing found: return false
		return $pass;
	}

/**
 * getSchema: gets a single schema
 *
 * Retrieves a single schema from the Schemas repository
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string
 * @return  schema object
 * @throws  none
 * @todo    none
*/
	function getSchema($name)
	{
//Set up the query and get the data from the xarschemas table
		$query = "SELECT * FROM $this->schemastable
					WHERE xar_schemas.xar_name= '$name'";
		$result = $this->dbconn->Execute($query);
		if (!$result) return;

		if (count($result->fields) == 0) {
			$msg = xarML('Unknown schema name: ') . $name;
			xarExceptionSet(XAR_USER_EXCEPTION, 'NO_SCHEMA',
						   new SystemException($msg));
        return;
    }

// reorganize the data into an array and create the schemas object
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
		return new xarSchema($pargs);
	}
}


/**
 * xarPermissions: class for the permissions repository
 *
 * Represents the repository containing all permissions
 * The constructor is the constructor of the parent object
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @throws  none
 * @todo    none
*/

class xarPermissions extends xarSchemas
{

/**
 * register: register a permission
 *
 * Creates a schema entry in the schemas table
 * This function should be invoked every time a new instance is created
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   array of permission values
 * @return  boolean
 * @throws  none
 * @todo    none
*/
	function register($name,$realm,$module,$component,$instance,$level,$description='')
	{
		$nextID = $this->dbconn->genID($this->schemastable);
		$nextIDprep = xarVarPrepForStore($nextID);
		$nameprep = xarVarPrepForStore($name);
		$realmprep = xarVarPrepForStore($realm);
		$moduleprep = xarVarPrepForStore($module);
		$componentprep = xarVarPrepForStore($component);
		$instanceprep = xarVarPrepForStore($instance);
		$levelprep = xarVarPrepForStore($level);
		$descriptionprep = xarVarPrepForStore($description);
		$query = "INSERT INTO $this->permissionstable VALUES ($nextIDprep,
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
 * assign: assign a permission to a user/group
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
	function assign($permission,$participant)
	{

// get the ID of the permission to be assigned
		$perm = $this->findPermission($permission);
		$permid = $perm->getID();

// get the Participants class
		include_once 'modules/participants/xarparticipants.php';
    	$parts = new xarParticipants();

// find the participant for the assignation and get its ID
		$part = $parts->findParticipant($participant);
		$partid = $part->getID();

// Add the assignation as an entry to the acl table
		$query = "INSERT INTO $this->acltable VALUES ($partid,
												$permid)";
		if (!$this->dbconn->Execute($query)) return;
		return true;
	}

/**
 * getpermissions: returns all the current permissions.
 *
 * Returns an array of all the permissions in the permissions repository
 * The repository contains an entry for each permission.
 * This function will initially load the permissions from the db into an array and return it.
 * On subsequent calls it just returns the array .
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  array of permissions
 * @throws  none
 * @todo    none
*/
    function getpermissions() {
	if ((!isset($allpermissions)) || count($allpermissions)==0) {
			$query = "SELECT xar_permissions.xar_pid,
						xar_permissions.xar_name,
						xar_permissions.xar_realm,
						xar_permissions.xar_module,
						xar_permissions.xar_component,
						xar_permissions.xar_instance,
						xar_permissions.xar_level,
						xar_permissions.xar_description,
						xar_permmembers.xar_parentid
						FROM $this->permissionstable INNER JOIN $this->permmemberstable
						ON xar_permissions.xar_pid = xar_permmembers.xar_pid
						ORDER BY xar_permissions.xar_name";

			$result = $this->dbconn->Execute($query);
			if (!$result) return;

			$permissions = array();
			$ind = 0;
			while(!$result->EOF) {
				list($pid, $name, $realm, $module, $component, $instance, $level,
						$description,$parentid) = $result->fields;
				$ind = $ind + 1;
				$permissions[$ind] = array('pid' => $pid,
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
			$allpermissions = $permissions;
			return $permissions;
		}
		else {
			return $allpermissions;
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
			$query = "SELECT xar_modules.xar_id,
						xar_modules.xar_name
						FROM $this->modulestable";

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

// add the realms from the database
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
 * The components correspond to schemas in the schemas table. Each one can be used to
 * construct a permissions challenge.
 * They are used to populate dropdowns in displays
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  array of component ids and names
 * @throws  none
 * @todo    this isn't really the right place for this function
*/
    function getcomponents($module) {
		$query = "SELECT xar_schemas.xar_sid,
					xar_schemas.xar_component
					FROM $this->schemastable
					WHERE xar_schemas.xar_module= '$module'";

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
				$ind = $ind + 1;
				$components[$ind] = array('id' => $mid,
								   'name' => $name);
				$result->MoveNext();
			}
		}
		return $components;
    }

	function getpermissionfast($pid){
		foreach($this->getpermissions() as $permission){
			if ($permission['pid'] == $pid) return $permission;
		}
		return false;
	}

	function getsubpermissions($pid){
		$subpermissions = array();
		$ind = 0;
		foreach($this->getpermissions() as $subpermission){
			if ($subpermission['parentid'] == $pid) {
				$ind = $ind + 1;
				$subpermissions[$ind] = $subpermission;
			}
		}
		return $subpermissions;
	}

/**
 * maketree: make a tree of permissions
 *
 * Makes a tree representation of a permissions hierarchy
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  private
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
	function maketree() {
		return $this->addbranches(array('parent'=>$this->getpermissionfast(1)));
	}

/**
 * addbranches: given an initial tree node, add on the branches
 *
 * Adds branches to a tree representation of permissions
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
		$node['children'] = array();
		foreach($this->getsubpermissions($object['pid']) as $subnode){
			array_push($node['children'],$this->addbranches(array('parent'=>$subnode)));
		}
		return $node;
	}

/**
 * drawtree: create a crude html drawing of the permissions tree
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
var $el = '<img src="modules/security/xarimages/el.gif">';
var $tee = '<img src="modules/security/xarimages/T.gif">';
var $aye = '<img src="modules/security/xarimages/I.gif">';
var $bar = '<img src="modules/security/xarimages/s.gif">';
var $emptybox = '<img src="modules/security/xarimages/k1.gif">';
var $fullbox = '<img src="modules/security/xarimages/k2.gif">';
var $blank = '<img src="modules/security/xarimages/blank.gif">';

// we'll use this to check whether a group has already been processed
var	$alreadydone;

function drawtree($node) {

	$this->html = '<table border="0" cellspacing="0" cellpadding="0" width="100%">';
	$this->nodeindex = 0;
	$this->indent = array();
	$this->level = 0;
	$this->alreadydone = array();

	$this->drawbranch($node);
	$this->html .= '</table>';
	return $this->html;
}

/**
 * drawbranch: draw a branch of the permissions tree
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

// now begin adding rows to the string
	$this->html .= '<tr><td>';

// this table hold the index, the tree drawing gifs and the info about the permission
	$this->html .= '<table cellspacing="0" cellpadding="0" border="0"><tr><td width="20">' . $this->nodeindex . '</td><td width="$indentwidth">';
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
		$this->html .= $this->fullbox . '</td>';
	}
	else {
		$this->html .= $this->bar;
		$this->html .= $this->emptybox . '</td>';
	}
	$this->html .=  '<td valign="middle">&nbsp;';

// if we've already done this entry skip the links and just tell the user
	if (!$drawchildren) {
		$this->html .= '<b>' . $object['name'] . '</b>: ';
		$this->html .= ' see the entry above';
	}
	else{
		if($object['pid'] < 3) {
			$this->html .= '<b>' . $object['name'] . '</b>: ';
		}
		else {
			$this->html .= '<a href="' .
						xarModURL('security',
							 'admin',
							 'modifypermission',
							 array('pid'=>$object['pid'])) .' ">' .$object['name'] . '</a>: &nbsp;';
		}
		$this->html .= count($this->getsubpermissions($object['pid'])) . ' subpermissions';
	}
	$this->html .= '</td></tr></table></td>';

// this next table holds the Delete, Users and Permissions links
// don't allow deletion of certain permissions
	$this->html .= '<td></td><td align="right"><table><tr><td width="40">';
	if(($object['pid'] < 3)) {
		$this->html .= '&nbsp;';
	}
	else {
		$this->html .= '<a href="' .
			xarModURL('security',
				 'admin',
				 'deletepermission',
				 array('pid'=>$object['pid'])) .
				 '" title="Delete this Permission">&nbsp;Delete&nbsp;</a>';
	}
	$this->html .= '</td>';

// offer to show the permissions of this group
	$this->html .= '<td width="60"><a href="' .
			xarModURL('security',
				 'admin',
				 'showparticipants',
				 array('pid'=>$object['pid'])) .
				 '" title="Show the Groups/Users this Permission is assigned to">&nbsp;Groups/Users</a>';

// close the html row
	$this->html .= '</td></tr></table></td></tr>';

// we've finished this row; now do the children of this permission
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
 * getPermission: gets a single permission
 *
 * Retrieves a single permission object from the Permissions repository
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   integer
 * @return  permission object
 * @throws  none
 * @todo    none
*/
 	function getPermission($pid)
	{
		$query = "SELECT *
                  FROM $this->permissionstable
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
		return new xarPermission($pargs);
	}

/**
 * findPermission: finds a single permission based on its name
 *
 * Retrieves a single permission object from the Permissions repository
 * This is a convenience class for module developers
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string
 * @return  permission object
 * @throws  none
 * @todo    none
*/
 	function findPermission($name)
	{
		$query = "SELECT *
                  FROM $this->permissionstable
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
		return new xarPermission($pargs);
	}

/**
 * makeMember: makes a permission a child of another permission
 *
 * Creates an entry in the permmembers table
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
                  FROM $this->permissionstable
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
		$parent =  new xarPermission($pargs);

// get the data for the child object
		$query = "SELECT *
                  FROM $this->permissionstable
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
		$child =  new xarPermission($pargs);

// done
		return $parent->addMember($child);
	}

/**
 * isRoot: defines the root of the permissions hierarchy
 *
 * Creates an entry in the permmembers table
 * This is a convenience class for module developers
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string
 * @return  boolean
 * @throws  none
 * @todo    create exceptions for bad input
*/
 	function isRoot($rootname)
	{
// get the data for the root object
		$query = "SELECT xar_pid
                  FROM $this->permissionstable
                  WHERE xar_name = '$rootname'";
		//Execute the query, bail if an exception was thrown
		$result = $this->dbconn->Execute($query);
		if (!$result) return;

// create the entry
		list($pid) = $result->fields;
		$query = "INSERT INTO $this->permmemberstable
				VALUES ($pid,0)";
		//Execute the query, bail if an exception was thrown
		if (!$this->dbconn->Execute($query)) return;

// done
		return true;
	}

}

/**
 * xarSchema: class for the schema object
 *
 * Represents a single security schema
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @throws  none
 * @todo    none
*/

  class xarSchema
{
	var $sid;           //the id of this permission
	var $name;          //the name of this permission
	var $realm;         //the realm of this permission
	var $module;        //the module of this permission
	var $component;     //the component of this permission
	var $instance;      //the instance of this permission
	var $level;         //the access level of this permission
	var $description;   //the long description of this permission

	var $dbconn;
	var $permissionstable;
	var $permmemberstable;

/**
 * xarSchema: constructor for the class
 *
 * Creates a security schema
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param	array of values
 * @return  schema
 * @throws  none
 * @todo    none
*/

    function xarSchema($pargs)
    {
		extract($pargs);

//TODO: check this line
		if (!xarSecAuthAction(0, 'Permissions::', "::", ACCESS_ADD)) {return;}

		list($this->dbconn) = xarDBGetConn();
		$xartable = xarDBGetTables();
		$this->permissionstable = $xartable['permissions'];
		$this->permmemberstable = $xartable['permmembers'];
		$this->participantstable = $xartable['participants'];
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
 * implies: compares two schemas
 *
 * Checks whether this schema is mighter than $schema
 * Returns true if it is.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   schema
 * @return  boolean
 * @throws  none
 * @todo    none
*/
	function implies($schema) {
		if (
			($this->getRealm() == 'All') ||
			($this->getRealm() == 'None') && ($schema->getRealm() != 'All')
			)
		{$xRealm = true;}
		else {$xRealm = false;}

		if (
			($this->getModule() == 'All') ||
			($this->getModule() == 'None') && ($schema->getModule() != 'All')
			)
		{$xModule = true;}
		else {$xModule = false;}

		if (
			($this->getComponent() == 'All') ||
			($this->getComponent() == 'None') && ($schema->getComponent() != 'All')
			)
		{$xComponent = true;}
		else {$xComponent = false;}

		if (
			($this->getInstance() == 'All') ||
			($this->getInstance() == 'None') && ($schema->getInstance() != 'All')
			)
		{$xInstance = true;}
		else {$xInstance = false;}

		$xLevel = $this->getLevel() >= $schema->getLevel();

		$implies = $xRealm && $xModule && $xComponent && $xInstance && $xLevel;

//		echo $this->getName() . " implies " . $schema->getName() . ": " . $implies;

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
 * xarPermission: class for the permissions object
 *
 * Represents a single permissions object
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @throws  none
 * @todo    none
*/

class xarPermission extends xarSchema
{

	var $pid;           //the id of this permission
	var $name;          //the name of this permission
	var $realm;         //the realm of this permission
	var $module;        //the module of this permission
	var $component;     //the component of this permission
	var $instance;      //the instance of this permission
	var $level;         //the access level of this permission
	var $description;   //the long description of this permission
	var $parentid;      //the pid of the parent of this permission

	var $dbconn;
	var $permissionstable;
	var $permmemberstable;

/**
 * xarPermission: constructor for the class
 *
 * Just sets up the db connection and initializes some variables
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param	array of values
 * @return  the permission object
 * @throws  none
 * @todo    none
*/
    function xarPermission($pargs)
    {
		extract($pargs);

//TODO: check this line
		if (!xarSecAuthAction(0, 'Permissions::', "::", ACCESS_ADD)) {return;}

		list($this->dbconn) = xarDBGetConn();
		$xartable = xarDBGetTables();
		$this->permissionstable = $xartable['permissions'];
		$this->permmemberstable = $xartable['permmembers'];
		$this->participantstable = $xartable['participants'];
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
 * add: add a new permissions object to the repository
 *
 * Creates an entry in the repository for a permissions object that has been created
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
						'security');
			xarExceptionSet(XAR_USER_EXCEPTION,
						'DUPLICATE_DATA',
						 new DefaultUserException($msg));
			xarSessionSetVar('errormsg', _MODARGSERROR);
			return false;
		}


// Confirm that this permission name does not already exist
		$query = "SELECT COUNT(*) FROM $this->permissionstable
			  WHERE xar_name = '$this->name'";

		$result = $this->dbconn->Execute($query);
		if (!$result) return;

		list($count) = $result->fields;

		if ($count == 1) {
			$msg = xarML('This entry already exists.',
						'security');
			xarExceptionSet(XAR_USER_EXCEPTION,
						'DUPLICATE_DATA',
						 new DefaultUserException($msg));
			xarSessionSetVar('errormsg', _GROUPALREADYEXISTS);
			return false;
		}

// set up the variables for inserting the object into the repository
			$nextId = $this->dbconn->genID($this->permissionstable);

			$nextIdprep = xarVarPrepForStore($nextId);
			$nameprep = xarVarPrepForStore($this->name);
			$realmprep = xarVarPrepForStore($this->realm);
			$moduleprep = xarVarPrepForStore($this->module);
			$componentprep = xarVarPrepForStore($this->component);
			$instanceprep = xarVarPrepForStore($this->instance);
			$levelprep = xarVarPrepForStore($this->level);

// create the insert query
		$query = "INSERT INTO $this->permissionstable
					(xar_pid, xar_name, xar_realm, xar_module, xar_component, xar_instance, xar_level)
				  VALUES ($nextIdprep, '$nameprep', '$realmprep', '$moduleprep', '$componentprep', '$instanceprep', $levelprep)";
		//Execute the query, bail if an exception was thrown
		if (!$this->dbconn->Execute($query)) return;

// the insert created a new index value
// retrieve the value
		$query = "SELECT MAX(xar_pid) FROM $this->permissionstable";
		//Execute the query, bail if an exception was thrown
		$result = $this->dbconn->Execute($query);
		if (!result) return;

// use the index to get the permissions object created from the repository
		list($pid) = $result->fields;
		$this->pid = $pid;
		$perms = new xarPermissions();
		$parentperm = $perms->getpermission($this->parentid);

// make this permission a child of its parent
		return $parentperm->addMember($this);
	}


/**
 * addMember: adds a permission to a permission
 *
 * Make a permission a member of another permission.
 * A permission can have any number of parents or children..
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   permission object
 * @return  boolean
 * @throws  none
 * @todo    check to make sure the child is not a parent of the parent
*/
    function addMember($member) {

		$query = "INSERT INTO $this->permmemberstable
				VALUES (" . $member->getID() . "," . $this->getID() . ")";
		//Execute the query, bail if an exception was thrown
		if (!$this->dbconn->Execute($query)) return;
		return true;
    }

/**
 * removeMember: removes a permission from a permission
 *
 * Removes a permission as an entry of another permission.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function removeMember($member) {

		$query = "DELETE FROM $this->permmemberstable
              WHERE xar_pid=" . $member->getID() .
              " AND xar_parentid=" . $this->getID();
		//Execute the query, bail if an exception was thrown
		if (!$this->dbconn->Execute($query)) return;
		return true;
    }

/**
 * update: updates a permission in the repository
 *
 * Updates a permission in the permissions repository
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
		$query = 	"UPDATE " . $this->permissionstable .
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
 * remove: deletes a permission in the repository
 *
 * Deletes a permission's entry in the permissions repository
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
		$query = "DELETE FROM $this->permissionstable
              WHERE xar_pid=" . $this->pid;
//Execute the query, bail if an exception was thrown
		if (!$this->dbconn->Execute($query)) return;

// set up a query to get all the parents of this child
		$query = "SELECT xar_parentid FROM $this->permmemberstable
              WHERE xar_pid=" . $this->getID();
		//Execute the query, bail if an exception was thrown
		$result = $this->dbconn->Execute($query);
		if (!result) return;

// remove this child from all the parents
		$perms = new xarPermissions();
		while(!$result->EOF) {
			list($parentid) = $result->fields;
			$parentperm = $perms->getPermission($parentid);
			$parentperm->removeMember($this);
			$result->MoveNext();
		}
		return true;
	}

/**
 * getParticipants: returns an array of participants
 *
 * Returns an array of participants this permission is assigned to
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
	function getParticipants(){

// set up a query to select the participants this permission
// is linked to in the acl table
		$query = "SELECT xar_participants.xar_pid,
					xar_participants.xar_name,
					xar_participants.xar_type,
					xar_participants.xar_uname,
					xar_participants.xar_email,
					xar_participants.xar_pass,
					xar_participants.xar_url,
					xar_participants.xar_auth_module
					FROM $this->participantstable INNER JOIN $this->acltable
					ON xar_participants.xar_pid = xar_acl.xar_partid
					WHERE xar_acl.xar_permid = $this->pid";
//Execute the query, bail if an exception was thrown
		$result = $this->dbconn->Execute($query);
		if (!$result) return;

// make objects from the db entries retrieved
		include_once 'modules/participants/xarparticipants.php';
		$participants = array();
//		$ind = 0;
		while(!$result->EOF) {
			list($pid,$name,$type,$uname,$email,$pass,$url,$auth_module) = $result->fields;
//			$ind = $ind + 1;
			$part = new xarParticipant(array('pid' => $pid,
							   'name' => $name,
							   'type' => $type,
							   'uname' => $uname,
							   'email' => $email,
							   'pass' => $pass,
							   'url' => $url,
							   'auth_module' => $auth_module,
							   'parentid' => 0));
			$result->MoveNext();
			array_push($participants, $part);
		}
// done
		return $participants;
	}

/**
 * removeParticipant: removes a participant
 *
 * Removes a participant this permission is assigned to
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   participant object
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function removeParticipant($part) {

// use the equivalent method from the participants object
		return $part->removePermission($this);
    }

/**
 * getParents: returns the parent objects of a permission
 *
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  array of permission objects
 * @throws  none
 * @todo    none
*/
    function getParents()
    {
// create an array to hold the objects to be returned
		$parents = array();

// if this is the root return an empty array
		if ($this->getID() == 1) return $parents;

// if this is a user just perform a SELECT on the partmembers table
		$query = "SELECT xar_permissions.*, xar_permmembers.xar_parentid
					FROM $this->permissionstable INNER JOIN $this->permmemberstable
					ON xar_permissions.xar_pid = xar_permmembers.xar_parentid
					WHERE xar_permmembers.xar_pid = " . $this->getID();
		$result = $this->dbconn->Execute($query);
		if (!$result) return;

// collect the table values and use them to create new participant objects
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
			array_push($parents, new xarPermission($pargs));
			$result->MoveNext();
			}
// done
		return $parents;
	}

/**
 * getAncestors: returns all objects in the permissions hierarchy above a permission
 *
 * The returned permissions are automatically winnowed
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  array of permission objects
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
		$schemas = new xarSchemas();
		while (list($key, $parent) = each($parents)) {
			$ancestors = $parent->getParents();
			foreach ($ancestors as $ancestor) {
				array_push($parents,$ancestor);
			}
		}

//done
		$ancestors = array();
		$parents = $schemas->winnow($ancestors,$parents);
		return $ancestors;
    }

/**
 * isEqual: checks whether two permissions are equal
 *
 * Two permission objects are considered equal if they have the same pid.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function isEqual($permission)
    {
    	return $this->getID() == $permission->getID();
	}

/**
 * getID: returns the ID of this permission
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