<?php
/**
 * File: $Id$
 *
 * Purpose of file:  Roles administration API
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
*/

/**
 * xarRoles: class for the role repository
 *
 * Represents the repository containing all roles
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @throws  none
 * @todo    none
*/
 class xarRoles
{
	var $allgroups = array();
	var $users = array();
	var $dbconn;
	var $rolestable;
	var $rolememberstable;

	function xarRoles() {
		list($this->dbconn) = xarDBGetConn();
		$xartable = xarDBGetTables();
		$this->rolestable = $xartable['roles'];
		$this->rolememberstable = $xartable['rolemembers'];
    }

/**
 * getgroups: returns all the current groups.
 *
 * Returns an array of all the groups in the roles repository
 * The repository contains an entry for each user and group.
 * This function will initially load the groups from the db into an array and return it.
 * On subsequent calls it just returns the array .
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  private
 * @param   none
 * @return  array of arrays representing all the groups
 * @throws  none
 * @todo    none
*/
    function getgroups() {

// check if we already have the groups stored
	if ((!isset($this->allgroups)) || count($this->allgroups)==0) {

// set up the query and get the groups
			$query = "SELECT xar_roles.xar_uid,
						xar_roles.xar_name,
						xar_roles.xar_users,
						xar_rolemembers.xar_parentid
						FROM $this->rolestable INNER JOIN $this->rolememberstable
						ON xar_roles.xar_uid = xar_rolemembers.xar_uid
						WHERE xar_roles.xar_type = 1
						ORDER BY xar_roles.xar_name";

			$result = $this->dbconn->Execute($query);
			if (!$result) return;

// arrange the data in an array
			$groups = array();
			$ind = 0;
			while(!$result->EOF) {
				list($uid,$name, $users, $parentid) = $result->fields;
				$ind = $ind + 1;
				$groups[$ind] = array('uid' => $uid,
								   'name' => $name,
								   'users' => $users,
								   'parentid' => $parentid);
				$result->MoveNext();
			}
			$this->allgroups = $groups;
			return $groups;
		}
		else {
			return $this->allgroups;
		}
    }

/**
 * getgroup: returns an array representing a group
 *
 * Returns an array of representing a group in the roles repository
 * The repository contains an entry for each user and group.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  private
 * @param   integer
 * @return  array representing the group
 * @throws  none
 * @todo    none
*/
	function getgroup($uid){
		foreach($this->getgroups() as $group){
			if ($group['uid'] == $uid) return $group;
		}
		return false;
	}

/**
 * getsubgroups: get the children of a group that are groups themselves
 *
 * This function is useful for setting up trees
 * We don't include users in the tree because there are too many to display
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  private
 * @param   none
 * @return  array representing the subgroups of a group
 * @throws  none
 * @todo    none
*/
	function getsubgroups($uid){

		$subgroups = array();
		$ind = 0;
		foreach($this->getgroups() as $subgroup){
			if ($subgroup['parentid'] == $uid) {
				$ind = $ind + 1;
				$subgroups[$ind] = $subgroup;
			}
		}
		return $subgroups;
	}

/**
 * maketree: make a tree of the roles that are groups
 *
 * We don't include users in the tree because there are too many to display
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  private
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
	function maketree() {
		return $this->addbranches(array('parent'=>$this->getgroup(1)));
	}

/**
 * addbranches: given an initial tree node, add on the brtanches that are groups
 *
 * We don't include users in the tree because there are too many to display
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
		foreach($this->getsubgroups($object['uid']) as $subnode){
			array_push($node['children'],$this->addbranches(array('parent'=>$subnode)));
		}
		return $node;
	}

/**
 * drawtree: create a crude html drawing of the role tree
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
var $el = '<img src="modules/roles/xarimages/el.gif" style="vertical-align: middle"/>';
var $tee = '<img src="modules/roles/xarimages/T.gif" style="vertical-align: middle"/>';
var $aye = '<img src="modules/roles/xarimages/I.gif" style="vertical-align: middle"/>';
var $bar = '<img src="modules/roles/xarimages/s.gif" style="vertical-align: middle"/>';
var $emptybox = '<img class="box" src="modules/roles/xarimages/k1.gif" style="vertical-align: middle"/>';
var $expandedbox = '<img class="box" src="modules/roles/xarimages/k2.gif" style="vertical-align: middle"/>';
var $collapsedbox = '<img class="box" src="modules/roles/xarimages/k3.gif" style="vertical-align: middle"/>';
var $blank = '<img src="modules/privileges/xarimages/blank.gif" style="vertical-align: middle"/>';
var $bigblank ='<span style="padding-left: 0.25em; padding-right: 0.25em;"><img src="modules/privileges/xarimages/blank.gif" style="vertical-align: middle; width: 16px; height: 16px;" /></span>';

// we'll use this to check whether a group has already been processed
var	$alreadydone;

/**
 * drawtree: draws the role tree
 * sets everything up and draws the first node
 *
 * This should be in a template or at least in the xaradmin file, but it's easier here
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  private
 * @param   array representing a tree node
 * @return  none
 * @throws  none
 * @todo    none
*/

function drawtree($node) {

	$this->html = '<div name="RolesTree" id="RolesTree">';
	$this->nodeindex = 0;
	$this->indent = array();
	$this->level = 0;
	$this->alreadydone = array();

	$this->drawbranch($node);
	$this->html .= '</div>';
	return $this->html;
}

/**
 * drawbranch: draw a branch of the role tree
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
	if (in_array($object['uid'],$this->alreadydone)) {
		$drawchildren = false;
		$node['children'] = array();
	}
	else {
		$drawchildren = true;
		array_push($this->alreadydone,$object['uid']);
	}

// is this a branch?
	$isbranch = count($node['children'])>0 ? true : false;

// now begin adding rows to the string
	$this->html .= '<div class="xarbranch" id="branch' . $this->nodeindex . '">';

// this next table holds the Delete, Users and Privileges links
// don't allow deletion of certain roles
	if(($object['uid'] < 9) || ($object['users'] > 0) || (!$drawchildren)) {
		$this->html .= $this->bigblank;
	}
	else {
		$this->html .= '<a href="' .
			xarModURL('roles',
				 'admin',
				 'deleterole',
				 array('uid'=>$object['uid'])) .
				 '" title="Delete this Group" style="padding-left: 0.25em; padding-right: 0.25em;"><img src="modules/roles/xarimages/delete.gif" style="vertical-align: middle;" /></a>';
	}

// offer to show users of a group if there are some
	if($object['users'] == 0 || (!$drawchildren)) {
		$this->html .= $this->bigblank;
	}
	else {
		$this->html .= '<a href="' .
				xarModURL('roles',
					 'admin',
					 'showusers',
					 array('uid'=>$object['uid'])) .
					 '" title="Show the Users in this Group" style="padding-left: 0.25em; padding-right: 0.25em;"><img src="modules/roles/xarimages/users.gif" style="vertical-align: middle;" /></a>';
	}

// offer to show the privileges of this group
	if(!$drawchildren) {
		$this->html .= $this->bigblank;
	}
	else {
		$this->html .= '<a href="' .
			xarModURL('roles',
				 'admin',
				 'showprivileges',
				 array('uid'=>$object['uid'])) .
				 '" title="Show the Privileges assigned to this Group" style="padding-left: 0.25em; padding-right: 0.25em;"><img src="modules/roles/xarimages/privileges.gif" style="vertical-align: middle;" /></a>';
	}

// offer to test the privileges of this group
	if(!$drawchildren) {
		$this->html .= $this->bigblank;
	}
	else {
		$this->html .= '<a href="' .
			xarModURL('roles',
				 'admin',
				 'testprivileges',
				 array('uid'=>$object['uid'])) .
				 '" title="Test this Groups\'s Privileges" style="padding-left: 0.25em; padding-right: 1em;"><img src="modules/roles/xarimages/test.gif" style="vertical-align: middle;" /></a>';
	}

// this table hold the index, the tree drawing gifs and the info about the role
	$this->html .= $this->drawindent();
	if ($isbranch) {
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
		$this->html .= $this->bar;
		$this->html .= $this->emptybox;
	}
	$this->html .=  '<span style="padding-left: 1em">';

// if we've already done this entry skip the links and just tell the user
	if (!$drawchildren) {
		$this->html .= '<b>' . $object['name'] . '</b>: ';
		$this->html .= ' see the entry above';
	}
	else{
		$this->html .= '<a href="' .
					xarModURL('roles',
						 'admin',
						 'modifyrole',
						 array('uid'=>$object['uid'])) .' ">' .$object['name'] . '</a>: &nbsp;';
		$this->html .= count($this->getsubgroups($object['uid'])) . ' subgroups';
		$this->html .= ' | ' . $object['users'] . ' users</span>';
	}


// we've finished this row; now do the children of this role
	$this->html .= $isbranch ? '<div class="xarleaf" id="leaf' . $this->nodeindex . '" >' : '';
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
 * getRole: gets a single role
 *
 * Retrieves a single role (user or group) from the roles repository
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   integer
 * @return  role object
 * @throws  none
 * @todo    none
*/
 	function getRole($uid)
	{
// retrieve the object's data from the repository
// set up and execute the query
		$query = "SELECT *
                  FROM $this->rolestable
                  WHERE xar_uid = $uid";
		//Execute the query, bail if an exception was thrown
		$result = $this->dbconn->Execute($query);
		if (!$result) return;

// set the data in an array
		list($uid,$name,$type,$parentid,$uname,$email,$pass,
		$date_reg,$val_code,$state,$auth_module) = $result->fields;

		$pargs = array('uid'=>$uid,
						'name'=>$name,
						'type'=>$type,
						'parentid'=>$parentid,
						'uname'=>$uname,
						'email'=>$email,
						'pass'=>$pass,
						'date_reg'=>$date_reg,
						'val_code'=>$val_code,
						'state'=>$state,
						'auth_module'=>$auth_module);

// create and return the role object
		return new xarRole($pargs);
	}

/**
 * findRole: finds a single role based on its name
 *
 * Retrieves a single role object from the Roles repository
 * This is a convenience class for module developers
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string
 * @return  role object
 * @throws  none
 * @todo    none
*/
 	function findRole($name)
	{
// retrieve the object's data from the repository
// set up and execute the query
		$query = "SELECT *
                  FROM $this->rolestable
                  WHERE xar_name = '$name'";
		//Execute the query, bail if an exception was thrown

		$result = $this->dbconn->Execute($query);
		if (!$result) return;

        if (!$result->EOF) {
            // set the data in an array
            list($uid,$name,$type,$parentid,$uname,$email,$pass,
                 $date_reg,$val_code,$state,$auth_module) = $result->fields;
            $pargs = array('uid'=>$uid,
                           'name'=>$name,
                           'type'=>$type,
                           'parentid'=>$parentid,
                           'uname'=>$uname,
                           'email'=>$email,
                           'pass'=>$pass,
                           'date_reg'=>$date_reg,
                           'val_code'=>$val_code,
                           'state'=>$state,
                           'auth_module'=>$auth_module);

            // create and return the role object
            return new xarRole($pargs);
        }
	}

/**
 * makeMemberByName: makes a role a child of a group
 *
 * Creates an entry in the rolemembers table
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
 	function makeMemberByName($childname,$parentname)
	{
// retrieve the parent's data from the repository
		$query = "SELECT *
                  FROM $this->rolestable
                  WHERE xar_name = '$parentname'";
		//Execute the query, bail if an exception was thrown
		$result = $this->dbconn->Execute($query);
		if (!$result) return;

// create the parent object
		list($uid,$name,$type,$parentid,$uname,$email,$pass,
		$date_reg,$val_code,$state,$auth_module) = $result->fields;
		$pargs = array('uid'=>$uid,
						'name'=>$name,
						'type'=>$type,
						'parentid'=>$parentid,
						'uname'=>$uname,
						'email'=>$email,
						'pass'=>$pass,
						'date_reg'=>$date_reg,
						'val_code'=>$val_code,
						'state'=>$state,
						'auth_module'=>$auth_module);
		$parent =  new xarRole($pargs);

// retrieve the child's data from the repository
		$query = "SELECT *
                  FROM $this->rolestable
                  WHERE xar_name = '$childname'";
		//Execute the query, bail if an exception was thrown
		$result = $this->dbconn->Execute($query);
		if (!$result) return;

// create the child object
		list($uid,$name,$type,$parentid,$uname,$email,$pass,
		$date_reg,$val_code,$state,$auth_module) = $result->fields;
		$pargs = array('uid'=>$uid,
						'name'=>$name,
						'type'=>$type,
						'parentid'=>$parentid,
						'uname'=>$uname,
						'email'=>$email,
						'pass'=>$pass,
						'date_reg'=>$date_reg,
						'val_code'=>$val_code,
						'state'=>$state,
						'auth_module'=>$auth_module);
		$child =  new xarRole($pargs);

// done
		return $parent->addMember($child);
	}

/**
 * isRoot: defines the root of the roles hierarchy
 *
 * Creates an entry in the rolemembers table
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
		$query = "SELECT xar_uid
                  FROM $this->rolestable
                  WHERE xar_name = '$rootname'";
		//Execute the query, bail if an exception was thrown
		$result = $this->dbconn->Execute($query);
		if (!$result) return;

// create the entry
		list($uid) = $result->fields;
		$query = "INSERT INTO $this->rolememberstable
				VALUES ($uid,0)";
		//Execute the query, bail if an exception was thrown
		if (!$this->dbconn->Execute($query)) return;

// done
		return true;
	}

/**
 * makeUser: add a new role object to the repository
 *
 * Creates an entry in the repository for a role object that has been created
 * This is a convenience method for module developers
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string
 * @param   string
 * @param   string
 * @return  boolean
 * @throws  none
 * @todo    create exception handling for bad input
*/
   function makeUser($name,$uname,$email,$pass='xaraya',$datereg='',$valcode='',$state=3,$authmodule=''){

//TODO: validate the email address
		if(empty($name) && empty($uname) || empty($email)) {
			$msg = xarML('You must enter a user name and a valid email address.',
						'roles');
			xarExceptionSet(XAR_USER_EXCEPTION,
						'DUPLICATE_DATA',
						 new DefaultUserException($msg));
			xarSessionSetVar('errormsg', _MODARGSERROR);
			return false;
		}

		// Confirm that this group or user does not already exist
			$query = "SELECT COUNT(*) FROM $this->rolestable
				  WHERE xar_uname = '$uname'";

		$result = $this->dbconn->Execute($query);
		if (!$result) return;

		list($count) = $result->fields;

		if ($count == 1) {
			$msg = xarML('This entry already exists.',
						'roles');
			xarExceptionSet(XAR_USER_EXCEPTION,
						'DUPLICATE_DATA',
						 new DefaultUserException($msg));
			xarSessionSetVar('errormsg', _GROUPALREADYEXISTS);
			return false;
		}

// create an ID for the user
		$nextId = $this->dbconn->genID($this->rolestable);

// set up the query and create the entry
		$nextIdprep = xarVarPrepForStore($nextId);
		$nameprep = xarVarPrepForStore($name);
		$unameprep = xarVarPrepForStore($uname);
		$emailprep = xarVarPrepForStore($email);
		$passprep = md5(xarVarPrepForStore($pass));
		$dateregprep = xarVarPrepForStore($datereg);
		$valcodeprep = xarVarPrepForStore($valcode);
		$stateprep = xarVarPrepForStore($state);
		$authmoduleprep = xarVarPrepForStore($authmodule);
		$query = "INSERT INTO $this->rolestable
					(xar_uid, xar_name, xar_type, xar_uname, xar_email, xar_pass,
					xar_date_reg, xar_valcode, xar_state, xar_auth_module)
				  VALUES ($nextIdprep, '$nameprep', 0, '$unameprep', '$emailprep', '$passprep',
				  '$dateregprep', '$valcodeprep', $stateprep, '$authmoduleprep')";
		if (!$this->dbconn->Execute($query)) return;

// done
		return true;
	}

/**
 * makeGroup: add a new role object to the repository
 *
 * Creates an entry in the repository for a role object that has been created
 * This is a convenience method for module developers
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   string
 * @return  boolean
 * @throws  none
 * @todo    create exception handling for bad input
*/
   function makeGroup($name){

// Confirm that this group or user does not already exist
		$query = "SELECT COUNT(*) FROM $this->rolestable
				  WHERE xar_name = '$name'";

		$result = $this->dbconn->Execute($query);
		if (!$result) return;

		list($count) = $result->fields;
		if ($count == 1) {
			$msg = xarML('This entry already exists.',
						'roles');
			xarExceptionSet(XAR_USER_EXCEPTION,
						'DUPLICATE_DATA',
						 new DefaultUserException($msg));
			xarSessionSetVar('errormsg', _GROUPALREADYEXISTS);
			return false;
		}

// create an ID for the group
		$nextId = $this->dbconn->genID($this->rolestable);

// set up the query and create the entry
		$nextIdprep = xarVarPrepForStore($nextId);
		$nameprep = xarVarPrepForStore($name);
		$query = "INSERT INTO $this->rolestable
					(xar_uid, xar_name, xar_type, xar_uname)
				  VALUES ($nextIdprep, '$nameprep', 1, '$nameprep')";
		if (!$this->dbconn->Execute($query)) return;

// done
		return true;
	}
}



