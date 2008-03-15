<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com *
 * @subpackage modules
 */

/**
 * Perform standard module removal actions
 *
 * @author Marc Lutolf (mfl@netspan.ch)
 * @return boolean result of action
**/
function modules_adminapi_standarddeinstall($args)
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
    // FIXME: this assumes modules name their tables prefixed by their own name!!
    xarMod::apiLoad($module);
    try {
        $tablenameprefix = xarDB::getPrefix() . '_' . $module;
        foreach ($xartables as $table) {
            if (is_array($table)) continue;
            if (strpos($table,$tablenameprefix) === 0) {
                $query = 'DROP TABLE ' . $table;
                $dbconn->Execute($query);
            }
        }
    } catch (Exception $e) {}

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
        xarModAPIFunc('categories', 'admin', 'deletecat',
                             array('cid' => xarModVars::get($module, 'basecategory'))
                            );
    } catch (Exception $e) {}

    // Remove hooks
    $modInfo = xarMod::getBaseInfo($module);
    $modId = $modInfo['systemid'];
    $query = "DELETE FROM " . $xartables['hooks'] .
             " WHERE s_module_id = " . $modId .
             " OR t_module_id = " . $modId;
    $dbconn->Execute($query);

    // Remove custom tags, modvars, masks and privilege instances
    xarTemplateTag::unregisterall($module);
    xarRemoveMasks($module);
    xarRemoveInstances($module);
    xarModVars::delete_all($module);

    // Deinstall successful
    return true;
}
?>
