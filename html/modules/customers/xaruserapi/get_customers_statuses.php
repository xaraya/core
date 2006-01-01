<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 XT-Commerce
//   based on Third Party contribution:
//   Customers Status v3.x  (c) 2002-2003 Copyright Elari elari@free.fr | www.unlockgsm.com/dload-osc/ | CVS : http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/elari/?sortby=date#dirlist
//  (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
//  (c) 2002-2003 osCommerce (oscommerce.sql,v 1.83); www.oscommerce.com
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------

// Return all customers statuses for a specified language_id and return an array(array())
// Use it to make pull_down_menu, checkbox....

  function commerce_userapi_get_customers_statuses() {

    include_once 'modules/xen/xarclasses/xenquery.php';
    $xartables = xarDBGetTables();

    $localeinfo = xarLocaleGetInfo(xarMLSGetSiteLocale());
    $currentlang = xarModAPIFunc('commerce','user','get_language',array('locale' => $localeinfo['lang'] . "_" . $localeinfo['country']));

    $customers_statuses_array = array();
    $q = new xenQuery('SELECT',$xartables['commerce_customers_status']);
    $q->eq('language_id',$currentlang['id']);
    $q->setorder('customers_status_id');
    $i=1;
    if(!$q->run()) return;
    foreach ($q->output() as $customers_statuses) {
       $i=$customers_statuses['customers_status_id'];
       $customers_statuses_array[] = array('id' => $customers_statuses['customers_status_id'],
                                           'text' => $customers_statuses['customers_status_name'],
                                           'csa_public' => $customers_statuses['customers_status_public'],
                                           'csa_show_price' => $customers_statuses['customers_status_show_price'],
                                           'csa_show_price_tax' => $customers_statuses['customers_status_show_price_tax'],
                                           'csa_image' => $customers_statuses['customers_status_image'],
                                           'csa_discount' => $customers_statuses['customers_status_discount'],
                                           'csa_ot_discount_flag' => $customers_statuses['customers_status_ot_discount_flag'],
                                           'csa_ot_discount' => $customers_statuses['customers_status_ot_discount'],
                                           'csa_graduated_prices' => $customers_statuses['customers_status_graduated_prices'],
//                                           'csa_cod_permission' => $customers_statuses['customers_status_cod_permission'],
//                                           'csa_cc_permission' => $customers_statuses['customers_status_cc_permission'],
//                                           'csa_bt_permission' => $customers_statuses['customers_status_bt_permission'],
                                           );
//       echo $customers_statuses_array[$i]['id'] . $customers_statuses_array[$i]['text'] . $customers_statuses_array[$i]['csa_image'] . $customers_statuses_array[$i]['csa_discount'] . $customers_statuses_array[$i]['csa_ot_discount_flag'] . $customers_statuses_array[$i]['csa_ot_discount'] . '<br>';
     }
    return $customers_statuses_array;
  }
 ?>
