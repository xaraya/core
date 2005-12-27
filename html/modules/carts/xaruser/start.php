<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 XT-Commerce
//  (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
//  (c) 2002-2003 osCommerce (oscommerce.sql,v 1.83); www.oscommerce.com
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------

  function commerce_user_start()
  {
    include_once 'modules/xen/xarclasses/xenquery.php';
    $xartables = xarDBGetTables();

//      include( 'includes/application_top.php');
      // the following cPath references come from application_top.php

//    xarResponseRedirect(xarModURL('commerce','user','default'));
    include ('modules/commerce/xarincludes/modules/default.php');


    // Show the admin info block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'commerceadmininfo'));
    if(!xarModAPIFunc('blocks', 'admin', 'activate', array('bid' => $blockinfo['bid']))) return;

    // Show the information block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'commerceinformation'));
    if(!xarModAPIFunc('blocks', 'admin', 'activate', array('bid' => $blockinfo['bid']))) return;

    // Show the language block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'commercelanguage'));
    if(!xarModAPIFunc('blocks', 'admin', 'activate', array('bid' => $blockinfo['bid']))) return;

    // Show the currencies block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'commercecurrencies'));
    if(!xarModAPIFunc('blocks', 'admin', 'activate', array('bid' => $blockinfo['bid']))) return;

    // Show the shopping cart block
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'commercecart'));
    if(!xarModAPIFunc('blocks', 'admin', 'activate', array('bid' => $blockinfo['bid']))) return;

    // Show  the exit menu
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('name'=> 'commerceexit'));
    if(!xarModAPIFunc('blocks', 'admin', 'activate', array('bid' => $blockinfo['bid']))) return;

//    $data['account'] = xarModAPIFunc('commerce','user','getaccount', array('accountid' => $account));
    $data['account'] = 1;
    return $data;
}
?>