<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 XT-Commerce
//   Third Party contributions:
//   New Attribute Manager v4b                Autor: Mike G | mp3man@internetwork.net | http://downloads.ephing.com
//  (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
//  (c) 2002-2003 osCommerce (oscommerce.sql,v 1.83); www.oscommerce.com
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------

function commerce_userapi_findtitle($args) {
    include_once 'modules/xen/xarclasses/xenquery.php';
    xarModAPILoad('categories');
    $xartables = xarDBGetTables();

    extract($args);

    $q = new xenQuery('SELECT', $xartables['commerce_products_description'], 'products_name');
    $q->eq('language_id',$language_id);
    $q->eq('products_id',$pID);
    if(!$q->run()) return;

    $matches = $q->row();
    if ($matches != array()) return $matches['products_name'];
    return "Something isn't right....";
}
?>