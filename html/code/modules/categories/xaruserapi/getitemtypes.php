<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @subpackage categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/147.html
 *
 */

/**
 * Utility function to retrieve the list of item types of this module (if any)
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array<string, mixed> $args Parameter data array
 * @return array<mixed> Returns array containing the item types and their description
 */
function categories_userapi_getitemtypes($args)
{
    $itemtypes = array();

/* itemtype 0 not used, means "All". Itemtype 1 not used, would be a category object without properties*/
    $itemtypes[1] = array('label' => xarML('Bare Category'),
                          'title' => xarML('View Bare Category'),
                          'url'   => xarController::URL('categories','admin','view')
                         );
    $itemtypes[2] = array('label' => xarML('Category'),
                          'title' => xarML('View Category'),
                          'url'   => xarController::URL('categories','admin','view')
                         );

    try {
        $extensionitemtypes = xarMod::apiFunc('dynamicdata', 'user', 'getmoduleitemtypes', array('moduleid' => 147, 'native' => false));
    } catch (Exception $e) {
        $extensionitemtypes = [];
    }
    if ($extensionitemtypes) {
        $types = array();
        foreach ($itemtypes as $key => $value) $types[$key] = $value;
        foreach ($extensionitemtypes as $key => $value) $types[$key] = $value;

        /* TODO: activate this code when we move to php5 - that would be about now, no? ;-)
        $keys = array_merge(array_keys($itemtypes),array_keys($extensionitemtypes));
        $values = array_merge(array_values($itemtypes),array_values($extensionitemtypes));
        return array_combine($keys,$values);
        */

    } else {
        $types = $itemtypes;
    }

    return $types;
}
