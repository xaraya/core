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

function commerce_userapi_get_language($args) {
    include_once 'modules/xen/xarclasses/xenquery.php';
    xarModAPILoad('commerce');
    $xartables = xarDBGetTables();

    extract($args);
    $q = new xenQuery('SELECT',
                      $xartables['commerce_languages'],
                      array('languages_id as id','name','code','image','directory')
                     );
    $q->eq('directory',$locale);
    if(!$q->run()) return;
    return $q->row();
}
?>