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

function commerce_userapi_get_shipping_status()
{
    $localeinfo = xarLocaleGetInfo(xarMLSGetSiteLocale());
    $data['language'] = $localeinfo['lang'] . "_" . $localeinfo['country'];
    $currentlang = xarModAPIFunc('commerce','user','get_language',array('locale' => $data['language']));
    $data['currentlang'] = $currentlang;

    //FIXME: create an API function for this stuff
    include_once 'modules/xen/xarclasses/xenquery.php';
    $xartables = xarDBGetTables();

    $q = new xenQuery('SELECT',
                  $xartables['commerce_shipping_status'],
                  array('shipping_status_id AS id','shipping_status_name AS text')
                 );
    $q->eq('language_id',$currentlang['id']);
    $q->setorder('shipping_status_name');
    if(!$q->run()) return;

    return $q->output();
}
?>