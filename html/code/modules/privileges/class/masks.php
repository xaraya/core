<?php
/**
 * Privileges administration API
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage privileges module
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

sys::import('modules.privileges.class.security');
class xarMasks extends xarSecurity
{
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
    public static function getmasks($modid=self::PRIVILEGES_ALL,$component='All')
    {
        self::initialize();
        // TODO: try to do all this a bit more compact and without xarMod_GetBaseInfo
        // TODO: sort on the name of the mod again
        // TODO: evaluate ambiguous signature of this method: does 'All' mean get *only* the masks which apply to all modules
        //       or get *all* masks.
        $bindvars = array();
        // base query, only the where clauses differ
        $query = "SELECT masks.id, masks.name, realms.name,
                  modules.name, masks.component, masks.instance,
                  masks.level, masks.description
                  FROM " . self::$privilegestable . " AS masks
                  LEFT JOIN " . self::$realmstable. " AS realms ON masks.realm_id = realms.id
                  LEFT JOIN " . self::$modulestable. " AS modules ON masks.module_id = modules.id ";
        if ($modid == self::PRIVILEGES_ALL) {
            if ($component == '' || $component == 'All') {
                // nothing differs
            } else {
                $query .= "WHERE (component IN (?,?,?) ";
                $bindvars = array($component,'All','None');
            }
        } else {
            if ($component == '' || $component == 'All') {
                $query .= "WHERE module_id = ? ";
                $bindvars = array($modid);
            } else {
                $query .= "WHERE  module_id = ? AND
                                 component IN (?,?,?) ";
                $bindvars = array($module_id,$component,'All','None');
            }
        }
        $query .= " AND itemtype = ? ";
        $bindvars[] = self::PRIVILEGES_MASKTYPE;
        $query .= "ORDER BY masks.module_id, masks.component, masks.name";

        $stmt = self::$dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);

        $masks = array();
        while($result->next()) {
            list($id, $name, $realm, $module_id, $component, $instance, $level,
                    $description) = $result->fields;
            $pargs = array('id' => $id,
                           'name' => $name,
                           'realm' => is_null($realm) ? 'All' : $realm,
                           'module' => $module_id,
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
    public static function register($name,$realm,$module,$component,$instance,$level,$description='')
    {
        self::initialize();
        // Check if the mask has already been registered, and update it if necessary.
        // FIXME: make mask names unique across modules (+ across realms) ?
        // FIXME: is module/name enough? Perhaps revisit this with realms in mind.
        if($module == 'All') {
            $module_id = self::PRIVILEGES_ALL;
        } elseif($module == null) {
            $module_id = null;
        } else {
            $module_id = xarMod::getID($module);
        }

        $realmid = null;
        if($realm != 'All') {
            $stmt = self::$dbconn->prepareStatement('SELECT id FROM '.self::$realmstable .' WHERE name=?');
            $result = $stmt->executeQuery(array($realm),ResultSet::FETCHMODE_ASSOC);
            if($result->next()) $realmid = $result->getInt('id');
        }

        $query = "SELECT id FROM " . self::$privilegestable  . " WHERE itemtype = ? AND module_id = ? AND name = ?";
        $stmt = self::$dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array(self::PRIVILEGES_MASKTYPE, $module_id, $name));

        try {
            self::$dbconn->begin();
            if ($result->first()) {
                list($id) = $result->fields;
                $query = "UPDATE " . self::$privilegestable .
                          " SET realm_id = ?, component = ?,
                              instance = ?, level = ?,
                              description = ?, itemtype= ?
                          WHERE id = ?";
                $bindvars = array($realmid, $component, $instance, $level,
                                  $description, self::PRIVILEGES_MASKTYPE, $id);
            } else {
                $query = "INSERT INTO " . self::$privilegestable .
                          " (name, realm_id, module_id, component, instance, level, description, itemtype)
                          VALUES (?,?,?,?,?,?,?,?)";
                $bindvars = array(
                                  $name, $realmid, $module_id, $component, $instance, $level,
                                  $description, self::PRIVILEGES_MASKTYPE);
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
    public static function unregister($name)
    {
        self::initialize();
        $query = "DELETE FROM " . self::$privilegestable . " WHERE itemtype = ? AND name = ?";
        self::$dbconn->Execute($query,array(self::PRIVILEGES_MASKTYPE, $name));
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
    public static function removemasks($module_id)
    {
        self::initialize();
        $query = "DELETE FROM " . self::$privilegestable . " WHERE itemtype = ? AND module_id = ?";
        //Execute the query, bail if an exception was thrown
        self::$dbconn->Execute($query,array(self::PRIVILEGES_MASKTYPE, $module_id));
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
    public static function xarSecLevel($levelname)
    {
        // If we could somehow turn a string into the name of a class constant, that would be great.
        sys::import('modules.privileges.class.securitylevel');
        return SecurityLevel::get($levelname);
    }

    /**
            if ($module == '') list($module) = xarController::$request->getInfo();
                xarController::redirect(xarModURL(xarModVars::get('roles','defaultauthmodule'),'user','showloginform',array('redirecturl'=> $requrl),false));
                xarController::redirect(xarModURL('privileges','user','errors',array('layout' => 'no_privileges')));
     * forgetprivsets: remove all irreducible set of privileges from the db
     *
     * used to lighten the cache
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   string
     * @return  boolean
    */
    public static function forgetprivsets()
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
    public static function getprivset($role)
    {
        static $selStmt = null;
        static $insStmt = null;

        if (xarVarIsCached('Security.getprivset', $role)) {
            return xarVarGetCached('Security.getprivset', $role);
        }
        $query = "SELECT set FROM " . self::$privsetstable . " WHERE id =?";
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
     * normalizeprivset: apply the normalize() method on all privileges in a privilege set
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   array representing the privilege set
     * @return  none
    */
    public static function normalizeprivset(&$privset)
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
     * Clear the cached privileges from all sessions
     * @access public
     */
    public static function clearCache()
    {
        if (class_exists('xarModVars')) {
            xarModVars::set('privileges', 'clearcache', time());
        }
    }
}
?>
