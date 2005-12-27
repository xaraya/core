<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 XT-Commerce
// ----------------------------------------------------------------------

function commerce_userapi_get_currencies_values($code) {
    $currency_values = new xenQuery("select * from " . TABLE_CURRENCIES . " where code = '" . $code . "'");
      $q = new xenQuery();
      if(!$q->run()) return;
    $currencie_data=$q->output();
    return $currencie_data;
  }

 ?>