<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * update an item (the whole item or the dynamic data fields of it)
 *
 * @author the DynamicData module development team
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['itemid'] item id of the original item<br/>
 *        integer  $args['module_id'] module id for the original item<br/>
 *        string   $args['itemtype'] item type of the original item<br/>
 *        array    $args['values'] array of id => value, or<br/>
 *        array    $args['fields'] array containing the field definitions and values
 * @return mixed item id on success, null on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_update(Array $args=array())
{
    extract($args);

    $invalid = array();
    if (!isset($itemid) || !is_numeric($itemid) || empty($itemid)) { // we can't accept item id 0 here
        $invalid[] = 'item id';
    }
    if (!isset($module_id) || !is_numeric($module_id)) {
        $invalid[] = 'module id';
    }
    if ((isset($fields) && is_array($fields)) ||
        (isset($values) && is_array($values)) ) {
    } else {
        $invalid[] = xarML('fields or values');
    }
    if (count($invalid) > 0) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array(join(', ',$invalid), 'admin', 'update', 'DynamicData');
        throw new BadParameterException($vars,$msg);
    }

    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $itemtype = 0;
    }

    if (!isset($fields) || !is_array($fields)) {
        $fields = array();
    }
    if (!isset($values) || !is_array($values)) {
        $values = array();
    }

// TODO: test this
    $myobject = & DataObjectMaster::getObject(array('moduleid' => $module_id,
                                         'itemtype' => $itemtype,
                                         'itemid'   => $itemid));
    if (empty($myobject)) return;
    if (!$myobject->checkAccess('update'))
        return;

    $myobject->getItem();

    if (count($values) == 0) {
        foreach ($fields as $field) {
            if (isset($field['value'])) {
                $values[$field['name']] = $field['value'];
            }
        }
    }
    $itemid = $myobject->updateItem($values);
    return $itemid;
}
?>