<?php
/**
 * Delete all dynamicdata fields for a module
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * delete all dynamicdata fields for a module - hook for ('module','remove','API')
 *
 * @param $args['objectid'] ID of the object (must be the module name here !!)
 * @param $args['extrainfo'] extra information
 * @returns bool
 * @return true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_removehook($args)
{
    extract($args);

    if (!isset($extrainfo)) {
        $extrainfo = array();
    }

    // When called via hooks, we should get the real module name from objectid
    // here, because the current module is probably going to be 'modules' !!!
    if (!isset($objectid) || !is_string($objectid)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('object ID (= module name)', 'admin', 'removehook', 'dynamicdata');
        throw new BadParameterException($vars,$msg);
        // we *must* return $extrainfo for now, or the next hook will fail
        // CHECKME: not anymore now, exceptions are either fatal or caught, in this case, we probably want to catch it in the callee.
        //return $extrainfo;
    }

    $modid = xarModGetIDFromName($objectid);
    if (empty($modid)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('module ID', 'admin', 'removehook', 'dynamicdata');
        throw new BadParameterException($vars,$msg);
        // we *must* return $extrainfo for now, or the next hook will fail
        // CHECKME: not anymore now, exceptions are either fatal or caught, in this case, we probably want to catch it in the callee.
        //return $extrainfo;
    }

    if(!xarSecurityCheck('DeleteDynamicDataItem',0,'Item',"$modid:All:All")) {
        // we *must* return $extrainfo for now, or the next hook will fail
        // CHECKME: not anymore now, exceptions are either fatal or caught, in this case, we probably want to catch it in the callee.
        //return $extrainfo;
    }

    // Get database setup
    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();

    $dynamicprop = $xartable['dynamic_properties'];

    $sql = "SELECT xar_prop_id FROM $dynamicprop WHERE xar_prop_moduleid = ?";
    $stmt = $dbconn->prepareStatement($sql);
    $result = $stmt->executeQuery(array($modid));

    // TODO: do we want to catch the exception here? or in the callee?
    //return $extrainfo;
    $ids = array();
    while ($result->next()) {
        list($id) = $result->fields;
        $ids[] = $id;
    }
    $result->close();

    if (count($ids) == 0) {
        return $extrainfo;
    }

    $dynamicdata = $xartable['dynamic_data'];

    // TODO: don't delete if the data source is not in dynamic_data
    try {
        $dbconn->begin();

        // Delete the item fields
        $bindmarkers = '?' . str_repeat(',?',count($ids)-1);
        $sql = "DELETE FROM $dynamicdata WHERE xar_dd_propid IN ($bindmarkers)";
        $stmt = $dbconn->prepareStatement($sql);
        $stmt->executeUpdate($ids);

        // Delete the properties
        $sql = "DELETE FROM $dynamicprop WHERE xar_prop_id IN ($bindmarkers)";
        $stmt = $dbconn->prepareStatement($sql);
        $stmt->executeUpdate($ids);
        $dbconn->commit();
    } catch(SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }

    // Return the extra info
    return $extrainfo;
}

?>
