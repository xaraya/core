<?php
/**
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
 * create a new item (the whole item or some dynamic data fields for it)
 *
 * @author the DynamicData module development team
 * @param $args['modid'] module id for the original item
 * @param $args['itemtype'] item type of the original item
 * @param $args['itemid'] item id of the original item
 * @param $args['values'] array of id => value, or
 * @param $args['fields'] array containing the field definitions and values
 * @returns mixed
 * @return item id on success, null on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_create($args)
{
    extract($args);

    $invalid = array();
    if (!isset($itemid) || !is_numeric($itemid)) {
        $invalid[] = 'item id';
    }
    if ((isset($fields) && is_array($fields)) ||
        (isset($values) && is_array($values)) ) {
    } else {
        $invalid[] = xarML('fields or values');
    }
    if (count($invalid) > 0) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array(join(', ',$invalid), 'admin', 'create', 'DynamicData');
        throw new BadParameterException($vars,$msg);
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if(!xarSecurityCheck('AddDynamicDataItem',1,'Item',"$modid:$itemtype:$itemid")) return;

    if (!isset($fields) || !is_array($fields)) {
        $fields = array();
    }
    if (!isset($values) || !is_array($values)) {
        $values = array();
    }

    // TODO: test this
    $myobject = & DataObjectMaster::getObject(array('moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'itemid'   => $itemid));
    if (empty($myobject)) return;

    if (count($values) == 0) {
        foreach ($fields as $field) {
            if (isset($field['value'])) {
                $values[$field['name']] = $field['value'];
            }
        }
    }
    $itemid = $myobject->createItem($values);
    return $itemid;
}
?>
