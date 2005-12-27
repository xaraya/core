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

function commerce_userapi_get_countries($args)
{
    //FIXME: create an API function for this stuff
    include_once 'modules/xen/xarclasses/xenquery.php';
    xarModAPILoad('commerce');
    $xartables = xarDBGetTables();

    extract($args);
    if(!isset($value)) $value = '';
    $countries_id = $value;
    if(!isset($with_iso_codes)) $with_iso_codes = false;
    $countries_array = array();
    if (xarModAPIFunc('commerce','user','not_null',array('arg' => $countries_id))) {
        $q = new xenQuery('SELECT',
                          $xartables['commerce_countries'],
                          array('countries_name')
                         );
        $q->eq('countries_id',$countries_id);
        $q->setorder('countries_name');
        if ($with_iso_codes == true) {
            $q->addfields('countries_iso_code_2', 'countries_iso_code_3');
            if(!$q->run()) return;
            echo $q->output();
            $countries_values = $q->output();
            $countries_array = array('countries_name' => $countries_values['countries_name'],
                                     'countries_iso_code_2' => $countries_values['countries_iso_code_2'],
                                     'countries_iso_code_3' => $countries_values['countries_iso_code_3']);
        }
        else {
            if(!$q->run()) return;
            $countries_array = $q->row();
//            $countries_array = array('countries_name' => $countries_values['countries_name']);
        }
    }
    else {
        $q = new xenQuery('SELECT',
                          $xartables['commerce_countries'],
                          array('countries_id AS id','countries_name AS text')
                         );
        $q->setorder('countries_name');
        if(!$q->run()) return;
        $countries_array = $q->output();
    }
    return $countries_array;
}
?>