<?php
/**
 * Delete an item
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * delete an item (the whole item or the dynamic data fields of it)
 *
 * @author the DynamicData module development team
 * @param array<string, mixed> $args array of optional parameters<br/>
 *        integer  $args['itemid'] item id of the original item<br/>
 *        integer  $args['module_id'] module id for the original item<br/>
 *        string   $args['itemtype'] item type of the original item
 * @return boolean|void true on success, false on failure
 * @throws BadParameterException
 */
function dynamicdata_adminapi_delete(array $args = [])
{
    $args = DataObjectDescriptor::getObjectID($args);
    extract($args);
    /** @var int $objectid */

    $invalid = [];
    /** @var ?int $itemid */
    if (!isset($itemid) || !is_numeric($itemid)) {
        $invalid[] = 'item id';
    }
    /** @var ?int $module_id */
    if (!isset($module_id) || !is_numeric($module_id)) {
        $invalid[] = 'module id';
    }
    if (count($invalid) > 0) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = [join(', ', $invalid), 'admin', 'delete', 'DynamicData'];
        throw new BadParameterException($vars, $msg);
    }

    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $itemtype = 0;
    }

    $myobject = DataObjectFactory::getObject(['objectid' => $objectid,
                                         'itemid'   => $itemid]);
    if (empty($myobject)) {
        return;
    }
    if (!$myobject->checkAccess('delete')) {
        return;
    }

    $myobject->getItem();
    $itemid = $myobject->deleteItem();

    unset($myobject);
    return $itemid;
}
