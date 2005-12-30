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

//set customer satus to new customer for upgrade account
function commerce_userapi_set_customer_status_upgrade($customer_id){

if ( ($_SESSION['customer_status_value']['customers_status_id'] == "' . DEFAULT_CUSTOMERS_STATUS_ID_NEWSLETTER .'" ) AND ($_SESSION['customer_status_value']['customers_is_newsletter'] == 0 ) ) {
  new xenQuery("update " . TABLE_CUSTOMERS . " set customers_status = '" . DEFAULT_CUSTOMERS_STATUS_ID . "' where customers_id = '" . $_SESSION['customer_id'] . "'");
  new xenQuery("insert into " . TABLE_CUSTOMERS_STATUS_HISTORY . " (customers_id, new_value, old_value, date_added, customer_notified) values ('" . $_SESSION['customer_id'] . "', '" . DEFAULT_CUSTOMERS_STATUS_ID . "', '" . DEFAULT_CUSTOMERS_STATUS_ID_NEWSLETTER . "', now(), '" . $customer_notified . "')");
}
return 1;
}

 ?>
