<?php
/**
 * Privileges administration API
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @link http://xaraya.com/index.php/release/1098.html
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */

/**
 * xarMasks: class for the mask repository
 *
 * Represents the repository containing all security masks
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @todo    evaluate scoping
*/
class xarMasks extends Object
{
    public    static $levels;
    protected static $dbconn;
    protected static $privilegestable;
    protected static $privmemberstable;
    protected static $maskstable;
    protected static $modulestable;
    protected static $realmstable;
    protected static $acltable;
    protected static $allmasks;
    protected static $instancestable;
    protected static $levelstable;
    protected static $privsetstable;

    protected static $privilegeset;

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
    static function initialize()
    {
        self::$dbconn =& xarDBGetConn();
        xarModAPILoad('privileges');
        $xartable =& xarDBGetTables();
        self::$privilegestable = $xartable['privileges'];
        self::$privmemberstable = $xartable['privmembers'];
        self::$maskstable = $xartable['security_masks'];
        self::$modulestable = $xartable['modules'];
        self::$realmstable = $xartable['security_realms'];
        self::$acltable = $xartable['security_acl'];
        self::$instancestable = $xartable['security_instances'];
        self::$modulestable = $xartable['modules'];

        // @todo refactor callers to do this directly
        sys::import('modules.privileges.class.securitylevel');
        self::$levels = SecurityLevel::$displayMap;
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
    static function getmasks($module = 'All',$component='All')
    {
        self::initialize();
        // TODO: try to do all this a bit more compact and without xarMod_GetBaseInfo
        // TODO: sort on the name of the mod again
        // TODO: evaluate ambiguous signature of this method: does 'All' mean get *only* the masks which apply to all modules
        //       or get *all* masks.
        $bindvars = array();
        // base query, only the where clauses differ
        $query = "SELECT masks.xar_sid, masks.xar_name, realms.xar_name,
                  modules.xar_name, masks.xar_component, masks.xar_instance,
                  masks.xar_level, masks.xar_description
                  FROM " . self::$maskstable . "masks
                  LEFT JOIN " . self::$realmstable. "realms ON masks.xar_realmid = realms.xar_rid ";
        if ($module == '' || $module == 'All') {
            $modId = 0;
            if ($component == '' || $component == 'All') {
                // nothing differs
            } else {
                $query .= "WHERE (xar_component IN (?,?,?) ";
                $bindvars = array($component,'All','None');
            }
        } else {
            $modInfo = xarMod_GetBaseInfo($module);
            $modId = $modInfo['systemid'];
            if ($component == '' || $component == 'All') {
                $query = "WHERE xar_modid = ? ";
                $bindvars = array($modId);
            } else {
                $query = "WHERE  xar_modid = ? AND
                                 xar_component IN (?,?,?) ";
                $bindvars = array($modId,$component,'All','None');
            }
        }
        $query .= "ORDER BY masks.xar_modid, masks.xar_component, masks.xar_name";

        $stmt = self::$dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);

        $masks = array();
        while($result->next()) {
            list($sid, $name, $realm, $modid, $component, $instance, $level,
                    $description) = $result->fields;
            $pargs = array('sid' => $sid,
                               'name' => $name,
                               'realm' => is_null($realm) ? 'All' : $realm,
                               'module' => $module,
                               'component' => $component,
                               'instance' => $instance,
                               'level' => $level,
                               'description' => $description);
            array_push($masks, new xarMask($pargs));
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
     * @todo    almost the same as privileges register method
    */
    static function register($name,$realm,$module,$component,$instance,$level,$description='')
    {
        self::initialize();
        // Check if the mask has already been registered, and update it if necessary.
        // FIXME: make mask names unique across modules (+ across realms) ?
        // FIXME: is module/name enough? Perhaps revisit this with realms in mind.
        // FIXME: we use 0 for modId for now, as we would have to make two separate queries otherwise
        //        one for xar_modid=<value>, another for xar_modid is null.
        if($module == 'All') {
            $modId = 0;
        } else {
            $modInfo = xarMod_GetBaseInfo($module);
            $modId= $modInfo['systemid'];
        }

        $realmid = null;
        if($realm != 'All') {
            $stmt = self::$dbconn->prepareStatement('SELECT xar_rid FROM '.self::$realmstable .' WHERE xar_name=?');
            $result = $stmt->executeQuery(array($realm),ResultSet::FETCHMODE_ASSOC);
            if($result->next()) $realmid = $result->getInt('xar_rid');
        }

        $query = "SELECT xar_sid FROM " . self::$maskstable  . " WHERE xar_modid = ? AND xar_name = ?";
        $stmt = self::$dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($modId, $name));

        try {
            self::$dbconn->begin();
            if ($result->first()) {
                list($sid) = $result->fields;
                $query = "UPDATE " . self::$maskstable .
                          " SET xar_realmid = ?, xar_component = ?,
                              xar_instance = ?, xar_level = ?,
                              xar_description = ?
                          WHERE xar_sid = ?";
                $bindvars = array($realmid, $component, $instance, $level,
                                  $description, $sid);
            } else {
                $query = "INSERT INTO " . self::$maskstable .
                          " (xar_name, xar_realmid, xar_modid, xar_component, xar_instance, xar_level, xar_description)
                          VALUES (?,?,?,?,?,?,?)";
                $bindvars = array(
                                  $name, $realmid, $modId, $component, $instance, $level,
                                  $description);
            }
            $stmt = self::$dbconn->prepareStatement($query);
            $stmt->executeUpdate($bindvars);
            self::$dbconn->commit();
        } catch (SQLException $e) {
            self::$dbconn->rollback();
            throw $e;
        }
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
     */
    static function unregister($name)
    {
        self::initialize();
        $query = "DELETE FROM " . self::$maskstable . " WHERE xar_name = ?";
        self::$dbconn->Execute($query,array($name));
        return true;
    }

    /**
     * removeMasks: remove the masks registered by a module from the database
     * *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   module name
     * @return  boolean
    */
    static function removemasks($module)
    {
        self::initialize();
        if($module=='All') {
            $modId = 0;
        } else {
            $modInfo = xarMod_GetBaseInfo($module);
            $modId = $modInfo['systemid'];
        }
        $query = "DELETE FROM " . self::$maskstable . " WHERE xar_modid = ?";
        //Execute the query, bail if an exception was thrown
        self::$dbconn->Execute($query,array($modId));
        return true;
    }


    /**
     * xarSecLevel: Return an access level based on its name
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   string $levelname the
     * @return  int access level
    */
    static function xarSecLevel($levelname)
    {
        // If we could somehow turn a string into the name of a class constant, that would be great.
        sys::import('modules.privileges.class.securitylevel');
        return SecurityLevel::get($levelname);
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
    */
    static function xarSecurityCheck($mask,$catch=1,$component='',$instance='',$module='',$rolename='',$pnrealm=0,$pnlevel=0)
    {
        self::initialize();
        $userID = xarSessionGetVar('uid');
        xarLogMessage("PRIVS: uid in security check: $userID");
        if ($userID == XARUSER_LAST_RESORT) return true;

        $maskname = $mask;
        $mask =  self::getMask($mask);
        if (!$mask) {
            // <mikespub> moved this whole $module thing where it's actually used, i.e. for
            // error reporting only. If you want to override masks with this someday, move
            // it back before the self::getMask($mask) or wherever :-)

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
            throw new Exception($msg);
        }

        // insert any component overrides
        if ($component != '') $mask->setComponent($component);
        // insert any instance overrides
        if ($instance != '') $mask->setInstance($instance);

        // insert any overrides of realm and level
        // this is for PostNuke backward compatibility
        if ($pnrealm != '') $mask->setRealm($pnrealm);
        if ($pnlevel != '') $mask->setLevel($pnlevel);
        $realmvalue = xarModGetVar('privileges', 'realmvalue');
        if (strpos($realmvalue,'string:') === 0) {
            $textvalue = substr($realmvalue,7);
            $realmvalue = 'string';
        } else {
            $textvalue = '';
        }
        switch($realmvalue) {
            //jojodee - should we not have a mapping so we can define realms of different types?
            //perhaps something for later.
            // <mrb> i dont grok this, theme can be realm?
            case "theme":
                $mask->setRealm(xarModGetVar('themes', 'default'));
                break;
            case "domain":
                $host = xarServerGetHost();
                $parts = explode('.',$host);
                if (count($parts) < 2) {
                    $mask->setRealm('All');
                } else { //doublecheck
                    if ($parts[0]=='www') {
                        $mask->setRealm($parts[1]);
                    } else {
                        $mask->setRealm($parts[0]);
                    }
                }
                break;
            case "string":
                $mask->setRealm($textvalue);
                break;
            case "group":
                //get some info on the user
                $thisname=xarUserGetVar('uname');
                $role = xarUFindRole($thisname);
                $parent='Everybody'; //set a default
                //We now have primary parent implemented
                //Use primary parent if implemented else get first parent??
                //TODO: this needs to be reviewed
                $useprimary = xarModGetVar('roles','setprimaryparent');
                if ($useprimary) { //grab the primary parent
                    $parent=$role->getPrimaryParent(); //string value
                }else { //we don't have a primary parent so use the first parent?? ... hmm review
                    foreach ($role->getParents() as $parent) {
                      $parent = $parent->name;
                        break;
                    }
                }
                $mask->setRealm($parent);
                break;
            case "none":
            default:
                $mask->setRealm('All');
                break;
        }

        // normalize the mask now - its properties won't change below
        $mask->normalize();


        // get the Roles class
        sys::import('modules.roles.class.roles');
        $roles = new xarRoles();

        // get the uid of the role we will check against
        // an empty role means take the current user
        if ($rolename == '') {
            // mrb: again?
            $userID = xarSessionGetVar('uid');
            if (empty($userID)) {
                $userID = _XAR_ID_UNREGISTERED;
            }
            $role = $roles->getRole($userID);
        }
        else {
            $role = $roles->findRole($rolename);
        }

        // check if we already have the irreducible set of privileges for the current user
        if (!xarVarIsCached('Security.Variables','privilegeset.'.$mask->module) || !empty($rolename)) {
            // get the privileges and test against them
            $privileges = self::irreducibleset(array('roles' => array($role)),$mask->module);

            // leave this as same-page caching, even if the db cache is finished
            // if this is the current user, save the irreducible set of privileges to cache
            if ($rolename == '') {
                // normalize all privileges before saving, to avoid re-doing that every time
                self::normalizeprivset($privileges);
                xarVarSetCached('Security.Variables','privilegeset.'.$mask->module,$privileges);
            }
        } else {
            // get the irreducible set of privileges for the current user from cache
            $privileges = xarVarGetCached('Security.Variables','privilegeset.'.$mask->module);
        }
        $pass = self::testprivileges($mask,$privileges,false,$role);

        //$pass = self::testprivileges($mask,self::getprivset($role),false);

        // check if the exception needs to be caught here or not

        if ($catch && !$pass) {
            if (xarModGetVar('privileges','exceptionredirect') && !xarUserIsLoggedIn()) {
                //authsystem will handle the authentication
                //Redirect to login for anon users, and take their current url as well for redirect after login
                $requrl = xarServerGetCurrentUrl(array(),false);
                xarResponseRedirect(xarModURL('authsystem','user','showloginform',array('redirecturl'=> $requrl),false));
            } else {
                $msg = xarML('You don\'t have the correct privileges for this operation');
                throw new Exception($msg);
            }
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
    */
    static function forgetprivsets()
    {
        $query = "DELETE FROM " . self::$privsetstable;
        self::$dbconn->executeUpdate($query);
        return true;
    }

    /**
     * getprivset: get a role's irreducible set of privileges from the db
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   role object
     * @return  array containing the role's ancestors and privileges
    */
    static function getprivset($role)
    {
        static $selStmt = null;
        static $insStmt = null;
        
        if (xarVarIsCached('Security.getprivset', $role)) {
            return xarVarGetCached('Security.getprivset', $role);
        }
        $query = "SELECT xar_set FROM " . self::$privsetstable . " WHERE xar_uid =?";
        if(!isset($selStmt)) $selStmt = self::$dbconn->prepareStatement($query);
        
        $result = $selStmt->executeQuery(array($role->getID()));

        if (!$result->first()) {
            $privileges = self::$irreducibleset(array('roles' => array($role)));
            $query = "INSERT INTO " . self::$privsetstable . " VALUES (?,?)";
            $bindvars = array($role->getID(), serialize($privileges));
            if(!isset($insStmt)) $insStmt = self::$dbconn->prepareStatement($query);
            $insStmt->executeUpdate($bindvars);
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
    */
    static function irreducibleset($coreset,$module='')
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
                $privileges = array_merge(array($priv),$privileges);
                $privileges = array_merge($priv->getDescendants(),$privileges);
            }
            $privs = array();
            foreach ($privileges as $priv) {
                $privModule = strtolower($priv->getModule());
                if ($privModule == "all" || $privModule == $module) {
                    $privs[] = $priv;
                }
            }
            $coreset['privileges'] = array_merge($coreset['privileges'],$privs);
            $parents = array_merge($parents,$role->getParents());
        }
        // CHECKME: Tail recursion, could be removed
        $coreset['children'] = self::irreducibleset(array('roles' => $parents),$module);
        return $coreset;
    }

    /**
     * normalizeprivset: apply the normalize() method on all privileges in a privilege set
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   array representing the privilege set
     * @return  none
    */
    static function normalizeprivset(&$privset)
    {
        if (isset($privset['privileges']) && is_array($privset['privileges'])) {
            foreach (array_keys($privset['privileges']) as $id) {
                $privset['privileges'][$id]->normalize();
            }
        }
        if (isset($privset['children']) && is_array($privset['children'])) {
            self::normalizeprivset($privset['children']);
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
    */
    static function testprivileges($mask,$privilegeset,$pass,$role='')
    {
        $candebug = (xarSessionGetVar('uid') == xarModGetVar('privileges','tester'));
        $test = xarModGetVar('privileges','test') && $candebug;
        $testdeny = xarModGetVar('privileges','testdeny') && $candebug;
        $testmask = xarModGetVar('privileges','testmask');
        $matched = false;
        $pass = false;
        // Note : DENY rules override all others here...
        $thistest = $testdeny && ($testmask == $mask->getName() || $testmask == "All");
        foreach ($privilegeset['privileges'] as $privilege) {
            if($thistest) {
                echo "Comparing <font color='blue'>[" . $privilege->present() . "]</font> against  <font color='green'>[". $mask->present() . "]</font> <b>for deny</b>. ";
                if (($privilege->level == 0) && ($privilege->includes($mask))) echo "<font color='blue'>[" . $privilege->getName() . "]</font> matches. ";
                else echo "no match found. ";
                /* debugging output */
                $msg = "Comparing for DENY.<font color='blue'>".$privilege->present(). "</blue>\n  ".
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
                if (!xarModGetVar('privileges','inheritdeny') && is_object($role)) {
                    if($thistest) {
                        echo "We don't inherit <strong>denys</strong>, ";
                    }
                    $privs = $role->getAssignedPrivileges();
                    $isassigned = false;
                    foreach ($privs as $priv) {
                        if ($privilege == $priv) {
                            if($thistest) {
                                echo "but <font color='blue'>[" . $privilege->present() . "] wins</font> because directly assigned. Continuing with other checks...<br />";
                            }
                            return false;
                            break;
                        }
                    }
                    if($thistest) {
                        echo "and <font color='blue'>[" . $privilege->present() . "] wins</font> is not directly assigned. Ignoring..<br/>";
                    }
                } else {
                    if($thistest) {
                        echo "<font color='blue'>[" . $privilege->present() . "] wins</font>. Continuing with other checks...<br />";
                    }
                    return false;
                }
            } else {
                if($thistest) {
                    echo "Continuing with other checks..<br />";
                }
            }
        }

        foreach ($privilegeset['privileges'] as $privilege) {
            if($test && ($testmask == $mask->getName() || $testmask == "All")) {
                echo "Comparing <font color='blue'>[" . $privilege->present() . "]</font> and <font color='green'>[" . $mask->present() . "]</font>. ";
                $msg = "Comparing \n  Privilege: ".$privilege->present().
                    "\n       Mask: ".$mask->present();
                xarLogMessage($msg, XARLOG_LEVEL_DEBUG);
            }
            if ($privilege->includes($mask)) {
                if ($privilege->implies($mask)) {
                    if($test && ($testmask == $mask->getName() || $testmask == "All")) {
                        echo "<font color='blue'>[" . $privilege->getName() . "] wins</font>. Privilege includes mask. Privilege level greater or equal. Continuing with other checks.. <br />";
                        $msg = $privilege->getName() . " WINS! ".
                            "Privilege includes mask. ".
                            "Privilege level greater or equal.\n";
                        xarLogMessage($msg, XARLOG_LEVEL_DEBUG);
                    }
                    if (!$pass || $privilege->getLevel() > $pass->getLevel()) $pass = $privilege;
                }
                else {
                    if($test && ($testmask == $mask->getName() || $testmask == "All")) {
                        echo "<font color='green'>[" . $mask->getName() . "] wins</font>. Privilege includes mask. Privilege level lesser. Continuing with other checks..<br />";
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
                    if($test && ($testmask == $mask->getName() || $testmask == "All")) {
                        echo "<font color='blue'>[" . $privilege->getName() . "] wins</font>. Mask includes privilege. Privilege level greater or equal. Continuing with other checks.. <br />";
                        $msg = $privilege->getName()." WINS! ".
                            "Mask includes privilege. Privilege level ".
                            "greater or equal.\n";
                        xarLogMessage($msg, XARLOG_LEVEL_DEBUG);
                    }
                    if (!$pass || $privilege->getLevel() > $pass->getLevel()) $pass = $privilege;
                    $matched = true;
                }
                else {
                    if($test && ($testmask == $mask->getName() || $testmask == "All")) {
                        echo "<font color='blue'>[" . $mask->getName() . "] wins</font>. Mask includes privilege. Privilege level lesser. Continuing with other checks..<br />";
                        $msg = $mask->getName()." MATCHES! ".
                            "Mask includes privilege. Privilege level ".
                            "lesser.\n";
                        xarLogMessage($msg, XARLOG_LEVEL_DEBUG);
                    }
                }
            }
            else {
                if($test && ($testmask == $mask->getName() || $testmask == "All")) {
                    echo "<font color='red'>no match</font>. Continuing with other checks..<br />";
                    $msg = "NO MATCH.\n";
                    xarLogMessage($msg, XARLOG_LEVEL_DEBUG);
                }
            }
        }
        if (!$matched && ($privilegeset['children'] != array()))
            $pass = self::testprivileges($mask,$privilegeset['children'],$pass,$role);
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
    */
    static function getMask($name,$module="All",$component="All",$suppresscache=FALSE)
    {
        self::initialize();
        // check if we already have the definition of this mask
        // TODO: try to do this without xarMod_GetBaseInfo
        if ($suppresscache || !xarVarIsCached('Security.Masks',$name)) {
            $bindvars = array();
            $query = "SELECT masks.xar_sid AS sid, masks.xar_name AS name, realms.xar_name AS realm,
                             mods.xar_name AS module, masks.xar_component as component, masks.xar_instance AS instance,
                             masks.xar_level AS level, masks.xar_description AS description
                      FROM " . self::$maskstable . " masks LEFT JOIN " . self::$modulestable . " mods ON masks.xar_modid = mods.xar_id
                                                           LEFT JOIN " . self::$realmstable .  " realms ON masks.xar_realmid = realms.xar_rid
                      WHERE  masks.xar_name = ? ";
            $bindvars[] = $name;
            if($module != 'All') {
                $query .= " AND mods.xar_name = ?";
                $bindvars[] = $module;
            }
            if($component != 'All') {
                $query .= " AND masks.xar_component = ? ";
                $bindvars[] = strtolower($component);
            }
            $stmt = self::$dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
            if(!$result->next()) return; // Mask isn't there.
            $pargs = $result->getRow();
            if(is_null($pargs['module'])) $pargs['module'] = 'All';
            if(is_null($pargs['realm']))  $pargs['realm']  = 'All';
//            $pargs['module'] = xarModGetNameFromID($pargs['module']);
            xarVarSetCached('Security.Masks',$name,$pargs);
        } else {
            $pargs = xarVarGetCached('Security.Masks',$name);
        }
        sys::import('modules.privileges.class.mask');
        return new xarMask($pargs);
    }
}

?>
