<?php
/**
 * Delete an item
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * delete an item (the whole item or the dynamic data fields of it)
 *
 * @author the DynamicData module development team
 * @param $args['itemid'] item id of the original item
 * @param $args['module_id'] module id for the original item
 * @param $args['itemtype'] item type of the original item
 * @return bool true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_delete($args)
{
    extract($args);

    $invalid = array();
    if (!isset($itemid) || !is_numeric($itemid)) {
        $invalid[] = 'item id';
    }
    if (!isset($module_id) || !is_numeric($module_id)) {
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

    $args = DataObjectDescriptor::getObjectID(array('moduleid'  => $module_id,
                                       'itemtype'  => $itemtype));
    $myobject = DataObjectMaster::getObject(array('objectid' => $args['objectid'],
                                         'itemid'   => $itemid));
    if (empty($myobject)) return;
    if (!$myobject->checkAccess('delete'))
        return;

    $myobject->getItem();
    $itemid = $myobject->deleteItem();

    unset($myobject);
    return $itemid;
}
?>