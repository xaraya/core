<?php

/**
 * utility function to retrieve the list of item types of this module (if any)
 *
 * @returns array
 * @return array containing the item types and their description
 */
function dynamicdata_userapi_getitemtypes($args)
{
    $itemtypes = array();

    // Get objects
    $objects = xarModAPIFunc('dynamicdata','user','getobjects');

    $modid = xarModGetIDFromName('dynamicdata');
    foreach ($objects as $id => $object) {
        // skip any object that doesn't belong to dynamicdata itself
        if ($modid != $object['moduleid']) continue;
        // skip the "internal" DD objects
        if ($object['objectid'] < 3) continue;
        $itemtypes[$id] = array('label' => xarVarPrepForDisplay($object['label']),
                                'title' => xarVarPrepForDisplay(xarML('View #(1)',$object['label'])),
                                'url'   => xarModURL('dynamicdata','user','view',array('itemtype' => $object['itemtype']))
                               );
    }
    return $itemtypes;
}

?>
