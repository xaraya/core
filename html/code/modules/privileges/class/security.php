<?php
/**
 * Privileges administration API
 *
 * @package modules\privileges
 * @subpackage privileges
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1098.html
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */

/**
 * xarSecurityCheck: class for the mask repository
 *
 * Represents the repository containing all security masks
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @todo    evaluate scoping
*/
class xarSecurity extends Object
{
    const PRIVILEGES_PRIVILEGETYPE = 2;
    const PRIVILEGES_MASKTYPE = 3;
    const PRIVILEGES_ALL = 0;

    public    static $levels;
    protected static $dbconn;
    protected static $privilegestable;
    protected static $privmemberstable;
    protected static $modulestable;
    protected static $realmstable;
    protected static $acltable;
    protected static $allmasks;
    protected static $instancestable;
    protected static $levelstable;
    protected static $privsetstable;
    protected static $rolestable;

    protected static $privilegeset;

    // copy frequently-called module vars to static vars
    protected static $realmcomparison;
    protected static $test;
    protected static $testdeny;
    protected static $testmask;
    protected static $inheritdeny;
    protected static $realmvalue;
    protected static $exceptionredirect;
    protected static $maskbasedsecurity;
    protected static $maskbasedgrouplist;

    /**
     * xarMasks: constructor for the class
     *
     * Just sets up the db connection and initializes some variables
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  void
    */
    public static function initialize()
    {
        if (!empty(self::$dbconn)  && !empty(self::$privilegestable)) return;

        self::$dbconn = xarDB::getConn();
        xarMod::loadDbInfo('privileges','privileges');
        $xartable =& xarDB::getTables();
        self::$privilegestable = $xartable['privileges'];
        self::$privmemberstable = $xartable['privmembers'];
        self::$modulestable = $xartable['modules'];
        self::$realmstable = $xartable['security_realms'];
        self::$acltable = $xartable['security_acl'];
        self::$instancestable = $xartable['security_instances'];
        self::$modulestable = $xartable['modules'];
        self::$rolestable = $xartable['roles'];

        // CHECKME: do we need to be careful during installation here or not ?
        if (class_exists('xarModVars')) {
            self::$realmcomparison = xarModVars::get('privileges', 'realmcomparison');
            self::$test = xarModVars::get('privileges','test');
            self::$testdeny = xarModVars::get('privileges','testdeny');
            self::$testmask = xarModVars::get('privileges','testmask');
            self::$inheritdeny = xarModVars::get('privileges','inheritdeny');
            self::$realmvalue = xarModVars::get('privileges', 'realmvalue');
            self::$exceptionredirect = xarModVars::get('privileges','exceptionredirect');
            self::$maskbasedsecurity = xarModVars::get('privileges','maskbasedsecurity');
        }

        // @todo refactor callers to do this directly
        sys::import('modules.privileges.class.securitylevel');
        self::$levels = SecurityLevel::$displayMap;
    }

