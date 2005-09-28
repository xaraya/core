<?php

/**
 * utility function to retrieve the list of item types of this module (if any)
 *
 * @returns array
 * @return array containing the item types and their description
 */
function blocks_userapi_getitemtypes($args)
{
    $itemtypes = array();

    if (xarSecurityCheck('EditBlock',0)) {
        $showurl = true;
    } else {
        $showurl = false;
    }

    $name = xarML('Block Types');
    $itemtypes[1] = array('label' => xarVarPrepForDisplay($name),
                          'title' => xarVarPrepForDisplay(xarML('Display #(1)',$name)),
                          'url'   => $showurl ? xarModURL('blocks','admin','view_types') : ''
                         );

    $name = xarML('Block Groups');
    $itemtypes[2] = array('label' => xarVarPrepForDisplay($name),
                          'title' => xarVarPrepForDisplay(xarML('Display #(1)',$name)),
                          'url'   => $showurl ? xarModURL('blocks','admin','view_groups') : ''
                         );

    $name = xarML('Block Instances');
    $itemtypes[3] = array('label' => xarVarPrepForDisplay($name),
                          'title' => xarVarPrepForDisplay(xarML('Display #(1)',$name)),
                          'url'   => $showurl ? xarModURL('blocks','admin','view_instances') : ''
                         );

    return $itemtypes;
}

?>
