<?php
/**
 * Return the properties for an item
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
 * return the properties for an item
 *
 * @param array<string, mixed> $args array of optional parameters<br/>
 * @return array<mixed> containing a reference to the properties of the item
 * @TODO: move this to some common place in Xaraya (base module ?)
 */
function dynamicdata_userapi_getitemfordisplay(array $args = [], $context = null)
{
    $args['getobject'] = 1;
    $object = xarMod::apiFunc('dynamicdata', 'user', 'getitem', $args, $context);
    $properties = [];
    if (isset($object)) {
        $properties = & $object->getProperties();
    }
    $item = [& $properties];
    return $item;
}
