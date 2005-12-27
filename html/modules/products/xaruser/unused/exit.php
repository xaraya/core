<?php
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2003 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: marcinmilan
// Modified by: Nuncanada
// Modified by: marcinmilan
// Purpose of file:  Exit the commerce module
// ----------------------------------------------------------------------

function commerce_user_exit()
{
    // Show the config menu block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'commerceconfig'));
    if(!xarModAPIFunc('blocks', 'admin', 'deactivate', array('bid' => $blockinfo['bid']))) return;

    // Show the adminpanels block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'adminpanel'));
    if(!xarModAPIFunc('blocks', 'admin', 'activate', array('bid' => $blockinfo['bid']))) return;

    // Show the main menu block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'mainmenu'));
    if(!xarModAPIFunc('blocks', 'admin', 'activate', array('bid' => $blockinfo['bid']))) return;

    // Hide the categories block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'commerceadmininfo'));
    if(!xarModAPIFunc('blocks', 'admin', 'deactivate', array('bid' => $blockinfo['bid']))) return;

    // Hide the categories block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'commercecategories'));
    if(!xarModAPIFunc('blocks', 'admin', 'deactivate', array('bid' => $blockinfo['bid']))) return;

    // Hide the search block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'commercesearch'));
    if(!xarModAPIFunc('blocks', 'admin', 'deactivate', array('bid' => $blockinfo['bid']))) return;

    // Hide the information block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'commerceinformation'));
    if(!xarModAPIFunc('blocks', 'admin', 'deactivate', array('bid' => $blockinfo['bid']))) return;

    // Hide the language block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'commercelanguage'));
    if(!xarModAPIFunc('blocks', 'admin', 'deactivate', array('bid' => $blockinfo['bid']))) return;

    // Hide the manufacturers block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'commercemanufacturers'));
    if(!xarModAPIFunc('blocks', 'admin', 'deactivate', array('bid' => $blockinfo['bid']))) return;

    // Hide the currencies block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'commercecurrencies'));
    if(!xarModAPIFunc('blocks', 'admin', 'deactivate', array('bid' => $blockinfo['bid']))) return;

    // Hide the shopping cart block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'commercecart'));
    if(!xarModAPIFunc('blocks', 'admin', 'deactivate', array('bid' => $blockinfo['bid']))) return;

    // Hide  the exit menu
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'commerceexit'));
    if(!xarModAPIFunc('blocks', 'admin', 'deactivate', array('bid' => $blockinfo['bid']))) return;

/*    $blockarray = unserialize(xarSessionGetVar('inactivated'));
    foreach ($blockarray as $block) {
        if(!xarModAPIFunc('blocks', 'admin', 'activate', array('bid' => $block['bid']))) return;
    }
*/
    xarResponseRedirect('index.php');
}
?>