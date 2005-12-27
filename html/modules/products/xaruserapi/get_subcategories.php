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

  function commerce_userapi_get_subcategories(&$subcategories_array, $parent_id = 0) {
    $subcategories_query = new xenQuery("select categories_id from " . TABLE_CATEGORIES . " where parent_id = '" . $parent_id . "'");
      $q = new xenQuery();
      if(!$q->run()) return;
    while ($subcategories = $q->output()) {
      $subcategories_array[sizeof($subcategories_array)] = $subcategories['categories_id'];
      if ($subcategories['categories_id'] != $parent_id) {
        xtc_get_subcategories($subcategories_array, $subcategories['categories_id']);
      }
    }
  }
 ?>