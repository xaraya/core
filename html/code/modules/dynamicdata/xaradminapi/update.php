<?php
/**
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
 * update an item (the whole item or the dynamic data fields of it)
 *
 * @author the DynamicData module development team
 * @param array<string, mixed> $args array of optional parameters<br/>
 * with
 *        integer  $args['itemid'] item id of the original item<br/>
 *        integer  $args['objectid'] object id of the original item, or<br/>
 *        string   $args['name'] object name of the original item, or<br/>
 *        integer  $args['moduleid'] module id for the original item +<br/>
 *        string   $args['itemtype'] item type of the original item<br/>
 *        array    $args['values'] array of id => value, or<br/>
 *        array    $args['fields'] array containing the field definitions and values
 * @return mixed item id on success, null on failure
 * @throws BadParameterException
 */
function dynamicdata_adminapi_update(array $args = [], $context = null)
{
    $args = DataObjectDescriptor::getObjectID($args);
    extract($args);
    /** @var int $objectid */

    $invalid = [];
    /** @var ?int $itemid */
    if (!isset($itemid) || !is_numeric($itemid) || empty($itemid)) { // we can't accept item id 0 here
        $invalid[] = 'item id';
    }
    /** @var ?int $module_id */
    if (!isset($module_id) || !is_numeric($module_id)) {
        $invalid[] = 'module id';
    }
    if ((isset($fields) && is_array($fields)) ||
        (isset($values) && is_array($values))) {
    } else {
        $invalid[] = xarML('fields or values');
    }
    if (count($invalid) > 0) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = [join(', ', $invalid), 'admin', 'update', 'DynamicData'];
        throw new BadParameterException($vars, $msg);
    }

    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $itemtype = 0;
    }

    if (!isset($fields) || !is_array($fields)) {
        $fields = [];
    }
    if (!isset($values) || !is_array($values)) {
        $values = [];
    }

    // TODO: test this
    // set context if available in function
    $myobject = DataObjectFactory::getObject(
        ['objectid' => $objectid,
        'itemid'   => $itemid],
        $context
    );
    if (empty($myobject)) {
        return;
    }
    if (!$myobject->checkAccess('update')) {
        return;
    }

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
