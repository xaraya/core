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

function commerce_userapi_get_country_name($args) {
    extract($args);
    if(!isset($value)) {
    $msg = xarML('Wrong arguments to commerce_userapi_get_country_name');
    xarExceptionSet(XAR_SYSTEM_EXCEPTION,
                'BAD_PARAM',
                 new SystemException($msg));
    return false;
    }
    $country_array = xarModAPIFunc('commerce','user','get_countries',array('value' => $value));
    return $country_array['countries_name'];
}
 ?>
