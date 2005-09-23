<?php
/**
 * File: $Id$
 *
 * Purpose of file:  Privileges administration API
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
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


//quick hack to show some of what the functions are doing
//set to 1 to activate
define('XARDBG_WINNOW', 0);
define('XARDBG_TEST', 0);
define('XARDBG_TESTDENY', 0);
define('XARDBG_MASK', 'All');
define('XAR_ENABLE_WINNOW', 0);

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
    var $levelstable;
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
    function xarMasks()
    {
        $this->dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();
        $this->privilegestable = $xartable['privileges'];
        $this->privmemberstable = $xartable['privmembers'];
        $this->maskstable = $xartable['security_masks'];
        $this->modulestable = $xartable['modules'];
        $this->modulestatestable = $xartable['module_states'];
        $this->realmstable = $xartable['security_realms'];
        $this->acltable = $xartable['security_acl'];
        $this->instancestable = $xartable['security_instances'];
        $this->levelstable = $xartable['security_levels'];
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
    function getmasks($module = 'All',$component='All')
    {
        $bindvars = array();
        if ($module == '' || $module == 'All') {
            if ($component == '' || $component == 'All') {
                $query = "SELECT * FROM $this->maskstable ORDER BY xar_module, xar_component, xar_name";
            }
            else {
                $query = "SELECT * FROM $this->maskstable
                        WHERE (xar_component = ?)
                        OR (xar_component = 'All')
                        OR (xar_component = 'None')
                        ORDER BY xar_module, xar_component, xar_name";
                $bindvars = array($component);
            }
        }
        else {
            if ($component == '' || $component == 'All') {
                $query = "SELECT * FROM $this->maskstable
                        WHERE xar_module = ? ORDER BY xar_module, xar_component, xar_name";
                $bindvars = array($module);
            }
            else {
                $query = "SELECT *
                    FROM $this->maskstable WHERE (xar_module = ?)
                    AND ((xar_component = ?)
                    OR (xar_component = 'All')
                    OR (xar_component = 'None'))
                    ORDER BY xar_module, xar_component, xar_name";
                $bindvars = array($module,$component);
            }
        }
        $result = $this->dbconn->Execute($query,$bindvars);
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
        // Check if the mask has already been registered, and update it if necessary.
// FIXME: make mask names unique across modules (+ across realms) ?
        // FIXME: is module/name enough? Perhaps revisit this with realms in mind.
        $query = 'SELECT xar_sid FROM ' . $this->maskstable
            . ' WHERE xar_module = ? AND xar_name = ?';
        $result = $this->dbconn->Execute($query, array($module, $name));
        if (!$result) return;
        if (!$result->EOF) {
            list($sid) = $result->fields;
            $query = 'UPDATE ' . $this->maskstable
                . ' SET xar_realm = ?, xar_component = ?,'
                . ' xar_instance = ?, xar_level = ?,'
                . ' xar_description = ?'
                . ' WHERE xar_sid = ?';
            $bindvars = array(
                $realm, $component, $instance, $level,
                $description, $sid
            );
        } else {
            $query = "INSERT INTO $this->maskstable (
                        xar_sid, xar_name, xar_realm, xar_module, xar_component, 
                        xar_instance, xar_level, xar_description) 
                      VALUES (?,?,?,?,?,?,?,?)";
            $bindvars = array(
                $this->dbconn->genID($this->maskstable),
                          $name, $realm, $module, $component, $instance, $level,
                $description
            );
        }

        if (!$this->dbconn->Execute($query,$bindvars)) return;
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
        $query = "DELETE FROM $this->maskstable WHERE xar_name = ?";
        if (!$this->dbconn->Execute($query,array($name))) return;
        return true;
    }

/**
 * removeMasks: remove the masks registered by a module from the database
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
        $query = "DELETE FROM $this->maskstable WHERE xar_module = ?";
        //Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query,array($module))) return;
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
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException($msg));
            return;
        }
        if ((($privs1 == array()) || ($privs1 == '')) &&
            (($privs2 == array()) || ($privs2 == ''))) return array();

        if (!XAR_ENABLE_WINNOW) {
            return array_merge($privs1,$privs2);
        }
        else {
            $privs1 = array_merge($privs1,$privs2);
            $privs2 = array();
            foreach ($privs1 as $key1 => $priv1) {
                $matched = false;
                foreach ($privs2 as $key2 => $priv2) {
                    if(XARDBG_WINNOW) {
                        $w1 = $priv1->matchesexactly($priv2) ? "<font color='green'>Yes</font>" : "<font color='red'>No</font>";
                        $w2 = $priv2->matchesexactly($priv1) ? "<font color='green'>Yes</font>"  : "<font color='red'>No</font>";
                        echo "Winnowing: ";
                        echo $priv1->getName(). " implies " . $priv2->getName() . ": " . $w1 . "<br />";
                        echo $priv2->getName(). " implies " . $priv1->getName() . ": " . $w2 . "<br /><br />";
                        /* debug output */
                        $w1 = $priv1->matchesexactly($priv2) ? "YES" : "NO";
                        $w2 = $priv2->matchesexactly($priv1) ? "YES" : "NO";
                        $msg = "Winnowing: \n  ".$priv1->getName()." implies ".
                                $priv2->getName()."?: ".$w1."\n  ".
                                $priv2->getName()." implies ".
                                $priv1->getName()."?: ".$w2;
                        xarLogMessage($msg, XARLOG_LEVEL_DEBUG);
                    }
                    if ($priv1->matchesexactly($priv2)) {
                        $privs3 = $privs2;
                        $notmoved = true;
                        foreach ($privs3 as $priv3) if($priv3->matchesexactly($priv1)) $notmoved = false;
                        if ($notmoved) $privs2[$key2] = $priv1;
                        else if (!$priv1->matchesexactly($priv2)) array_splice($privs2,$key2);
                        $matched = true;
                    }
                    elseif ($priv2->matchesexactly($priv1) || $priv1->matchesexactly($priv2)) {
                        $matched = true;
                        break;
                    }
                }
                if(!$matched) $privs2[] = $priv1;
            }
            return $privs2;
        }
    }

