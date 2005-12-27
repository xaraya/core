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

function commerce_userapi_remove_product($args)
{
    include_once 'modules/xen/xarclasses/xenquery.php';
    $xartables = xarDBGetTables();

    extract($args);
    $q = new xenQuery('SELECT',$xartables['commerce_products'],'products_image');
    $q->eq('products_id',$pID);
    if(!$q->run()) return;
    $product_image = $q->row();

    $q = new xenQuery('SELECT',$xartables['commerce_products'],'count(*) as total');
    $q->eq('products_id',$product_image['products_image']);
    if(!$q->run()) return;
    $duplicate_image = $q->row();
    if ($duplicate_image['total'] < 2) {
        if (file_exists('modules/xarimages/popup_images' . $product_image['products_image']))
            @unlink('modules/xarimages/popup_images' . $product_image['products_image']);
    }
// START CHANGES
    $image_subdir = 'product_images';
    if (substr($image_subdir, -1) != '/') $image_subdir .= '/';
    if (file_exists('modules/xarimages/' . $image_subdir . $product_image['products_image']))
        @unlink('modules/xarimages/' . $image_subdir . $product_image['products_image']);
    }
// END CHANGES

    $q = new xenQuery('DELETE',$xartables['commerce_specials']);
    $q->eq('products_id',$pID);
    if(!$q->run()) return;
    $q = new xenQuery('DELETE',$xartables['commerce_products']);
    $q->eq('products_id',$pID);
    if(!$q->run()) return;
    $q = new xenQuery('DELETE',$xartables['commerce_products_to_categories']);
    $q->eq('products_id',$pID);
    if(!$q->run()) return;
    $q = new xenQuery('DELETE',$xartables['commerce_products_descriptions']);
    $q->eq('products_id',$pID);
    if(!$q->run()) return;
    $q = new xenQuery('DELETE',$xartables['commerce_products_attributes']);
    $q->eq('products_id',$pID);
    if(!$q->run()) return;
    $q = new xenQuery('DELETE',$xartables['commerce_customers_basket']);
    $q->eq('products_id',$pID);
    if(!$q->run()) return;
    $q = new xenQuery('DELETE',$xartables['commerce_customers_basket_attributes']);
    $q->eq('products_id',$pID);
    if(!$q->run()) return;

    $q = new xenQuery('SELECT',$xartables['commerce_reviews'],'reviews_id');
    $q->eq('products_id',$pID);
    if(!$q->run()) return;
    while ($q->output() as $product_reviews) {
        $q = new xenQuery('DELETE',$xartables['commerce_reviews_description']);
        $q->eq('reviews_id',$product_reviews['reviews_id']);
        if(!$q->run()) return;
    }
    $q = new xenQuery('DELETE',$xartables['commerce_reviews']);
    $q->eq('products_id',$pID);
    if(!$q->run()) return;
}
?>