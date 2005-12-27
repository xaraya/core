<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 XT-Commerce
// ----------------------------------------------------------------------

  function commerce_userapi_get_tax_class_id($products_id) {


    $tax_query = new xenQuery("SELECT
                               products_tax_class_id
                               FROM ".TABLE_PRODUCTS."
                               where products_id='".$products_id."'");
      $q = new xenQuery();
      if(!$q->run()) return;
    $tax_query_data=$q->output();

    return $tax_query_data['products_tax_class_id'];
  }
 ?>