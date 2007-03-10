<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Perform standard module removal actions
 *
 * @author Marc Lutolf (mfl@netspan.ch)
 * @param none
 * @returns boolean result of action
 */

sys::import('modules.roles.class.xarQuery');

function modules_adminapi_standarddeinstall($args)
{
    extract($args);
    if (!isset($module)) return false;

    $dbconn =& xarDBGetConn();
    $xartables =& xarDBGetTables();

# --------------------------------------------------------
#
# Remove database tables
#
   // Load table maintenance API
    xarMod::apiLoad($module);
    try {
        $tablenameprefix = xarDBGetSiteTablePrefix() . '_' . $module;
        foreach ($xartables as $table) {
            if (is_array($table)) continue;
            if (strpos($table,$tablenameprefix) === 0) {
                $query = 'DROP TABLE ' . $table;
                $dbconn->Execute($query);
            }
        }
    } catch (Exception $e) {}

# --------------------------------------------------------
#
# Delete the base group created by this module
# Move the descendants to the Users group
#
    try {
        $role = xarFindRole(UCFirst($module) . 'Group');
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

# --------------------------------------------------------
#
# Delete all DD objects created by this module
#
    try {
        $dd_objects = unserialize(xarModGetVar($module,'dd_objects'));
        foreach ($dd_objects as $key => $value)
            $result = xarModAPIFunc('dynamicdata','admin','deleteobject',array('objectid' => $value));
    } catch (Exception $e) {}

# --------------------------------------------------------
#
# Remove the categories created by this module
#
    try {
        xarModAPIFunc('categories', 'admin', 'deletecat',
                             array('cid' => xarModGetVar($module, 'basecategory'))
                            );
    } catch (Exception $e) {}

# --------------------------------------------------------
#
# Remove hooks
#
    $modInfo = xarMod::getBaseInfo($module);
    $modId = $modInfo['systemid'];
    $query = "DELETE FROM " . $xartables['hooks'] .
             " WHERE s_module_id = " . $modId .
             " OR t_module_id = " . $modId;
    $dbconn->Execute($query);

# --------------------------------------------------------
#
# Remove custom tags, modvars, masks and privilege instances
#
    xarTemplateTag::unregisterall($module);
    xarRemoveMasks($module);
    xarRemoveInstances($module);
    xarModDelAllVars($module);

    // Deinstall successful
    return true;
}

?>
