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

// Recursively go through the categories and retreive all parent categories IDs
// TABLES: categories
  function commerce_userapi_get_parent_categories(&$categories, $categories_id) {
    $parent_categories_query = new xenQuery("select parent_id from " . TABLE_CATEGORIES . " where categories_id = '" . $categories_id . "'");
      $q = new xenQuery();
      if(!$q->run()) return;
    while ($parent_categories = $q->output()) {
      if ($parent_categories['parent_id'] == 0) return true;
      $categories[sizeof($categories)] = $parent_categories['parent_id'];
      if ($parent_categories['parent_id'] != $categories_id) {
        xtc_get_parent_categories($categories, $parent_categories['parent_id']);
      }
    }
  }
 ?>