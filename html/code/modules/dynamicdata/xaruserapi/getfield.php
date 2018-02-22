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
 * get a specific item field
 * @TODO: update this with all the new stuff
 *
 * @author the DynamicData module development team
 * @param array    $args array of optional parameters<br/>
 *        string   $args['module'] module name of the item field to get, or<br/>
 *        integer  $args['module_id'] module id of the item field to get<br/>
 *        integer  $args['itemtype'] item type of the item field to get<br/>
 *        integer  $args['itemid'] item id of the item field to get<br/>
 *        string   $args['name'] name of the field to get<br/>
 * @return mixed value of the field, or false on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_userapi_getfield(Array $args=array())
{
    extract($args);

    if (empty($module_id) && !empty($module)) {
        $module_id = xarMod::getRegID($module);
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($module_id) || !is_numeric($module_id)) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (!isset($itemid) || !is_numeric($itemid)) {
        $invalid[] = 'item id';
    }
    if (!isset($name) || !is_string($name)) {
        $invalid[] = 'field name';
    }
    if (count($invalid) > 0) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array(join(', ',$invalid), 'user', 'get', 'DynamicData');
        throw new BadParameterException($vars,$msg);
    }

    $object = DataObjectMaster::getObject(array('moduleid'  => $module_id,
                                       'itemtype'  => $itemtype,
                                       'itemid'    => $itemid,
                                       'fieldlist' => array($name)));
    if (!isset($object)) return;
    $object->getItem();

    if (!isset($object->properties[$name])) return;
    $property = $object->properties[$name];

    // TODO: security check on object level

    if (!isset($property->value)) {
        $value = $property->defaultvalue;
    } else {
        $value = $property->value;
    }

    return $value;
}

?>
