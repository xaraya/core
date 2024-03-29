<?php
/**
 * Retrieve a list of itemtypes of this module
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
 * Utility function to retrieve the list of itemtypes of this module (if any).
 * @param array<string, mixed> $args array of optional parameters<br/>
 * @return array<mixed> the itemtypes of this module and their description *
 */
function dynamicdata_userapi_getitemtypes(array $args = [], $context = null)
{
    $itemtypes = [];

    // Get objects
    $objects = DataObjectFactory::getObjects();

    $module_id = xarMod::getRegID('dynamicdata');
    foreach ($objects as $id => $object) {
        // skip any object that doesn't belong to dynamicdata itself
        if ($module_id != $object['moduleid']) {
            continue;
        }
        // skip the "internal" DD objects
        if ($object['objectid'] < 3) {
            continue;
        }
        $itemtypes[$object['itemtype']] = [
            'label' => xarVar::prepForDisplay($object['label']),
            'title' => xarVar::prepForDisplay(xarML('View #(1)', $object['label'])),
            'url'   => xarController::URL('dynamicdata', 'user', 'view', ['itemtype' => $object['itemtype']]),
        ];
    }
    return $itemtypes;
}