/**
 * xarRole: class for the role object
 *
 * Represents a single role (user or group)
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @throws  none
 * @todo    none
*/
 class xarRole
{
	var $uid;           //the id of this user or group
	var $name;          //the name of this user or group
	var $type;          //the type of this role (0=user, 1=group)
	var $parentid;      //the id of the parent of this role
	var $uname;         //the user name (not used by groups)
	var $email;         //the email address (not used by groups)
	var $pass;          //the password (not used by groups)
	var $date_reg;      //the date of registration
	var $val_code;      //the validation code of this user or group
	var $state;         //the state of this user or group
	var $auth_module;   //no idea what this is (not used by groups)
	var $parentlevel;			//we use this just to store transient information

	var $dbconn;
	var $rolestable;
	var $rolememberstable;
	var $privilegestable;
	var $acltable;

	var $allprivileges;

/**
 * xarRole: constructor for the role object
 *
 * Retrieves a single role (user or group) from the roles repository
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   array
 * @return  role object
 * @throws  none
 * @todo    none
*/
    function xarRole($pargs)
    {
		extract($pargs);

		list($this->dbconn) = xarDBGetConn();
		$xartable = xarDBGetTables();
		$this->rolestable = $xartable['roles'];
		$this->rolememberstable = $xartable['rolemembers'];
		$this->privilegestable = $xartable['privileges'];
		$this->acltable = $xartable['security_acl'];

        $this->uid          = $uid;
        $this->name         = $name;
        $this->type         = $type;
        $this->parentid     = $parentid;
        $this->uname        = $uname;
        $this->email        = $email;
        $this->pass         = $pass;
        $this->state        = $state;
        $this->date_reg     = $date_reg;
        $this->val_code     = $val_code;
        $this->auth_module  = $auth_module;
        $this->parentlevel	= 0;

    }

/**
 * add: add a new role object to the repository
 *
 * Creates an entry in the repository for a role object that has been created
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
						'roles');
			xarExceptionSet(XAR_USER_EXCEPTION,
						'DUPLICATE_DATA',
						 new DefaultUserException($msg));
			xarSessionSetVar('errormsg', _MODARGSERROR);
			return false;
		}

//TODO: validate the email address
		if((empty($this->type)) && (empty($this->uname) || empty($this->email))) {
			$msg = xarML('You must enter a user name and a valid email address.',
						'roles');
			xarExceptionSet(XAR_USER_EXCEPTION,
						'DUPLICATE_DATA',
						 new DefaultUserException($msg));
			xarSessionSetVar('errormsg', _MODARGSERROR);
			return false;
		}

		// Confirm that this group or user does not already exist
		if ($this->type == 1) {
			$query = "SELECT COUNT(*) FROM $this->rolestable
				  WHERE xar_name = '$this->name'";
		}
		else {
			$query = "SELECT COUNT(*) FROM $this->rolestable
				  WHERE xar_uname = '$this->uname'";
		}

		$result = $this->dbconn->Execute($query);
		if (!$result) return;

		list($count) = $result->fields;

		if ($count == 1) {
			$msg = xarML('This entry already exists.',
						'roles');
			xarExceptionSet(XAR_USER_EXCEPTION,
						'DUPLICATE_DATA',
						 new DefaultUserException($msg));
			xarSessionSetVar('errormsg', _GROUPALREADYEXISTS);
			return false;
		}

		$nextId = $this->dbconn->genID($this->rolestable);

		if ($this->type == 1) {
			$nextIdprep = xarVarPrepForStore($nextId);
			$nameprep = xarVarPrepForStore($this->name);
			$typeprep = xarVarPrepForStore($this->type);
			$query = "INSERT INTO $this->rolestable
						(xar_uid, xar_name, xar_type)
					  VALUES ($nextIdprep, '$nameprep', $typeprep)";
		}
		else {
			$nextIdprep = xarVarPrepForStore($nextId);
			$nameprep = xarVarPrepForStore($this->name);
			$typeprep = xarVarPrepForStore($this->type);
			$unameprep = xarVarPrepForStore($this->uname);
			$emailprep = xarVarPrepForStore($this->email);
			$passprep = xarVarPrepForStore(md5($this->pass));
			$dateregprep = xarVarPrepForStore($this->date_reg);
			$stateprep = xarVarPrepForStore($this->state);
			$valcodeprep = xarVarPrepForStore($this->val_code);
			$authmodprep = xarVarPrepForStore($this->auth_module);
			$query = "INSERT INTO $this->rolestable
						(xar_uid, xar_name, xar_type, xar_uname, xar_email, xar_pass,
						xar_date_reg, xar_state, xar_valcode, xar_auth_module)
					  VALUES ($nextIdprep, '$nameprep', $typeprep, '$unameprep', '$emailprep',
					  '$passprep', '$dateregprep', $stateprep, '$valcodeprep', '$authmodprep')";
		}
		//Execute the query, bail if an exception was thrown
		if (!$this->dbconn->Execute($query)) return;

		$query = "SELECT MAX(xar_uid) FROM $this->rolestable";
		//Execute the query, bail if an exception was thrown
		$result = $this->dbconn->Execute($query);
		if (!$result) return;

		list($uid) = $result->fields;
		$this->uid = $uid;
		$parts = new xarRoles();
		$parentpart = $parts->getRole($this->parentid);
		return $parentpart->addMember($this);
	}


/**
 * addMember: adds a role to a group
 *
 * Make a user or group a member of another group.
 * A user of group can have any number of parents or children..
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   role object
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function addMember($member) {

// bail if the purported parent is not a group.
		if ($this->isUser()) return false;

// add the necessary entry to the rolemembers table
		$query = "INSERT INTO $this->rolememberstable
				VALUES (" . $member->getID() . "," . $this->getID() . ")";
		if (!$this->dbconn->Execute($query)) return;

// for children that are users
// add 1 to the users field of the parent group. This is for display purposes.
		if ($member->isUser()) {

// get the current count
			$query = "SELECT xar_users FROM $this->rolestable
					WHERE xar_uid =" . $this->getID();
			$result = $this->dbconn->Execute($query);
			if (!$result) return;

// add 1 and update.
			list($users) = $result->fields;
			$users = $users + 1;
			$query = "UPDATE " . $this->rolestable .
					" SET " .
					"xar_users = $users" .
					" WHERE xar_uid =" . $this->getID();
			if (!$this->dbconn->Execute($query)) return;
		}

// done
		return true;
    }

/**
 * removeMember: removes a role from a group
 *
 * Removes a user or group as an entry of another group.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   role object
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function removeMember($member) {

// delete the relevant entry from the rolemembers table
		$query = "DELETE FROM $this->rolememberstable
              WHERE xar_uid=" . $member->getID() .
              " AND xar_parentid=" . $this->getID();
		if (!$this->dbconn->Execute($query)) return;

// for children that are users
// subtract 1 from the users field of the parent group. This is for display purposes.
		if ($member->isUser()) {

// get the current count.
			$query = "SELECT xar_users FROM $this->rolestable
					WHERE xar_uid =" . $this->getID();
			$result = $this->dbconn->Execute($query);
			if (!$result) return;

// subtract 1 and update.
			list($users) = $result->fields;
			$users = $users - 1;
			$query = "UPDATE " . $this->rolestable .
					" SET " .
					"xar_users = $users" .
					" WHERE xar_uid =" . $this->getID();
			if (!$this->dbconn->Execute($query)) return;
		}

// done
		return true;
    }

    function update()
    {
		$query = 	"UPDATE " . $this->rolestable .
					" SET " .
					"xar_name = '$this->name'," .
					"xar_type = $this->type," .
					"xar_uname = '$this->uname'," .
					"xar_email = '$this->email'," .
					"xar_pass = '$this->pass'," .
					"xar_state = '$this->state'" .
					" WHERE xar_uid = " . $this->getID();

//Execute the query, bail if an exception was thrown
		if (!$this->dbconn->Execute($query)) return;
		return true;
    }

/**
 * remove: remove a role from the repository
 *
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    flag illegal deletes
*/
	function remove(){

// get a list of all relevant entries in the rolemembers table
// where this role is the child
		$query = "SELECT xar_parentid FROM $this->rolememberstable
              WHERE xar_uid=" . $this->getID();
		//Execute the query, bail if an exception was thrown
		$result = $this->dbconn->Execute($query);
		if (!$result) return;

// get the Roles class so we can use its methods
		$parts = new xarRoles();

// go through the list, retrieving the roles and detaching each one
// we need to do it this way because the method removeMember is more than just
// a simple SQL DELETE
		while(!$result->EOF) {
			list($parentid) = $result->fields;
			$parentpart = $parts->getRole($parentid);
			$parentpart->removeMember($this);
			$result->MoveNext();
		}

// delete the relevant entry in the roles table
		$query = "DELETE FROM $this->rolestable
              WHERE xar_uid=" . $this->getID();
		//Execute the query, bail if an exception was thrown
		if (!$this->dbconn->Execute($query)) return;

//done
		return true;
	}

