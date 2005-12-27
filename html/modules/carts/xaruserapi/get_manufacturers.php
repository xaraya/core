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

function commerce_userapi_get_manufacturers($args)
{
    extract($args);
    if (!is_array($manufacturers_array)) $manufacturers_array = array();
    //FIXME: create an API function for this stuff
    include_once 'modules/xen/xarclasses/xenquery.php';
    $xartables = xarDBGetTables();

    $q = new xenQuery('SELECT',
                  $xartables['commerce_manufacturers'],
                  array('manufacturers_id AS id','manufacturers_name AS text')
                 );
    $q->setorder('manufacturers_name');
    if(!$q->run()) return;

    return array_merge($manufacturers_array,$q->output());
}
?>