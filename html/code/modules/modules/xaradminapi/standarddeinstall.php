<?php
/**
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/1.html
 */

/**
 * Perform standard module removal actions
 *
 * @author Marc Lutolf (mfl@netspan.ch)
 * @param array    $args array of optional parameters<br/>
 * @return boolean result of action
**/
function modules_adminapi_standarddeinstall(Array $args=array())
{
    extract($args);
    if (!isset($module)) return false;

    // Delete all DD objects created by this module
    try {
        $dd_objects = unserialize(xarModVars::get($module,'dd_objects'));
        foreach ($dd_objects as $key => $value)
            $result = DataObjectMaster::deleteObject(array('objectid' => $value));
    } catch (Exception $e) {}

    $dbconn = xarDB::getConn();
    $xartables =& xarDB::getTables();

    // Remove database tables
    xarMod::apiLoad($module);
    $tablenameprefix = xarDB::getPrefix() . '_' . $module;
    foreach ($xartables as $table) {
        if (is_array($table)) continue;
        if (strpos($table,$tablenameprefix) === 0) {
            $query = 'DROP TABLE ' . $table;
            try {
                $dbconn->Execute($query);
            } catch (Exception $e) {}
        }
    }

     // Delete the base group created by this module if it exists
     // Move the descendants to the Users group
    try {
        $role = xarFindRole(ucfirst($module) . 'Group');
        if (!empty($role)) {
            $usersgroup = xarFindRole('Users');
            $descendants = $role->getDescendants();
            foreach ($descendants as $item) {
                $parents = $item->getParents();
                if (count($parents) > 1) $usersgroup->addMember($item);
                if (!$role->removeMember($item)) return;
            }
            if (!$role->purge()) return;
        }
    } catch (Exception $e) {}

    // Remove the categories created by this module
    try {
        xarMod::apiFunc('categories', 'admin', 'deletecat',
                             array('cid' => xarModVars::get($module, 'basecategory'))
                            );
    } catch (Exception $e) {}

    // Remove hooks
    #
    /* Since this is hooks, the ModuleRemove subject should deal with it
    xarHooks::notify('ModuleRemove', array('module' => $module, 'id' => $module));
    $modInfo = xarMod::getBaseInfo($module);
    $modId = $modInfo['systemid'];
    $query = "DELETE FROM " . $xartables['hooks'] .
             " WHERE s_module_id = " . $modId .
             " OR t_module_id = " . $modId;
    $dbconn->Execute($query);
    */
    
    // Remove modvars, masks and privilege instances
    xarRemoveMasks($module);
    xarRemoveInstances($module);
    xarModVars::delete_all($module);

    // Deinstall successful
    return true;
}
?>