    function getAllPrivileges() {
	if ((!isset($allprivileges)) || count($allprivileges)==0) {
			$query = "SELECT xar_privileges.xar_pid,
						xar_privileges.xar_name
						FROM $this->privilegestable
						ORDER BY xar_privileges.xar_name";

			$result = $this->dbconn->Execute($query);
			if (!$result) return;
			$privileges = array();
			$ind = 0;
			while(!$result->EOF) {
				list($pid, $name) = $result->fields;
				$ind = $ind + 1;
				$privileges[$ind] = array('pid' => $pid,
								   'name' => $name);
				$result->MoveNext();
			}
			$allprivileges = $privileges;
			return $privileges;
		}
		else {
			return $allprivileges;
		}
    }

	function getAssignedPrivileges(){

		$query = "SELECT xar_pid,
					xar_name,
					xar_realm,
					xar_module,
					xar_component,
					xar_instance,
					xar_level,
					xar_description
					FROM $this->privilegestable INNER JOIN $this->acltable
					ON xar_privileges.xar_pid = xar_security_acl.xar_permid
					WHERE xar_security_acl.xar_partid = $this->uid";
		//Execute the query, bail if an exception was thrown
		$result = $this->dbconn->Execute($query);
		if (!$result) return;

		include_once 'modules/privileges/xarprivileges.php';
		$privileges = array();
		while(!$result->EOF) {
			list($pid,$name, $realm, $module, $component, $instance, $level,
			$description) = $result->fields;
			$perm = new xarPrivilege(array('pid' => $pid,
							   'name' => $name,
							   'realm' => $realm,
							   'module' => $module,
							   'component' => $component,
							   'instance' => $instance,
							   'level' => $level,
							   'description' => $description,
							   'parentid' => 0));
			array_push($privileges, $perm);
			$result->MoveNext();
		}
		return $privileges;
	}

