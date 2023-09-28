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
 * return the properties and items
 *
 * @param array<string, mixed> $args array of optional parameters<br/>
 * @return array<mixed> containing a reference to the properties and a reference to the items
 * @TODO: move this to some common place in Xaraya (base module ?)
 */
function dynamicdata_userapi_getitemsforview(array $args = [])
{
    if (empty($args['fieldlist']) && empty($args['status'])) {
        // get the Active properties only (not those for Display Only)
        $args['status'] = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
    }
    $args['getobject'] = 1;
    $object =  xarMod::apiFunc('dynamicdata', 'user', 'getitems', $args);
    if (!isset($object)) {
        return [[], []];
    }
    $properties = & $object->getProperties();
    $items = & $object->items;
    return [& $properties, & $items];
}
