<?php
/**
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 */

/**
 * Perform standard module removal actions
 *
 * @author Marc Lutolf (mfl@netspan.ch)
 * @param array    $args array of optional parameters<br/>
 * @return boolean|void result of action
**/
function modules_adminapi_standarddeinstall(Array $args=array())
{
    extract($args);
    if (!isset($module)) return false;

# --------------------------------------------------------
#
# Delete all DD objects created by this module
#
    try {
        $dd_objects = unserialize(xarModVars::get($module,'dd_objects'));
        foreach ($dd_objects as $key => $value)
            $result = DataObjectMaster::deleteObject(array('objectid' => $value));
    } catch (Exception $e) {}

# --------------------------------------------------------
#
# Remove database tables
#
    $dbconn = xarDB::getConn();
    $xartables =& xarDB::getTables();

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

# --------------------------------------------------------
#
# Remove all blocks created by this module
#
    try {
        $blocks = unserialize(xarModVars::get($module,'blocks'));
        
        foreach ($blocks as $blockid) {
            xarMod::apiFunc('blocks', 'instances', 'deleteitem', array('block_id' => $blockid));
        }
    } catch (Exception $e) {}

# --------------------------------------------------------
#
# Delete the base group created by this module if it exists
#
     // Move the descendants to the Users group
    try {
        $role = xarRoles::findRole(ucfirst($module) . 'Group');
        if (!empty($role)) {
            $usersgroup = xarRoles::findRole('Users');
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

# --------------------------------------------------------
#
# Remove hooks
    /*
    Since this is hooks, the ModuleRemove subject deals with it
    */

# --------------------------------------------------------
#
# Remove modvars, masks and privilege instances
#
    xarMasks::removemasks($module);
    xarPrivileges::removeInstances($module);
    xarModVars::delete_all($module);

    // Deinstall successful
    return true;
}
