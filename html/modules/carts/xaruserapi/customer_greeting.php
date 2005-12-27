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

// Return a customer greeting
  function commerce_userapi_customer_greeting() {

    if (isset($_SESSION['customer_first_name']) && isset($_SESSION['customer_id'])) {
      $greeting_string = sprintf(TEXT_GREETING_PERSONAL, $_SESSION['customer_first_name'], xarModURL('commerce','user','products_new'));
    } else {
      $greeting_string = sprintf(TEXT_GREETING_GUEST, xarModURL('commerce','user','login', '', 'SSL'), xarModURL('commerce','user','create_account', '', 'SSL'));
    }

    return $greeting_string;
  }
 ?>