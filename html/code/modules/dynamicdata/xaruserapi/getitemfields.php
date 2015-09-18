<?php
/**
 * Utility function to pass item field definitions
 *
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/182.html
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
function dynamicdata_userapi_getitemfields(Array $args=array())
{
    $object = DataObjectMaster::getObject($args);
    if (!is_object($object)) return array();
    $fields = $object->getProperties();
    $itemfields = array();
    foreach ($fields as $name => $prop) {
        $itemfields[$name] = $prop->label;
    }
    return $itemfields;
}

?>