	function getInheritedPrivileges() {
		$ancestors = $this->getAncestors();
		$inherited = array();
		foreach ($ancestors as $ancestor) {
			$perms = $ancestor->getAssignedPrivileges();
			while (list($key,$perm) = each($perms)){
				array_push($inherited, $perm);
			}
		}
	}

/**
 * assignPrivilege: assigns a privilege to a role
 *
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   privilege object
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function assignPrivilege($perm) {

// create an entry in the privmembers table
		$query = "INSERT INTO $this->acltable
				VALUES (" . $this->getID() . "," . $perm->getID() . ")";
		if (!$this->dbconn->Execute($query)) return;

		return true;
    }

/**
 * removePrivilege: removes a privilege from a role
 *
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   privilege object
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function removePrivilege($perm) {

// remove an entry from the privmembers table
		$query = "DELETE FROM $this->acltable
              WHERE xar_partid=" . $this->uid .
              " AND xar_permid=" . $perm->getID();
		if (!$this->dbconn->Execute($query)) return;

		return true;
    }

/**
 * getUsers: get the members of a group that are users
 *
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function getUsers($state='') {

// set up the query and get the data
	if ($state == '') {
		$query = "SELECT xar_roles.xar_uid,
						xar_roles.xar_name,
						xar_roles.xar_type,
						xar_roles.xar_uname,
						xar_roles.xar_email,
						xar_roles.xar_pass,
						xar_roles.xar_auth_module
						FROM $this->rolestable INNER JOIN $this->rolememberstable
						ON xar_roles.xar_uid = xar_rolemembers.xar_uid
						WHERE xar_roles.xar_type = 0
						AND xar_rolemembers.xar_parentid = $this->uid";
	}
	else {
		$query = "SELECT xar_roles.xar_uid,
						xar_roles.xar_name,
						xar_roles.xar_type,
						xar_roles.xar_uname,
						xar_roles.xar_email,
						xar_roles.xar_pass,
						xar_roles.xar_auth_module
						FROM $this->rolestable INNER JOIN $this->rolememberstable
						ON xar_roles.xar_uid = xar_rolemembers.xar_uid
						WHERE xar_roles.xar_type = 0 AND xar_state = $state
						AND xar_rolemembers.xar_parentid = $this->uid";
	}
		$result = $this->dbconn->Execute($query);
		if (!$result) return;

// arrange the data in an array of role objects
		$users = array();
		while(!$result->EOF) {
		list($uid,$name,$type,$uname,$email,$pass,
		$date_reg,$val_code,$state,$auth_module) = $result->fields;
		$pargs = array('uid'=>$uid,
						'name'=>$name,
						'type'=>$type,
						'parentid'=>$parentid,
						'uname'=>$uname,
						'email'=>$email,
						'pass'=>$pass,
						'date_reg'=>$date_reg,
						'val_code'=>$val_code,
						'state'=>$state,
						'auth_module'=>$auth_module);
			array_push($users,new xarRole($pargs));
			$result->MoveNext();
		}

//done
		return $users;
    }

/**
 * getParents: returns the parent objects of a role
 *
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  array of role objects
 * @throws  none
 * @todo    none
*/
    function getParents()
    {
// create an array to hold the objects to be returned
		$parents = array();

// if this is the root return an empty array
		if ($this->getID() == 1) return $parents;

// if this is a group pick up the uids using getgroups()
// May be faster
		if (!$this->isUser()) {

// get the roles class
			$parts = new xarRoles();

// look for the parent uids and create role objects from them
			foreach($parts->getgroups() as $group){
				if ($group['uid'] == $this->uid){
					array_push($parents, $parts->getRole($group['parentid']));
				}
			}
		}else {

// if this is a user just perform a SELECT on the rolemembers table
			$query = "SELECT xar_roles.*
						FROM $this->rolestable INNER JOIN $this->rolememberstable
						ON xar_roles.xar_uid = xar_rolemembers.xar_parentid
						WHERE xar_rolemembers.xar_uid = $this->uid";
			$result = $this->dbconn->Execute($query);
			if (!$result) return;

// collect the table values and use them to create new role objects
			while(!$result->EOF) {
		list($uid,$name,$type,$parentid,$uname,$email,$pass,
		$date_reg,$val_code,$state,$auth_module) = $result->fields;
		$pargs = array('uid'=>$uid,
						'name'=>$name,
						'type'=>$type,
						'parentid'=>$parentid,
						'uname'=>$uname,
						'email'=>$email,
						'pass'=>$pass,
						'date_reg'=>$date_reg,
						'val_code'=>$val_code,
						'state'=>$state,
						'auth_module'=>$auth_module);
				array_push($parents, new xarRole($pargs));
				$result->MoveNext();
			}
		}
// done
		return $parents;
	}

/**
 * getAncestors: returns all objects in the roles hierarchy above a role
 *
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  array of role objects
 * @throws  none
 * @todo    if PHP does static variables we can make this a lot easier
*/
    function getAncestors()
    {
// if this is the root return an empty array
		if ($this->getID() == 1) return array();

// start by getting an array of the parents
		$parents = $this->getParents();
		$parents1 = array();
		foreach ($parents as $key => $parent) {
			$parents[$key]->setLevel(1);
		}

//Get the parent field for each parent
		while (list($key,$parent) = each ($parents)) {
		    $plevel = $parent->getLevel() + 1;
		    $ancestors = $parent->getParents();
			foreach ($ancestors as $key1 => $ancestor) {
				$ancestors[$key1]->setLevel($plevel);
				array_push($parents, $ancestors[$key1]);
			}
		}

		$ancestors = array();
//If this is a new ancestor add to the end of the array
			foreach ($parents as $parent){
				$iscontained = false;
				foreach ($ancestors as $ancestor){
					if ($parent->isEqual($ancestor)) {
						$iscontained = true;
						break;
					}
				}
			if (!$iscontained) array_push($ancestors, $parent);
			}

//done
		return $ancestors;
    }

/**
 * isEqual: checks whether two roles are equal
 *
 * Two role objects are considered equal if they have the same uid.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function isEqual($role)
    {
    	return $this->getID() == $role->getID();
	}

/**
 * isUser: checks whether this role is a user
 *
 * Users have type = 0.
 * Groups have type = 1.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function isUser()
    {
    	return $this->getType() == 0;
	}

/**
 * isParent: checks whether a role is a parent of this one
 *
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function isParent($role)
    {
    	$parents = $this->getParents();
    	foreach ($parents as $parent) {
    		if ($role->isEqual($parent)) return true;
    	}
    	return false;
	}

/**
 * isAncestor: checks whether a role is an ancestor of this one
 *
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function isAncestor($role)
    {
    	$ancestors = $this->getAncestors();
    	foreach ($ancestors as $ancestor) {
    		if ($role->isEqual($ancestor)) return true;
    	}
    	return false;
	}

/**
 * getPrivileges: returns the privileges in the privileges repository
 *
 * Returns an array of all the privileges objects
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  array of privilege objects
 * @throws  none
 * @todo    none
*/
	function getPrivileges(){
/*	// start by getting an array of all the privileges
			$query = "SELECT * FROM $this->privilegestable";
			$result = $this->dbconn->Execute($query);
			if (!$result) return;

			$privileges = array();
			while(!$result->EOF) {
				list($pid,$name,$realm,$module,$component,$instance,$level,$description) = $result->fields;
				$pargs = array('pid' => $pid,
							'name' => $name,
							'realm'=>$realm,
							'module'=>$module,
							'component'=>$component,
							'instance'=>$instance,
							'level'=>$level,
							'description'=>$description);
				array_push($privileges,new xarPrivilege($pargs))
				$result->MoveNext();
			}

	// start by getting an array of the parents
			$parents = $part->getParents();

	//Get the parent field for each parent
			while (list($key, $parent) = each($parents)) {
				$ancestors = $parent->getParents();
				foreach ($ancestors as $ancestor) {

	//If this is a new ancestor add to the end of the array
					$iscontained = false;
					foreach ($parents as $parent){
						if ($parent->isEqual($ancestor)) $iscontained = true;
					}
					if (!$iscontained) array_push($parents, $ancestor);
				}
			}
*/	}

/**
 * Gets and Sets
 *
 * Get and set methods for the class variables
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   n/a
 * @return  n/a
 * @throws  none
 * @todo    none
*/
	function getID()            {return $this->uid;}
    function getName()          {return $this->name;}
    function getType()          {return $this->type;}
    function getUser()          {return $this->uname;}
    function getEmail()         {return $this->email;}
    function getPass()          {return $this->pass;}
    function getState()         {return $this->state;}
    function getDateReg()       {return $this->date_reg;}
    function getValCode()       {return $this->val_code;}
    function getAuthModule()    {return $this->auth_module;}
    function getLevel()         {return $this->parentlevel;}

    function setName($var)      {$this->name = $var;}
    function setParent($var)    {$this->parentid = $var;}
    function setUser($var)      {$this->uname = $var;}
    function setEmail($var)     {$this->email = $var;}
    function setPass($var)      {$this->pass = $var;}
    function setState($var)     {$this->state = $var;}
    function setDateReg($var)   {$this->date_reg = $var;}
    function setValCode($var)   {$this->val_code = $var;}
    function setAuthModule($var) {$this->auth_module = $var;}
    function setLevel($var)     {$this->parentlevel = $var;}
}

?>
