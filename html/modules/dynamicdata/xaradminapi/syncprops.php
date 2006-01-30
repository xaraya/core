<?php
/**
 * Resynchronise properties with object
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * resynchronise properties with object (for module & itemtype)
 *
 * @author the DynamicData module development team
 * @param $args['objectid'] object id for the properties you want to update
 * @param $args['moduleid'] new module id for the properties
 * @param $args['itemtype'] new item type for the properties
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_syncprops($args)
{
    extract($args);
    // Required arguments
    $invalid = array();
    if (!isset($objectid) || !is_numeric($objectid)) {
        $invalid[] = 'object id';
    }
    if (!isset($moduleid) || !is_numeric($moduleid)) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (count($invalid) > 0) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array(join(', ',$invalid), 'admin', 'syncprops', 'DynamicData');
        throw new BadParameterException($vars,$msg);
    }

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $dynamicprop = $xartable['dynamic_properties'];

    $sql = "UPDATE $dynamicprop
            SET xar_prop_moduleid = ?, xar_prop_itemtype = ?
            WHERE xar_prop_objectid = ?";
    $bindvars = array($moduleid, $itemtype, $objectid);
    $dbconn->Execute($sql,$bindvars);

    return true;
}

?>
