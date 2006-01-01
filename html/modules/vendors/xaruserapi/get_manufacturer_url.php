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

function commerce_userapi_get_manufacturer_url($args)
{
    extract($args);
    include_once 'modules/xen/xarclasses/xenquery.php';
    $xartables = xarDBGetTables();
    $q = new xenQuery('SELECT', $xartables['commerce_manufacturers_info'], array('manufacturers_url'));
    $q->eq('manufacturers_id', $manufacturers_id);
    $q->eq('languages_id', $language_id);
    if(!$q->run()) return;
    $manufacturer = $q->row();
    return $manufacturer['manufacturers_url'];
}
?>