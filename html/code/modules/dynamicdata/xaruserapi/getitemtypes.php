<?php
/**
 * Retrieve list of itemtypes of this module
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * utility function to retrieve the list of item types of this module (if any)
 *
 * @return array containing the item types and their description
 */
function dynamicdata_userapi_getitemtypes($args)
{
    $itemtypes = array();

    // Get objects
    $objects = DataObjectMaster::getObjects();

    $module_id = xarMod::getRegID('dynamicdata');
    foreach ($objects as $id => $object) {
        // skip any object that doesn't belong to dynamicdata itself
        if ($module_id != $object['moduleid']) continue;
        // skip the "internal" DD objects
        if ($object['objectid'] < 3) continue;
        $itemtypes[$object['itemtype']] = array('label' => xarVarPrepForDisplay($object['label']),
                                                'title' => xarVarPrepForDisplay(xarML('View #(1)',$object['label'])),
                                                'url'   => xarModURL('dynamicdata','user','view',array('itemtype' => $object['itemtype']))
                                               );
    }
    return $itemtypes;
}

?>