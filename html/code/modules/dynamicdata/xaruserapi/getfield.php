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
 * @param array<string, mixed> $args array of optional parameters<br/>
 *        string   $args['module'] module name of the item field to get, or<br/>
 *        integer  $args['module_id'] module id of the item field to get<br/>
 *        integer  $args['itemtype'] item type of the item field to get<br/>
 *        integer  $args['itemid'] item id of the item field to get<br/>
 *        string   $args['name'] name of the field to get<br/>
 * @return mixed value of the field, or false on failure
 * @throws BadParameterException
 */
function dynamicdata_userapi_getfield(array $args = [], $context = null)
{
    extract($args);

    if (empty($module_id) && !empty($module)) {
        $module_id = xarMod::getRegID($module);
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = [];
    /** @var ?int $module_id */
    if (!isset($module_id) || !is_numeric($module_id)) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    /** @var ?int $itemid */
    if (!isset($itemid) || !is_numeric($itemid)) {
        $invalid[] = 'item id';
    }
    /** @var ?string $name */
    if (!isset($name) || !is_string($name)) {
        $invalid[] = 'field name';
    }
    if (count($invalid) > 0) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = [join(', ', $invalid), 'user', 'get', 'DynamicData'];
        throw new BadParameterException($vars, $msg);
    }

    $args = DataObjectDescriptor::getObjectID(['moduleid'  => $module_id,
                                               'itemtype'  => $itemtype]);
    if (empty($args['objectid'])) {
        return;
    }
    // set context if available in function
    $object = DataObjectFactory::getObject(
        ['objectid'  => $args['objectid'],
        'itemid'    => $itemid,
        'fieldlist' => [$name]],
        $context
    );
    if (!isset($object) || empty($object->objectid)) {
        return;
    }

    $object->getItem();

    if (!isset($object->properties[$name])) {
        return;
    }
    $property = $object->properties[$name];

    // TODO: security check on object level

    if (!isset($property->value)) {
        $value = $property->defaultvalue;
    } else {
        $value = $property->value;
    }

    return $value;
}