    /**
     * check: check a role's privileges against the masks of a component
     *
     * Checks the current group or user's privileges against a component
     * This function should be invoked every time a security check needs to be done
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   component string
     * @return  boolean
    */
    public static function check($mask,$catch=1,$component='',$instance='',$module='',$rolename='',$realm=0,$level=0)
    {
        self::initialize();
        $userID = xarSession::getVar('role_id');
        
        xarLog::message("xarSecurity::check: Testing user $userID against mask $mask", xarLog::LEVEL_INFO);
        
        if ($userID == XARUSER_LAST_RESORT) return true;

        // mask-based security check for the group(s) of the current user (optional)
        if (self::$maskbasedsecurity && !empty($mask) && empty($rolename)) { // $rolename must be empty
            $savedmask = $mask;
            if (empty(self::$maskbasedgrouplist)) {
                sys::import('modules.roles.class.roles');
                $role = xarRoles::get($userID);
                $grouplist = array();
                foreach ($role->getParents() as $parent) {
                    $grouplist[$parent->getID()] = 1;
                }
                self::$maskbasedgrouplist = array_keys($grouplist);
            }
            $found = 0;
            foreach (self::$maskbasedgrouplist as $groupid) {
                $val = xarModVars::get('privileges','SC:'.$savedmask.':'.$groupid);
                if (!empty($val)) {
                    // access for this group - stop checking and return true
                    return true;
                } elseif (isset($val)) {
                    // no access for this group - continue checking
                    $found = 1;
                }
            }
            if ($found == 1) {
                // no access for any groups - return false
                return false;
            }
        }

        $maskname = $mask;
        if (empty($maskname)) {
            sys::import('modules.privileges.class.mask');
            $mask = new xarMask();
        }
        else $mask =  self::getMask($mask);
        if (!$mask) {
            // <mikespub> moved this whole $module thing where it's actually used, i.e. for
            // error reporting only. If you want to override masks with this someday, move
            // it back before the self::getMask($mask) or wherever :-)

            // get the masks pertaining to the current module and the component requested
            // <mikespub> why do you need this in the first place ?
            if ($module == '') list($module) = xarController::$request->getInfo();

            // I'm a bit lost on this line. Does this var ever get set?
            // <mikespub> this gets set in xarBlock_render, to replace the xarModVars::set /
            // xarModVars::get combination you used before (although $module will generally
            // not be 'blocks', so I have no idea why this is needed anyway)
            if ($module == 'blocks' && xarVarIsCached('Security.Variables','currentmodule'))
            $module = xarVarGetCached('Security.Variables','currentmodule');

            if ($component == "") {
                $msg = xarML('Did not find mask #(1) registered for an unspecified component in module #(2)', $maskname, $module);
            }
            else {
                $msg = xarML('Did not find mask #(1) registered for component #(2) in module #(3)', $maskname, $component, $module);
            }
            xarLog::message("xarSecurity::check: " . $msg, xarLog::LEVEL_INFO);
            return false;
        }

        // Insert any overrides
        if (!empty($component)) $mask->setComponent($component);
        if (!empty($instance)) $mask->setInstance($instance);
        if (!empty($module)) {
            $mask->setModule($module);
            $mask->setModuleID($module);
        }
        if (!empty($realm)) $mask->setRealm($realm);
        if (!empty($level)) $mask->setLevel($level);

        $realmvalue = self::$realmvalue;
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
                $mask->setRealm(xarModVars::get('themes', 'default_theme'));
                break;
            case "domain":
                $host = xarServer::getHost();
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
                $useprimary = xarModVars::get('roles','setprimaryparent');
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

        // get the id of the role we will check against
        // an empty role means take the current user
        if ($rolename == '') {
            // mrb: again?
            $userID = xarSession::getVar('role_id');
            if (empty($userID)) {
                $userID = _XAR_ID_UNREGISTERED;
            }
            $role = xarRoles::get($userID);
        } else {
            $role = xarRoles::findRole($rolename);
        }

        // check if we already have the irreducible set of privileges for the current user
        if (($rolename == '') || ($rolename == xarUserGetVar('uname'))) {
            // We are checking the privileges of the current user
            // See if we have something cached
            if (!xarVarIsCached('Security.Variables','privilegeset')) {

            // CHECKME: why not cache this as module user variable instead of session ?
            //          That would save a lot of space for anonymous sessions...

                // No go from cache. Try and get it from the session
                sys::import('modules.privileges.class.privilege');
                $privileges = unserialize(xarSession::getVar('privilegeset'));
                // Check that privileges haven't been changed since we last cached the privilegeset
                $clearcache = xarModVars::get('privileges', 'clearcache');
                if (empty($privileges) || empty($privileges['updated']) || $clearcache > $privileges['updated']) {

                    // Still no go. Assemble the privleges
                    $privileges = self::irreducibleset(array('roles' => array($role)),$mask->module);
                    // Keep track of when this was last updated
                    if (is_array($privileges)) {
                        $privileges['updated'] = time();
                    }
                    // Save them to the sesssion too
                    xarSession::setVar('privilegeset',serialize($privileges));
                }

                // Save them to the cache
                xarVarSetCached('Security.Variables','privilegeset',$privileges);

            } else {
                // get the irreducible set of privileges for the current user from cache
                $privileges = xarVarGetCached('Security.Variables','privilegeset');
            }
        } else {
            // This is a different user, force recalculation
            // ChECKME: is this working?
            $privileges = self::irreducibleset(array('roles' => array($role)),$mask->module);
        }

        $pass = self::testprivileges($mask->normalform,$privileges,false,$role);

        //$pass = self::testprivileges($mask,self::getprivset($role),false);

        // mask-based security check for the groups of the current user (optional)
        if (self::$maskbasedsecurity && !empty($savedmask) && count(self::$maskbasedgrouplist) == 1) {
            $groupid = self::$maskbasedgrouplist[0];
            if (!empty($pass)) {
                xarModVars::set('privileges','SC:'.$savedmask.':'.$groupid, true);
            } else {
                xarModVars::set('privileges','SC:'.$savedmask.':'.$groupid, false);
            }
        }

        // check if the exception needs to be caught here or not

        if ($catch && !$pass) {
            $requrl = xarServer::getCurrentURL(array(),false);
            if (self::$exceptionredirect && !xarUserIsLoggedIn()) {
                // The current authentication module will handle the authentication
                //Redirect to login for anon users, and take their current url as well for redirect after login
                $redirectURL = xarModURL(xarModVars::get('roles','defaultauthmodule'),'user','showloginform',array('redirecturl'=> $requrl),false);
            } else {
                // Redirect to the privileges error page
                $redirectURL = xarModURL('privileges','user','errors',array('layout' => 'no_privileges', 'redirecturl'=> $requrl),false);
            }
            // Remove &amp; entites to prevent redirect breakage
            $redirectURL = str_replace('&amp;', '&', $redirectURL);
            $header = "Location: " . $redirectURL;
            header($header, TRUE, 302);
            exit();
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
    */
    public static function getMask($name,$modid=0,$component="All",$suppresscache=FALSE)
    {
        self::initialize();
        if ($suppresscache || !xarVarIsCached('Security.Masks',$name)) {
            $bindvars = array();
            $query = "SELECT masks.id AS id, masks.name AS name, realms.name AS realm,
                             module_id AS module_id, modules.name as module, masks.component as component, masks.instance AS instance,
                             masks.level AS level, masks.description AS description
                      FROM " . self::$privilegestable . " masks LEFT JOIN " . self::$realmstable .  " realms ON masks.realm_id = realms.id INNER JOIN  " . self::$modulestable . " modules ON masks.module_id = modules.id
                      WHERE  masks.name = ? ";
            $bindvars[] = $name;
            if(!empty($modid)) {
                $query .= " AND masks.module_id = ?";
                $bindvars[] = $modid;
            }
            if($component != 'All') {
                $query .= " AND masks.component = ? ";
                $bindvars[] = strtolower($component);
            }
            $query .= " AND itemtype = ? ";
            $bindvars[] = self::PRIVILEGES_MASKTYPE;
            $stmt = self::$dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
            if(!$result->next()) return; // Mask isn't there.
            $pargs = $result->getRow();
            if(is_null($pargs['realm']))  $pargs['realm']  = 'All';
            xarVarSetCached('Security.Masks',$name,$pargs);
        } else {
            $pargs = xarVarGetCached('Security.Masks',$name);
        }
        sys::import('modules.privileges.class.mask');
        return new xarMask($pargs);
    }

    /**
     * irreducibleset: assemble a role's irreducible set of privileges
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   array representing the initial node to start from
     * @return  nested array containing the role's ancestors and privileges
    */
    public static function irreducibleset($coreset,$module_id=null)
    {
        $roles = $coreset['roles'];
        unset($coreset['roles']);
        $coreset['privileges'] = array();
        $coreset['children'] = array();
        if (count($roles) === 0) return $coreset;
        if ($module_id === null) return $coreset;

        $parents = array();
        foreach ($roles as $role) {
            // FIXME: evaluate why role is empty
            // Below (hack) fix added by Rabbitt (suggested by mikespub on the devel mailing list)
            if (empty($role)) continue;

            $privs = $role->getAssignedPrivileges();
            $privileges = array();
            foreach ($privs as $priv) {
                $priv->normalize();
                $privs = array();
                $descendants = $priv->getDescendants();
                foreach ($descendants as $descendant) {
                    $descendant->normalize();
                    unset($descendant->dbconn);
                    $privs[] = $descendant->normalform;
                }
                unset($priv->dbconn);
                $privileges = array_merge(array($priv->normalform),$privileges);
                $privileges = array_merge($privs,$privileges);
            }
            $privs = array();
            foreach ($privileges as $priv) $privs[] = $priv;

            $coreset['privileges'] = array_merge($coreset['privileges'],$privs);
            $parents = array_merge($parents,$role->getParents());
        }
        // CHECKME: Tail recursion, could be removed
        $coreset['children'] = self::irreducibleset(array('roles' => $parents),$module_id);
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
    */
    public static function testprivileges($mask,$privilegeset,$pass,$role='')
    {
        $candebug = in_array(xarUser::getVar('id'),xarConfigVars::get(null, 'Site.User.DebugAdmins'));
        $test = self::$test && $candebug;
        $testdeny = self::$testdeny && $candebug;
        $testmask = self::$testmask;
        $matched = false;
        $pass = false;
        // Note : DENY rules override all others here...
        $thistest = $testdeny && ($testmask == $mask['name'] || $testmask == "All");
        foreach ($privilegeset['privileges'] as $privilege) {
            if($thistest) {
                echo "Comparing <font color='blue'>[" . self::present($privilege) . "]</font> against  <font color='green'>[". self::present($mask) . "]</font> <b>for deny</b>. ";
                if (($privilege['level'] == 0) && (self::includes($privilege,$mask))) echo "<font color='blue'>[" . $privilege['name'] . "]</font> matches. ";
                else echo "<font color='red'>no match</font>";
                /* debugging output */
                $msg = "Comparing for DENY.<font color='blue'>".self::present($privilege). "</blue>\n  ".
                    self::present($mask);
                if (($privilege['level'] == 0) &&
                    (self::includes($privilege,$mask))) {
                    $msg .= $privilege['name'] . " FOUND. \n";
                } else {
                    $msg .= " NOT FOUND. \n";
                }
                xarLog::message($msg, xarLog::LEVEL_DEBUG);
            }
            if ($privilege['level'] == 0 && self::includes($privilege,$mask)) {
                if (!self::$inheritdeny && is_object($role)) {
                    if($thistest) {
                        echo "We don't inherit <strong>denys</strong>, ";
                    }
                    $privs = $role->getAssignedPrivileges();
                    $isassigned = false;
                    foreach ($privs as $priv) {
                        if ($privilege == $priv) {
                            if($thistest) {
                                echo "but <font color='blue'>[" . self::present($privilege) . "] wins</font> because directly assigned. Continuing with other checks...<br />";
                            }
                            return false;
                            break;
                        }
                    }
                    if($thistest) {
                        echo "and <font color='blue'>[" . self::present($privilege) . "] wins</font> is not directly assigned. Ignoring..<br/>";
                    }
                } else {
                    if($thistest) {
                        echo "<font color='blue'>[" . self::present($privilege) . "] wins</font>. Continuing with other checks...<br />";
                    }
                    return false;
                }
            } else {
                if($thistest) {
                    echo "Continuing with other checks1..<br />";
                }
            }
        }
        foreach ($privilegeset['privileges'] as $privilege) {
            if($test && ($testmask == $mask['name'] || $testmask == "All")) {
                echo "Comparing <font color='blue'>[" . self::present($privilege) . "]</font> and <font color='green'>[" . self::present($mask) . "]</font>. ";
                $msg = "Comparing \n  Privilege: ".self::present($privilege).
                    "\n       Mask: ".self::present($mask);
                xarLog::message($msg, xarLog::LEVEL_DEBUG);
            }
            if (self::includes($privilege,$mask)) {
                if (self::implies($privilege,$mask)) {
                    if($test && ($testmask == $mask['name'] || $testmask == "All")) {
                        echo "<font color='blue'>[" . $privilege['name'] . "] wins</font>. Privilege includes mask. Privilege level greater or equal. Continuing with other checks.. <br />";
                        $msg = $privilege['name'] . " WINS! ".
                            "Privilege includes mask. ".
                            "Privilege level greater or equal.\n";
                        xarLog::message($msg, xarLog::LEVEL_DEBUG);
                    }
                    if (!$pass || $privilege['level'] > $pass['level']) $pass = $privilege;
                }
                else {
                    if($test && ($testmask == $mask['name'] || $testmask == "All")) {
                        echo "<font color='green'>[" . $mask['name'] . "] wins</font>. Privilege includes mask. Privilege level lesser. Continuing with other checks..<br />";
                        $msg = $mask['name'] . " MATCHES! ".
                                "Privilege includes mask. Privilege level ".
                                "lesser.\n";
                        xarLog::message($msg, xarLog::LEVEL_DEBUG);
                    }
                }
                $matched = true;
            } elseif (self::includes($mask,$privilege)) {
                if ($privilege['level'] >= $mask['level']) {
                    if($test && ($testmask == $mask['name'] || $testmask == "All")) {
                        echo "<font color='blue'>[" . $privilege['name'] . "] wins</font>. Mask includes privilege. Privilege level greater or equal. Continuing with other checks.. <br />";
                        $msg = $privilege['name'] ." WINS! ".
                            "Mask includes privilege. Privilege level ".
                            "greater or equal.\n";
                        xarLog::message($msg, xarLog::LEVEL_DEBUG);
                    }
                    if (!$pass || $privilege['level'] > $pass['level']) $pass = $privilege;
                    $matched = true;
                }
                else {
                    if($test && ($testmask == $mask['name'] || $testmask == "All")) {
                        echo "<font color='blue'>[" . $mask['name'] . "] wins</font>. Mask includes privilege. Privilege level lesser. Continuing with other checks..<br />";
                        $msg = $mask['name']." MATCHES! ".
                            "Mask includes privilege. Privilege level ".
                            "lesser.\n";
                        xarLog::message($msg, xarLog::LEVEL_DEBUG);
                    }
                }
            } else {
                if($test && ($testmask == $mask['name'] || $testmask == "All")) {
                    echo "<font color='red'>no match</font>. Continuing with other checks..<br />";
                    $msg = "NO MATCH.\n";
                    xarLog::message($msg, xarLog::LEVEL_DEBUG);
                }
            }
        }
        if (!$matched && ($privilegeset['children'] != array()))
            $pass = self::testprivileges($mask,$privilegeset['children'],$pass,$role);
        return $pass;
    }

    static function includes($privilege,$mask)
    {
        $p1 = $privilege;
        $p2 = $mask;

        // match realm. bail if no match.
        switch(self::$realmcomparison) {
            case "contains":
                $fails = $p1['realm']!=$p2['realm'];
            case "exact":
            default:
                $fails = $p1['realm']!=$p2['realm'];
                break;
        }
        if (($p1['realm'] != 'all') && ($fails)) return false;

        // match module and component. bail if no match.
        if (($p1['module'] === null) || (($p1['module'] != self::PRIVILEGES_ALL) && ($p1['module']!=$p2['module']))) {
            return false;
        }
        if (($p1['component'] != 'all') && ($p1['component']!=$p2['component'])) {
            return false;
        }

        // now match the instances
        $p1count = count($p1['instance']);
        $p2count = count($p2['instance']);
        if($p1count != $p2count) {
            if($p1count > $p2count) {
                $p = $p2;
                $p2 = self::normalize($p2,$p1count - $p2count);
            } else {
                $p = $p1;
                $p1 = self::normalize($p1,$p2count - $p1count);
            }
            if (count($p1['instance']) != count($p2['instance'])) {
                $msg = xarML('#(1) and #(2) do not have the same instances. #(3) | #(4) | #(5)',$mask['name'],$privilege['name'],implode(',',$p2['instance']),implode(',',$p1['instance']),self::present($privilege) . "|" . self::present($mask));
                throw new Exception($msg);
            }
        }
        for ( $i = 0, $p1count = count($p1['instance']); $i < $p1count; $i++) {
            if (($p1['instance'][$i] != 'all') && ($p1['instance'][$i]!=$p2['instance'][$i])) {
                return false;
            }
        }
        return true;
    }

    static function implies($privilege,$mask)
    {
        $match = self::includes($privilege,$mask);
        return $match && ($privilege['level'] >= $mask['level']) && ($mask['level'] > 0);
    }

    static function normalize($privilege,$adds=0)
    {
        for ($i=0;$i<$adds;$i++) {
            $privilege['instance'][] = 'all';
        }
        return $privilege;
    }

    static function present($privilege)
    {
        $display = $privilege['name'];
        $display .= "-" . strtolower($privilege['level']);
        $display .= ":" . strtolower($privilege['realm']);
        $display .= ":" . strtolower($privilege['module']);
        $display .= ":" . strtolower($privilege['component']);
        $display .= ":" . strtolower(implode(',',$privilege['instance']));
        return $display;
    }


}
?>