/**
 * xarSecLevel: Return an access level based on its name
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   access level description
 * @return  access level
 * @throws  none
 * @todo    none
*/

    function xarSecLevel($levelname)
    {
        if (xarVarIsCached('Security.xarSecLevel', $levelname)) {
            return xarVarGetCached('Security.xarSecLevel', $levelname);
        }
        $query = "SELECT xar_level FROM $this->levelstable
                    WHERE xar_leveltext = ?";
        $result = $this->dbconn->Execute($query,array($levelname));
        if (!$result) return;
        $level = -1;
        if (!$result->EOF) list($level) = $result->fields;
        xarVarSetCached('Security.xarSecLevel', $levelname, $level);
        return $level;
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

    function xarSecurityCheck($mask,$catch=1,$component='',$instance='',$module='',$rolename='',$pnrealm=0,$pnlevel=0)
    {
        $maskname = $mask;
        $mask =  $this->getMask($mask);
//        if($mask->getName() == "pnLegacyMask") {
//            echo "realm: " . $pnrealm . "\n" . "level: " . $pnlevel;exit;
//        }
//        else return 1;
        if (!$mask) {
            // <mikespub> moved this whole $module thing where it's actually used, i.e. for
            // error reporting only. If you want to override masks with this someday, move
            // it back before the $this->getMask($mask) or wherever :-)

            // get the masks pertaining to the current module and the component requested
            // <mikespub> why do you need this in the first place ?
            if ($module == '') list($module) = xarRequestGetInfo();

            // I'm a bit lost on this line. Does this var ever get set?
            // <mikespub> this gets set in xarBlock_render, to replace the xarModSetVar /
            // xarModGetVar combination you used before (although $module will generally
            // not be 'blocks', so I have no idea why this is needed anyway)
            if ($module == 'blocks' && xarVarIsCached('Security.Variables','currentmodule'))
            $module = xarVarGetCached('Security.Variables','currentmodule');

            if ($component == "") {
                $msg = xarML('Did not find mask #(1) registered for an unspecified component in module #(2)', $maskname, $module);
            }
            else {
                $msg = xarML('Did not find mask #(1) registered for component #(2) in module #(3)', $maskname, $component, $module);
            }
            xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA',
                           new DefaultUserException($msg));
            return;
        }

        // insert any component overrides
        if ($component != '') $mask->setComponent($component);
        // insert any instance overrides
        if ($instance != '') $mask->setInstance($instance);

        // insert any overrides of realm and level
        // this is for PostNuke backward compatibility
        if ($pnrealm != '') $mask->setRealm($pnrealm);
        if ($pnlevel != '') $mask->setLevel($pnlevel);

        // normalize the mask now - its properties won't change below
        $mask->normalize();

        // check if we already have the irreducible set of privileges for the current user
        if (!xarVarIsCached('Security.Variables','privilegeset.'.$mask->module) || !empty($rolename)) {

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
            $privileges = $this->irreducibleset(array('roles' => array($role)),$mask->module);

            // leave this as same-page caching, even if the db cache is finished
            // if this is the current user, save the irreducible set of privileges to cache
            if ($rolename == '') {
                // normalize all privileges before saving, to avoid re-doing that every time
                $this->normalizeprivset($privileges);
                xarVarSetCached('Security.Variables','privilegeset.'.$mask->module,$privileges);
            }
        } else {
            // get the irreducible set of privileges for the current user from cache
            $privileges = xarVarGetCached('Security.Variables','privilegeset.'.$mask->module);
        }

        $pass = $this->testprivileges($mask,$privileges,false);

        // $pass = $this->testprivileges($mask,$this->getprivset($role),false);

        // check if the exception needs to be caught here or not
        if ($catch && !$pass) {
            $msg = xarML('No privilege for #(1)',$mask->getName());
            xarErrorSet(XAR_USER_EXCEPTION, 'NO_PRIVILEGES',
                           new DefaultUserException($msg));
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
        if (xarVarIsCached('Security.getprivset', $role)) {
            return xarVarGetCached('Security.getprivset', $role);
        }
        $query = "SELECT xar_set FROM $this->privsetstable WHERE xar_uid =?";
        $result = $this->dbconn->Execute($query,array($role->getID()));
        if (!$result) return;

        if ($result->EOF) {
            $privileges = $this->irreducibleset(array('roles' => array($role)));
            $query = "INSERT INTO $this->privsetstable VALUES (?,?)";
            $bindvars = array($role->getID(), serialize($privileges));
            if (!$this->dbconn->Execute($query,$bindvars)) return;
            return $privileges;
        } else {
            list($serprivs) = $result->fields;
        }
        // MrB: Why the unserialize here?
        xarVarSetCached('Security.getprivset', $role, unserialize($serprivs));
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
    function irreducibleset($coreset,$module='')
    {
        if (!empty($module)) {
            $module = strtolower($module);
        }

        $roles = $coreset['roles'];
        $coreset['privileges'] = array();
        $coreset['children'] = array();
        if (count($roles) == 0) return $coreset;

        $parents = array();
        foreach ($roles as $role) {
            // FIXME: evaluate why role is empty
            // Below (hack) fix added by Rabbitt (suggested by mikespub on the devel mailing list)
            if (empty($role)) continue;

            $privs = $role->getAssignedPrivileges();
            $privileges = array();
            foreach ($privs as $priv) {
                $privileges = $this->winnow(array($priv),$privileges);
                $privileges = $this->winnow($priv->getDescendants(),$privileges);
            }
            $privs = array();
            foreach ($privileges as $priv) {
                $privModule = strtolower($priv->getModule());
                if ($privModule == "all" || $privModule == $module) {
                    $privs[] = $priv;
                }
            }
            $coreset['privileges'] = $this->winnow($coreset['privileges'],$privs);
            $parents = array_merge($parents,$role->getParents());
        }
        $coreset['children'] = $this->irreducibleset(array('roles' => $parents),$module);
        return $coreset;
    }

/**
 * normalizeprivset: apply the normalize() method on all privileges in a privilege set
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   array representing the privilege set
 * @return  none
 * @throws  none
 * @todo    none
*/
    function normalizeprivset(&$privset)
    {
        if (isset($privset['privileges']) && is_array($privset['privileges'])) {
            foreach (array_keys($privset['privileges']) as $id) {
                $privset['privileges'][$id]->normalize();
            }
        }
        if (isset($privset['children']) && is_array($privset['children'])) {
            $this->normalizeprivset($privset['children']);
        }
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
        $pass = false;

        // Note : DENY rules override all others here...
        foreach ($privilegeset['privileges'] as $privilege) {
            if(XARDBG_TESTDENY && (XARDBG_MASK == $mask->getName() || XARDBG_MASK == "All")) {
                echo "<br />Comparing " . $privilege->present() . " against " . $mask->present() . " <b>for deny</b>. ";
                if (($privilege->level == 0) && ($privilege->includes($mask))) echo $privilege->getName() . " found. ";
                else echo "not found. ";
                /* debugging output */
                $msg = "Comparing for DENY.".$privilege->present(). "\n  ".
                    $mask->present();
                if (($privilege->level == 0) &&
                    ($privilege->includes($mask))) {
                    $msg .= $privilege->getName() . " FOUND. \n";
                } else {
                    $msg .= " NOT FOUND. \n";
                }
                xarLogMessage($msg, XARLOG_LEVEL_DEBUG);
            }
            if ($privilege->level == 0 && $privilege->includes($mask)) {
                return false;
            }
        }

        foreach ($privilegeset['privileges'] as $privilege) {
            if(XARDBG_TEST && (XARDBG_MASK == $mask->getName() || XARDBG_MASK == "All")) {
                echo "<br />Comparing <br />" . $privilege->present() . " and <br />" . $mask->present() . ". <br />";
                $msg = "Comparing \n  Privilege: ".$privilege->present().
                    "\n       Mask: ".$mask->present();
                xarLogMessage($msg, XARLOG_LEVEL_DEBUG);
            }
            if ($privilege->includes($mask)) {
                if ($privilege->implies($mask)) {
                    if(XARDBG_TEST && (XARDBG_MASK == $mask->getName() || XARDBG_MASK == "All")) {
                        echo $privilege->getName() . " <font color='blue'>wins</font>. Continuing .. <br />Privilege includes mask. Privilege level greater or equal.<br />";
                        $msg = $privilege->getName() . " WINS! ".
                            "Privilege includes mask. ".
                            "Privilege level greater or equal.\n";
                        xarLogMessage($msg, XARLOG_LEVEL_DEBUG);
                    }
                    if (!$pass || $privilege->getLevel() > $pass->getLevel()) $pass = $privilege;
                }
                else {
                    if(XARDBG_TEST && (XARDBG_MASK == $mask->getName() || XARDBG_MASK == "All")) {
                        echo $mask->getName() . " <font color='blue'>wins</font>. Continuing .. <br />Privilege includes mask. Privilege level lesser.<br />";
                        $msg = $mask->getName() . " MATCHES! ".
                                "Privilege includes mask. Privilege level ".
                                "lesser.\n";
                        xarLogMessage($msg, XARLOG_LEVEL_DEBUG);
                    }
                }
                $matched = true;
            }
            elseif ($mask->includes($privilege)) {
                if ($privilege->level >= $mask->level) {
                    if(XARDBG_TEST && (XARDBG_MASK == $mask->getName() || XARDBG_MASK == "All")) {
                        echo $privilege->getName() . " <font color='blue'>wins</font>. Continuing .. <br />Mask includes privilege. Privilege level greater or equal.<br />";
                        $msg = $privilege->getName()." WINS! ".
                            "Mask includes privilege. Privilege level ".
                            "greater or equal.\n";
                        xarLogMessage($msg, XARLOG_LEVEL_DEBUG);
                    }
                    if (!$pass || $privilege->getLevel() > $pass->getLevel()) $pass = $privilege;
                    $matched = true;
                }
                else {
                    if(XARDBG_TEST && (XARDBG_MASK == $mask->getName() || XARDBG_MASK == "All")) {
                        echo $mask->getName() . " <font color='blue'>wins</font>. Continuing...<br />Mask includes privilege. Privilege level lesser.<br />";
                        $msg = $mask->getName()." MATCHES! ".
                            "Mask includes privilege. Privilege level ".
                            "lesser.\n";
                        xarLogMessage($msg, XARLOG_LEVEL_DEBUG);
                    }
                }
            }
            else {
                if(XARDBG_TEST && (XARDBG_MASK == $mask->getName() || XARDBG_MASK == "All")) {
                    echo "<font color='red'>no match</font>. Continuing...<br />";
                    $msg = "NO MATCH.\n";
                    xarLogMessage($msg, XARLOG_LEVEL_DEBUG);
                }
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
    function getMask($name,$module="All")
    {
        // check if we already have the definition of this mask
        if (!xarVarIsCached('Security.Masks',$name)) {
//Set up the query and get the data from the xarmasks table
            $bindvars = array();
// FIXME: make mask names unique across modules (+ across realms) ?
            if ($module == "All") {
                $query = "SELECT * FROM $this->maskstable WHERE xar_name= ?";
                $bindvars = array($name);
            }
            else {
                $module = strtolower($module);
                $query = "SELECT * FROM $this->maskstable
                            WHERE xar_name= ? AND xar_module = ?";
                $bindvars = array($name,$module);
            }
            $result = $this->dbconn->Execute($query,$bindvars);
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
            // make privilege wizard URLs relative, for easier migration of sites
            if (!empty($instance['header']) && $instance['header'] == 'external' && !empty($instance['query'])) {
                $base = xarServerGetBaseURL();
                $instance['query'] = str_replace($base,'',$instance['query']);
            }

            // Check if the instance already exists.
            // The instance is uniquely defined by its module, component and header.
            // FIXME: since the header is just a label, it probably should not be
            // treated as key information here. Do we need some further unique (within a
            // module and component) name for an instance, independant of the header label?
            $query = 'SELECT xar_iid FROM ' . $this->instancestable
                . ' WHERE xar_module = ? AND xar_component = ? AND xar_header = ?';
            $result = $this->dbconn->execute($query, array($module, $type, $instance['header']));
            if (!$result) return;
            if (!$result->EOF) {
                // Instance exists: update it.
                list($iid) = $result->fields;
                $query = 'UPDATE ' . $this->instancestable
                    . ' SET xar_query = ?, xar_limit = ?,'
                    . ' xar_propagate = ?, xar_instancetable2 = ?, xar_instancechildid = ?,'
                    . ' xar_instanceparentid = ?, xar_description = ?'
                    . ' WHERE xar_iid = ?';
                $bindvars = array(
                    $instance['query'], $instance['limit'],
                    $propagate, $table2, $childID, $parentID,
                    $description, $iid
                );
            } else {
                $query = "INSERT INTO $this->instancestable (
                            xar_iid, xar_module, xar_component, xar_header, 
                            xar_query, xar_limit, xar_propagate, 
                            xar_instancetable2, xar_instancechildid, 
                            xar_instanceparentid, xar_description)
                          VALUES (?,?,?,?,?,?,?,?,?,?,?)";
                $bindvars = array(
                    $this->dbconn->genID($this->instancestable),
                              $module, $type, $instance['header'],
                              $instance['query'], $instance['limit'],
                              $propagate, $table2, $childID, $parentID,
                    $description
                );
            }

            if (!$this->dbconn->Execute($query,$bindvars)) return;
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
        $query = "DELETE FROM $this->instancestable WHERE xar_module = ?";
        //Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query,array($module))) return;
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
        $query = "INSERT INTO $this->privilegestable (
                    xar_pid, xar_name, xar_realm, xar_module, xar_component, 
                    xar_instance, xar_level, xar_description)
                  VALUES (?,?,?,?,?,?,?,?)";
        $bindvars = array($this->dbconn->genID($this->privilegestable),
                          $name, $realm, $module, $component,
                          $instance, $level, $description);

        if (!$this->dbconn->Execute($query,$bindvars)) return;
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
        $query = "INSERT INTO $this->acltable VALUES (?,?)";
        $bindvars = array($roleid,$privid);
        if (!$this->dbconn->Execute($query,$bindvars)) return;

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
    function getprivileges()
    {
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
                        FROM $this->privilegestable p, $this->privmemberstable pm
                        WHERE p.xar_pid = pm.xar_pid
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
    function gettoplevelprivileges($arg)
    {
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
//    }
//    else {
//        return $alltoplevelprivileges;
//    }
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
    function getrealms()
    {
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
    function getmodules()
    {
    if ((!isset($allmodules)) || count($allmodules)==0) {
            $query = "SELECT modules.xar_id,
                        modules.xar_name
                        FROM $this->modulestable modules LEFT JOIN $this->modulestatestable states
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
                               'name' => 'All',
                               'display' => 'All');
//          $modules[] = array('id' => 0,
//                             'name' => 'None');

// add the modules from the database
// TODO: maybe remove the key, don't really need it
            while(!$result->EOF) {
                list($mid, $name) = $result->fields;
                $modules[] = array('id' => $mid,
                                   'name' => $name,
                                   //'display' => xarModGetDisplayableName($name),
                                   'display' => ucfirst($name));
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
    function getcomponents($module)
    {
        $query = "SELECT DISTINCT xar_component
                    FROM $this->instancestable
                    WHERE xar_module= ?
                    ORDER BY xar_component";

        $result = $this->dbconn->Execute($query,array($module));
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
    function getinstances($module, $component)
    {


        if ($component =="All") {
            $componentstring = "";
        }
        else {
            $componentstring = "AND ";
        }
        $query = "SELECT xar_header, xar_query, xar_limit
                    FROM $this->instancestable
                    WHERE xar_module= ? AND xar_component= ?
                     ORDER BY xar_component,xar_iid";
        $bindvars = array($module,$component);

        $instances = array();
        $result = $this->dbconn->Execute($query,$bindvars);
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
                $msg = xarML('A query is missing in component #(1) of module #(2)', $component, $module);

                xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA',
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

    function getprivilegefast($pid)
    {
        foreach($this->getprivileges() as $privilege){
            if ($privilege['pid'] == $pid) return $privilege;
        }
        return false;
    }

    function getChildren($pid)
    {
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
    function returnPrivilege($pid,$name,$realm,$module,$component,$instances,$level)
    {

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
        $query = "SELECT * FROM $this->privilegestable WHERE xar_pid = ?";
        //Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query,array($pid));
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
        $query = "SELECT * FROM $this->privilegestable WHERE xar_name = ?";
        //Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query,array($name));
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
 * findPrivilegesForModule: finds the privileges assigned to a module
 *
 * Retrieves an of privilege objects from the Privileges repository
 * This is a convenience class for module developers
 *
 * @author  Richard Cave<rcave@xaraya.com>
 * @access  public
 * @param   string
 * @return  privilege object
 * @throws  none
 * @todo    none
*/
    function findPrivilegesForModule($module)
    {
        $privileges = array();
        $query = "SELECT * FROM $this->privilegestable WHERE xar_module = ?";
        //Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query,array($module));
        if (!$result) return;
        for (; !$result->EOF; $result->MoveNext()) {
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
            $privileges[] = new xarPrivilege($pargs);
        }
        // Close result set
        $result->Close();
        return $privileges;
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
                  FROM $this->privilegestable WHERE xar_name = ?";
        //Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query,array($parentname));
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
        $query = "SELECT * FROM $this->privilegestable WHERE xar_name = ?";
        //Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query,array($childname));
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
        $priv = $this->findPrivilege($rootname);
        $priv->makeEntry();
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
    var $normalform;    //the normalized form of this privilege

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

        $this->dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();
        $this->privilegestable = $xartable['privileges'];
        $this->privmemberstable = $xartable['privmembers'];
        $this->rolestable = $xartable['roles'];
        $this->acltable = $xartable['security_acl'];

        $this->sid          = (int) $sid;
        $this->name         = $name;
        $this->realm        = $realm;
        $this->module       = $module;
        $this->component    = $component;
        $this->instance     = $instance;
        $this->level        = (int) $level;
        $this->description  = $description;
    }

    function present()
    {
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
 * @param   integer   adds  Number of additional instance parts to add to the array
 * @return  array of strings
 * @throws  none
 * @todo    none
*/
    function normalize($adds=0)
    {
        if (isset($this->normalform)) {
            if (empty($adds)) return $this->normalform;
            $normalform = $this->normalform;
        } else {
            $normalform = array();
            $normalform[] = strtolower($this->getLevel());
            $normalform[] = strtolower($this->getRealm());
            $normalform[] = strtolower($this->getModule());
            $normalform[] = strtolower($this->getComponent());
            $thisinstance = strtolower($this->getInstance());
            $thisinstance = str_replace('myself',xarSessionGetVar('uid'),$thisinstance);
            $normalform   = array_merge($normalform, explode(':', $thisinstance));
            $this->normalform = $normalform;
        }

        for ($i=0;$i<$adds;$i++) {
            $normalform[] = 'all';
        }

        return $normalform;
    }

/**
 * canonical: returns 2 normalized privileges or masks as arrays for comparison
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
        $p1count = count($p1);
        $p2count = count($p2);
        if ($p1count != $p2count) return false;
        for ($i=1; $i < $p1count; $i++) {
            $match = $match && ($p1[$i]==$p2[$i]);
        }
//        echo $this->present() . $mask->present() . $match;exit;
        return $match;
    }

/**
 * matchesexactly: checks the structure of one privilege against another
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
        if (isset($this->normalform)) {
            $p1 = $this->normalform;
        } else {
            $p1 = $this->normalize();
        }
        if (isset($mask->normalform)) {
            $p2 = $mask->normalform;
        } else {
            $p2 = $mask->normalize();
        }

        // match realm, module and component. bail if no match.
        for ($i=1;$i<4;$i++) {
            if (($p1[$i] != 'all') && ($p1[$i]!=$p2[$i])) {
                return false;
            }
        }

        // now match the instances
        $p1count = count($p1);
        $p2count = count($p2);
        if($p1count != $p2count) {
            if($p1count > $p2count) {
                $p = $p2;
                $p2 = $mask->normalize($p1count - $p2count);
            } else {
                $p = $p1;
                $p1 = $this->normalize($p2count - $p1count);
            }
            if (count($p) != 5) {
                $msg = xarML('#(1) and #(2) do not have the same instances. #(3) | #(4) | #(5)',$mask->getName(),$this->getName(),implode(',',$p2),implode(',',$p1),$this->present() . "|" . $mask->present());
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException($msg));
            }
        }
        for ( $i = 4, $p1count = count($p1); $i < $p1count; $i++) {
            if (($p1[$i] != 'all') && ($p1[$i]!=$p2[$i])) {
                return false;
            }
        }
        return true;
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

    function getID()
    {
        return $this->sid;
    }

    function getName()
    {
        return $this->name;
    }

    function getRealm()
    {
        return $this->realm;
    }

    function getModule()
    {
        return $this->module;
    }

    function getComponent()
    {
        return $this->component;
    }

    function getInstance()
    {
        return $this->instance;
    }

    function getLevel()
    {
        return $this->level;
    }

    function getDescription()
    {
        return $this->description;
    }

    function setName($var)
    {
        $this->name = $var;
    }

    function setRealm($var)
    {
        $this->realm = $var;
    }

    function setModule($var)
    {
        $this->module = $var;
    }

    function setComponent($var)
    {
        $this->component = $var;
    }

    function setInstance($var)
    {
        $this->instance = $var;
    }

    function setLevel($var)
    {
        $this->level = $var;
    }

    function setDescription($var)
    {
        $this->description = $var;
    }

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

        $this->dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();
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

        $this->pid          = (int) $pid;
        $this->name         = $name;
        $this->realm        = $realm;
        $this->module       = $module;
        $this->component    = $component;
        $this->instance     = $instance;
        $this->level        = (int) $level;
        $this->description  = $description;
        $this->parentid     = (int) $parentid;
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
   function add()
   {

        if(empty($this->name)) {
            $msg = xarML('You must enter a name.',
                        'privileges');
            xarErrorSet(XAR_USER_EXCEPTION,
                        'DUPLICATE_DATA',
                         new DefaultUserException($msg));
            xarSessionSetVar('errormsg', _MODARGSERROR);
            return false;
        }


// Confirm that this privilege name does not already exist
        $query = "SELECT COUNT(*) FROM $this->privilegestable
              WHERE xar_name = ?";

        $result = $this->dbconn->Execute($query,array($this->name));
        if (!$result) return;

        list($count) = $result->fields;

        if ($count == 1) {
            $msg = xarML('This entry already exists.',
                        'privileges');
            xarErrorSet(XAR_USER_EXCEPTION,
                        'DUPLICATE_DATA',
                         new DefaultUserException($msg));
            xarSessionSetVar('errormsg', _GROUPALREADYEXISTS);
            return;
        }

// create the insert query
        $query = "INSERT INTO $this->privilegestable
                    (xar_pid, xar_name, xar_realm, xar_module, xar_component, xar_instance, xar_level)
                  VALUES (?,?,?,?,?,?,?)";
        $bindvars = array($this->dbconn->genID($this->privilegestable),
                          $this->name, $this->realm, $this->module,
                          $this->component, $this->instance, $this->level);
        //Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query,$bindvars)) return;

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
    function makeEntry()
    {
        if ($this->isRootPrivilege()) return true;
        $query = "INSERT INTO $this->privmemberstable VALUES (?,0)";
        if (!$this->dbconn->Execute($query,array($this->getID()))) return;
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
    function addMember($member)
    {
        $query = "INSERT INTO $this->privmemberstable VALUES (?,?)";
        $bindvars = array($member->getID(), $this->getID());
        //Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query,$bindvars)) return;

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
    function removeMember($member)
    {

        $q = new xarQuery('SELECT', $this->privmemberstable, 'COUNT(*) AS count');
        $q->eq('xar_pid', $member->getID());
        if (!$q->run()) return;
        $total = $q->row();
        if($total['count'] == 0) return true;

        if($total['count'] > 1) {
            $q = new xarQuery('DELETE');
            $q->eq('xar_parentid', $this->getID());
        } else {
            $q = new xarQuery('UPDATE');
            $q->addfield('xar_parentid', 0);
        }
        $q->addtable($this->privmemberstable);
        $q->eq('xar_pid', $member->getID());
        if (!$q->run()) return;

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
                    " SET xar_name = ?, xar_realm = ?,
                          xar_module = ?, xar_component = ?,
                          xar_instance = ?, xar_level = ?
                      WHERE xar_pid = ?";
        $bindvars = array($this->name, $this->realm, $this->module,
                          $this->component, $this->instance, $this->level,
                          $this->getID());
        //Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query,$bindvars)) return;
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
    function remove()
    {

// set up the DELETE query
        $query = "DELETE FROM $this->privilegestable WHERE xar_pid=?";
//Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query,array($this->pid))) return;

// set up a query to get all the parents of this child
        $query = "SELECT xar_parentid FROM $this->privmemberstable
              WHERE xar_pid=?";
        //Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query,array($this->getID()));
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

// remove this child from the root privilege too
        $query = "DELETE FROM $this->privmemberstable WHERE xar_pid=? AND xar_parentid=0";
        if (!$this->dbconn->Execute($query,array($this->pid))) return;

// get all the roles this privilege was assigned to
        $roles = $this->getRoles();
// remove the role assignments for this privilege
        foreach ($roles as $role) {
            $this->removeRole($role);
        }

// get all the child privileges
        $children = $this->getChildren();
// remove the child privileges from this parent
        foreach ($children as $childperm) {
            $this->removeMember($childperm);
        }

// CHECKME: re-assign all child privileges to the roles that the parent was assigned to ?

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
                xar_partid = ? AND xar_permid = ?";
        $bindvars = array($role->getID(), $this->getID());
        $result = $this->dbconn->Execute($query,$bindvars);
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
    function getRoles()
    {

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
                    AND acl.xar_permid = ?";
//Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query,array($this->pid));
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
    function removeRole($role)
    {

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

// perform a SELECT on the privmembers table
        $query = "SELECT p.*, pm.xar_parentid
                    FROM $this->privilegestable p, $this->privmemberstable pm
                    WHERE p.xar_pid = pm.xar_parentid
                      AND pm.xar_pid = ?";
        $result = $this->dbconn->Execute($query,array($this->getID()));
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
        $cacheId = $this->getID();

        // we retrieve and cache everything at once now
        if (xarVarIsCached('Privileges.getChildren', 'cached')) {
            if (xarVarIsCached('Privileges.getChildren', $cacheId)) {
                return xarVarGetCached('Privileges.getChildren', $cacheId);
            } else {
                return array();
            }
        }

        // create an array to hold the objects to be returned
        $children = array();

        // if this is a user just perform a SELECT on the rolemembers table
        $query = "SELECT p.*, pm.xar_parentid
                    FROM $this->privilegestable p, $this->privmemberstable pm
                    WHERE p.xar_pid = pm.xar_pid";
        // retrieve all children of everyone at once
        //              AND pm.xar_parentid = " . $cacheId;
// Can't use caching here. The privs have changed
//        if (xarCore_getSystemVar('DB.UseADODBCache')){
//            $result =& $this->dbconn->CacheExecute(3600,$query);
//            if (!$result) return;
//        } else {
            $result = $this->dbconn->Execute($query);
            if (!$result) return;
//        }

        // collect the table values and use them to create new role objects
        while(!$result->EOF) {
            list($pid,$name,$realm,$module,$component,$instance,$level,$description,$parentid) = $result->fields;
            if (!isset($children[$parentid])) $children[$parentid] = array();
            $pargs = array('pid'=>$pid,
                            'name'=>$name,
                            'realm'=>$realm,
                            'module'=>$module,
                            'component'=>$component,
                            'instance'=>$instance,
                            'level'=>$level,
                            'description'=>$description,
                            'parentid' => $parentid);
            array_push($children[$parentid], new xarPrivilege($pargs));
            $result->MoveNext();
        }
        // done
        foreach (array_keys($children) as $parentid) {
            xarVarSetCached('Privileges.getChildren', $parentid, $children[$parentid]);
        }
        xarVarSetCached('Privileges.getChildren', 'cached', 1);
        if (isset($children[$cacheId])) {
            return $children[$cacheId];
        } else {
            return array();
        }
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
    function getID()
    {
        return $this->pid;
    }

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
    function isEmpty()
    {
        return $this->module == 'empty';
    }

/**
 * isParentPrivilege: checks whether a given privilege is a parent of this privilege
 *
 * This methods returns true if the privilege is a parent of this one
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function isParentPrivilege($privilege)
    {
        $privs = $this->getParents();
        foreach ($privs as $priv) {
            if ($privilege->isEqual($priv)) return true;
        }
        return false;
    }
/**
 * isRootPrivilege: checks whether this privilege is root privilege
 *
 * This methods returns true if this privilege is a root privilege
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function isRootPrivilege()
    {
        $q = new xarQuery('SELECT');
        $q->addtable($this->privilegestable,'p');
        $q->addtable($this->privmemberstable,'pm');
        $q->join('p.xar_pid','pm.xar_pid');
        $q->eq('pm.xar_pid',$this->getID());
        $q->eq('pm.xar_parentid',0);
        if(!$q->run()) return;
        return ($q->output() != array());
    }
}

?>
