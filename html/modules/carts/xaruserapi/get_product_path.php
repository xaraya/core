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

// Construct a category path to the product
// TABLES: products_to_categories
  function commerce_userapi_get_product_path($products_id) {
    $cPath = '';

    $category_query = new xenQuery("select p2c.categories_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_id = '" . (int)$products_id . "' and p.products_status = '1' and p.products_id = p2c.products_id limit 1");
    if ($category_query->getrows()) {
      $q = new xenQuery();
      if(!$q->run()) return;
      $category = $q->output();

      $categories = array();
      xtc_get_parent_categories($categories, $category['categories_id']);

      $categories = array_reverse($categories);

      $cPath = implode('_', $categories);

      if (xarModAPIFunc('commerce','user','not_null',array('arg' => $cPath))) $cPath .= '_';
      $cPath .= $category['categories_id'];
    }

    return $cPath;
  }
 ?>