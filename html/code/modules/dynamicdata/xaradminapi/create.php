<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * create a new item (the whole item or some dynamic data fields for it)
 *
 * @author the DynamicData module development team
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['module_id'] module id for the original item<br/>
 *        string   $args['itemtype'] item type of the original item<br/>
 *        integer  $args['itemid'] item id of the original item<br/>
 *        string   $args['values'] array of id => value, or<br/>
 *        string   $args['fields'] array containing the field definitions and values
 * @return integer item id on success, null on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_create(Array $args=array())
{

    $args = DataObjectDescriptor::getObjectID($args);
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

    if (!isset($fields) || !is_array($fields)) {
        $fields = array();
    }
    if (!isset($values) || !is_array($values)) {
        $values = array();
    }

    // TODO: test this
    $myobject = DataObjectMaster::getObject(array('objectid' => $objectid,
                                         'itemid'   => $itemid));
    if (empty($myobject)) return;
    if (!$myobject->checkAccess('create'))
        return;

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
