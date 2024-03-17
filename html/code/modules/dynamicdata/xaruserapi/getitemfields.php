<?php
/**
 * Utility function to pass item field definitions
 *
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
 * utility function to pass item field definitions to whoever
 *
 * @param array<string, mixed> $args array of optional parameters<br/>
 * with
 *        integer  $args['objectid'] object id of the item fields to get, or<br/>
 *        integer  $args['name'] object name of the item fields to get, or<br/>
 *        string   $args['module'] module name of the item fields, or<br/>
 *        integer  $args['moduleid'] module id of the item fields to get +<br/>
 *        string   $args['itemtype'] item type of the item fields to get<br/>
 * @return array<mixed> containing the item field definitions
 */
function dynamicdata_userapi_getitemfields(array $args = [], $context = null)
{
    if (empty($args['objectid']) && empty($args['name'])) {
        $args = DataObjectDescriptor::getObjectID($args);
    }
    $object = DataObjectFactory::getObject($args, $context);
    if (!is_object($object)) {
        return [];
    }
    $fields = $object->getProperties();
    $itemfields = [];
    foreach ($fields as $name => $prop) {
        $itemfields[$name] = $prop->label;
    }
    return $itemfields;
}
