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
 * get a specific item field
 * @TODO: update this with all the new stuff
 *
 * @author the DynamicData module development team
 * @param string $args['module'] module name of the item field to get, or
 * @param int $args['modid'] module id of the item field to get
 * @param int $args['itemtype'] item type of the item field to get
 * @param int $args['itemid'] item id of the item field to get
 * @param string $args['name'] name of the field to get
 * @return mixed value of the field, or false on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_userapi_getfield($args)
{
    extract($args);

    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid)) {
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

    $object = & DataObjectMaster::getObject(array('moduleid'  => $modid,
                                       'itemtype'  => $itemtype,
                                       'itemid'    => $itemid,
                                       'fieldlist' => array($name)));
    if (!isset($object)) return;
    $object->getItem();

    if (!isset($object->properties[$name])) return;
    $property = $object->properties[$name];

    if(!xarSecurityCheck('ReadDynamicDataField',1,'Field',$property->name.':'.$property->type.':'.$property->id)) return;
    if (!isset($property->value)) {
        $value = $property->defaultvalue;
    } else {
        $value = $property->value;
    }

    return $value;
}

?>
