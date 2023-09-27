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
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['itemtype'] item type<br/>
 *        integer  $args['module_id'] ID of the module
 * @return array containing the item field definitions
 */
function dynamicdata_userapi_getitemfields(array $args = [])
{
    if (empty($args['objectid']) && empty($args['name'])) {
        $args = DataObjectDescriptor::getObjectID($args);
    }
    $object = DataObjectMaster::getObject($args);
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
