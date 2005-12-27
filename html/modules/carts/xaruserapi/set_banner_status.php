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

// Sets the status of a banner
function commerce_userapi_set_banner_status($args) {
    include_once 'modules/xen/xarclasses/xenquery.php';
    $xartables = xarDBGetTables();

    extract($args);
    if (!isset($status)) $status = 0;
    $q = new xenQuery('UPDATE',$xartables['commerce_banners']);
    $q->addfield('date_status_change',mktime());
    $q->eq('banners_id',$banners_id);
    if ($status == '1') {
        $q->eq('status',1);
        $q->addfield('date_scheduled',NULL);
        if(!$q->run()) return;
    } elseif ($status == '0') {
        $q->eq('status',0);
        if(!$q->run()) return;
    } else {
      return -1;
    }
}
?>