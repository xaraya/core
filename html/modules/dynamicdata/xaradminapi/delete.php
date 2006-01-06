<?php
/**
 * Delete an item 
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * delete an item (the whole item or the dynamic data fields of it)
 *
 * @author the DynamicData module development team
 * @param $args['itemid'] item id of the original item
 * @param $args['modid'] module id for the original item
 * @param $args['itemtype'] item type of the original item
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_delete($args)
{
    extract($args);

    $invalid = array();
    if (!isset($itemid) || !is_numeric($itemid)) {
        $invalid[] = 'item id';
    }
    if (!isset($modid) || !is_numeric($modid)) {
        $invalid[] = 'module id';
    }
    if (count($invalid) > 0) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array(join(', ',$invalid), 'admin', 'delete', 'DynamicData');
        throw new BadParameterException($vars,$msg);
    }

    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $itemtype = 0;
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if(!xarSecurityCheck('DeleteDynamicDataItem',1,'Item',"$modid:$itemtype:$itemid")) return;

// TODO: test this
    $myobject = & Dynamic_Object_Master::getObject(array('moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'itemid'   => $itemid));
    if (empty($myobject)) return;

    $myobject->getItem();
    $itemid = $myobject->deleteItem();
    return $itemid;
}
?>
