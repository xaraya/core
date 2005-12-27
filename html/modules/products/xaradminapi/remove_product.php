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

function commerce_admin_remove_product($args) {
    //FIXME: create an API function for this stuff
    include_once 'modules/xen/xarclasses/xenquery.php';
    xarModAPILoad('commerce');
    $xartables = xarDBGetTables();

    extract($args);
    if(!isset($id)) $id = '';
    $product_id = $id;

    $q = new xenQuery('SELECT', $xartables['commerce_products'],array('products_image'));
    $q->eq('product_id',$product_id);
    if(!$q->run()) return;
    $product_image = $q->row();

    $q = new xenQuery('SELECT', $xartables['commerce_products'],array('count(*) as total'));
    $q->eq('products_image',$product_image['products_image']);
    if(!$q->run()) return;
    $duplicate_image = $q->row();

    if ($duplicate_image['total'] < 2) {
      if (file_exists('modules/commerce/xarimages/product_images/' . $product_image['products_image'])) {
        @unlink('modules/commerce/xarimages/product_images/' . $product_image['products_image']);
      }
    // START CHANGES
      $image_subdir = BIG_IMAGE_SUBDIR;
      if (substr($image_subdir, -1) != '/') $image_subdir .= '/';
      if (file_exists(DIR_FS_CATALOG_IMAGES . $image_subdir . $product_image['products_image'])) {
        @unlink(DIR_FS_CATALOG_IMAGES . $image_subdir . $product_image['products_image']);
      }
    // END CHANGES
    }

    $q = new xenQuery('DELETE', $xartables['commerce_specials']);
    $q->eq('products_id',$product_id);
    if(!$q->run()) return;
    $q->settable($xartables['commerce_products']);
    if(!$q->run()) return;
    $q->settable($xartables['commerce_products']);
    if(!$q->run()) return;
    $q->settable($xartables['commerce_products_to_categories']);
    if(!$q->run()) return;
    $q->settable($xartables['commerce_products_description']);
    if(!$q->run()) return;
    $q->settable($xartables['commerce_products_attibutes']);
    if(!$q->run()) return;
    $q->settable($xartables['commerce_customers_basket']);
    if(!$q->run()) return;
    $q->settable($xartables['commerce_customers_basket_attributes']);
    if(!$q->run()) return;

    $q = new xenQuery('SELECT', $xartables['commerce_reviews'],array('reviews_id'));
    $q->eq('products_id',$product_id);
    if(!$q->run()) return;

    $q1 = new xenQuery('DELETE', $xartables['commerce_reviews_description']);
    foreach ($q->output() as $product_review) {
        $q1->eq('reviews_id',$product_review['reviews_id']);
        $q1->run();
    }

    $q = new xenQuery('DELETE', $xartables['commerce_reviews']);
    $q->eq('products_id',$product_id);
    if(!$q->run()) return;

//    if (USE_CACHE == 'true') {
//      xtc_reset_cache_block('categories');
//      xtc_reset_cache_block('also_purchased');
//    }
}
?>