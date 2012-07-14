<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 */
/**
 * Utility function to retrieve the list of item types of this module (if any)
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) copyright-placeholder
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Categories module
 */
/**
 * utility function to retrieve the list of item types of this module (if any)
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @returns array
 * @return array containing the item types and their description
 */
function categories_userapi_getitemtypes($args)
{
    $itemtypes = array();

/* itemtype 0 not used, means "All". Itemtype 1 not used, would be a category object without properties*/
    $itemtypes[1] = array('label' => xarML('Bare Category'),
                          'title' => xarML('View Bare Category'),
                          'url'   => xarModURL('categories','admin','view')
                         );
    $itemtypes[2] = array('label' => xarML('Category'),
                          'title' => xarML('View Category'),
                          'url'   => xarModURL('categories','admin','view')
                         );

    if ($extensionitemtypes = xarMod::apiFunc('dynamicdata','user','getmoduleitemtypes',array('moduleid' => 147, 'native' =>false),0)) {
        $types = array();
        foreach ($itemtypes as $key => $value) $types[$key] = $value;
        foreach ($extensionitemtypes as $key => $value) $types[$key] = $value;

        /* TODO: activate this code when we move to php5
        $keys = array_merge(array_keys($itemtypes),array_keys($extensionitemtypes));
        $values = array_merge(array_values($itemtypes),array_values($extensionitemtypes));
        return array_combine($keys,$values);
        */

    } else {
        $types = $itemtypes;
    }

    return $types;
}

?>
