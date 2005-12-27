<?php
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2003 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Jim McDonald
// Modified by: Nuncanada
// Modified by: marcinmilan
// Purpose of file:  Initialisation functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 XT-Commerce
//  (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
//  (c) 2002-2003 osCommerce (oscommerce.sql,v 1.83); www.oscommerce.com
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------

    function products_admin_start()
    {

/*
$blocks = xarModAPIFunc('blocks','user','getall');
$blockarray = array();
foreach ($blocks as $block) {
    if ($block['state'] == 2) {
        if(!xarModAPIFunc('blocks', 'admin', 'deactivate', array('bid' => $block['bid']))) return;
        $blockarray[] = $block['bid'];
    }
}
xarSessionSetVar('inactivated', serialize($blockarray));


    // Show the configmenu block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'productsconfig'));
    if(!xarModAPIFunc('blocks', 'admin', 'activate', array('bid' => $blockinfo['bid']))) return;

    // Hide the adminpanel block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'adminpanel'));
    if(!xarModAPIFunc('blocks', 'admin', 'deactivate', array('bid' => $blockinfo['bid']))) return;

    // Hide the main menu block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'mainmenu'));
    if(!xarModAPIFunc('blocks', 'admin', 'deactivate', array('bid' => $blockinfo['bid']))) return;

    // Hide the admin block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'productsadmininfo'));
    if(!xarModAPIFunc('blocks', 'admin', 'deactivate', array('bid' => $blockinfo['bid']))) return;

    // Hide the categories block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'productscategories'));
    if(!xarModAPIFunc('blocks', 'admin', 'deactivate', array('bid' => $blockinfo['bid']))) return;

    // Hide the search block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'productssearch'));
    if(!xarModAPIFunc('blocks', 'admin', 'deactivate', array('bid' => $blockinfo['bid']))) return;

    // Hide the information block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'productsinformation'));
    if(!xarModAPIFunc('blocks', 'admin', 'deactivate', array('bid' => $blockinfo['bid']))) return;

    // Hide the language block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'productslanguage'));
    if(!xarModAPIFunc('blocks', 'admin', 'deactivate', array('bid' => $blockinfo['bid']))) return;

    // Hide the manufacturers block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'productsmanufacturers'));
    if(!xarModAPIFunc('blocks', 'admin', 'deactivate', array('bid' => $blockinfo['bid']))) return;

    // Hide the currencies block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'productscurrencies'));
    if(!xarModAPIFunc('blocks', 'admin', 'deactivate', array('bid' => $blockinfo['bid']))) return;

    // Hide the shopping cart block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'productscart'));
    if(!xarModAPIFunc('blocks', 'admin', 'deactivate', array('bid' => $blockinfo['bid']))) return;

    // Show  the exit menu
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'productsexit'));
    if(!xarModAPIFunc('blocks', 'admin', 'activate', array('bid' => $blockinfo['bid']))) return;

*/
        xarResponseRedirect(xarModURL('products', 'admin', 'configuration',array('gID' => 1)));
    }
?>