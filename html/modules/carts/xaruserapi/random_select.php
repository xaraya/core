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

function commerce_userapi_random_select($args) {
    extract($args);
    if(!isset($query)) {
    $msg = xarML('Wrong arguments to commerce_userapi_address_format');
    xarExceptionSet(XAR_SYSTEM_EXCEPTION,
                'BAD_PARAM',
                 new SystemException($msg));
    return false;
    }
    $random_product = '';
    $random_query = $query;
    $random_query->run();
    $num_rows = $random_query->getrows();
    if ($num_rows > 0) {
      $random_row = xarModAPIFunc('commerce','user','rand',array('min' =>0,'max' =>($num_rows - 1)));
      $random_product = $random_query->row($random_row);
    }
    return $random_product;
}
?>