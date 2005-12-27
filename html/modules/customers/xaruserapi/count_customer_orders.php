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

function commerce_userapi_count_customer_orders($args)
{
    include_once 'modules/xen/xarclasses/xenquery.php';
    $xartables = xarDBGetTables();
    extract($args);

    $q = new xenQuery('SELECT',$xartables['commerce_orders'],'count(*) as total');
    $q->eq('customers_id', xarSessionGetVar('uid'));
    if(!$q->run()) return;
    $orders_check = $q->row();
    return $orders_check['total'];
}
 ?>