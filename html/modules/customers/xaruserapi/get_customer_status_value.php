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

// Return all status info values for a customer_id in catalog, need to check session registered customer or will return dafault guest customer status value !
function commerce_userapi_get_customer_status_value($customer_id) {

  if (isset($_SESSION['customer_id'])) {
    $customer_status_query = new xenQuery("select c.customers_status, c.member_flag, cs.customers_status_id, cs.customers_status_name, cs.customers_status_public, cs.customers_status_show_price, cs.customers_status_show_price_tax, cs.customers_status_image, cs.customers_status_discount, cs.customers_status_ot_discount_flag, cs.customers_status_ot_discount, cs.customers_status_graduated_prices, cs.customers_status_cod_permission, cs.customers_status_cc_permission, cs.customers_status_bt_permission  FROM " . TABLE_CUSTOMERS . " as c left join " . TABLE_CUSTOMERS_STATUS . " as cs on customers_status = customers_status_id where c.customers_id='" . $_SESSION['customer_id'] . "' and cs.language_id = '" . $_SESSION['languages_id'] . "'");
  } else {
    $customer_status_query = new xenQuery("select                                    cs.customers_status_id, cs.customers_status_name, cs.customers_status_public, cs.customers_status_show_price, cs.customers_status_show_price_tax, cs.customers_status_image, cs.customers_status_discount, cs.customers_status_ot_discount_flag, cs.customers_status_ot_discount, cs.customers_status_graduated_prices  FROM "                                        . TABLE_CUSTOMERS_STATUS . " as cs                                           where cs.customers_status_id='" . DEFAULT_CUSTOMERS_STATUS_ID_GUEST . "' and cs.language_id = '" . $_SESSION['languages_id'] . "'");
    $customer_status_value['customers_status'] = DEFAULT_CUSTOMERS_STATUS_ID_GUEST;
  }

      $q = new xenQuery();
      if(!$q->run()) return;
  $customer_status_value = $q->output();

  xtc_session_register('customer_status_value');
return $customer_status_value;
}
 ?>
