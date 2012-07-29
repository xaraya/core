<?php
/**
 * Retrieve a list of itemtypes of this module
 *
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Utility function to retrieve the list of itemtypes of this module (if any).
 * @param array    $args array of optional parameters<br/>
 * @return array the itemtypes of this module and their description *
 */
function dynamicdata_userapi_getitemtypes(Array $args=array())
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
