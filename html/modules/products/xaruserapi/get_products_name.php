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

function commerce_userapi_get_products_name($args)
{
    //FIXME: create an API function for this stuff
    include_once 'modules/xen/xarclasses/xenquery.php';
    $xartables = xarDBGetTables();

    extract($args);
    if(!isset($product_id)) {
        $msg = xarML('Wrong arguments to commerce_userapi_get_products_name');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }
    if(!isset($language_id)) {
        $localeinfo = xarLocaleGetInfo(xarMLSGetSiteLocale());
        $data['language'] = $localeinfo['lang'] . "_" . $localeinfo['country'];
        $currentlang = xarModAPIFunc('commerce','user','get_language',array('locale' => $data['language']));
        $language_id = $currentlang['id'];
    }
    $q = new xenQuery('SELECT', $xartables['commerce_products_description']);
    $q->eq('products_id',$product_id);
    $q->eq('language_id',$language_id);
    if(!$q->run()) return;
    $product = $q->row();
    return $product['products_name'];
}
?>