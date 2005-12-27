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

function commerce_adminapi_remove_category($args)
{
//FIXME: create an API function for this stuff
    include_once 'modules/xen/xarclasses/xenquery.php';
    xarModAPILoad('categories');
    $xartables = xarDBGetTables();

    extract($args);
    if(!isset($category_id)) $category_id = 0;

    $q = new xenQuery('SELECT',
                      $xartables['categories'],'xar_image as image');
    $q->eq('xar_cid',$category_id);
    if(!$q->run()) return;
    $category = $q->row();
    $category_image = $category['image'];

    $q = new xenQuery('SELECT',
                      $xartables['commerce_categories'],'count(*) as total');
    $q->eq('categories_image',$category_image);
    if(!$q->run()) return;
    $category = $q->row();
    $duplicates = $category['total'];

    if ($duplicates < 2) {
        if (file_exists("modules/commerce/xarimages/" . $category_image)) {
            @unlink("modules/commerce/xarimages/" . $category_image);
        }
    }

    $q = new xenQuery('DELETE',$xartables['categories']);
    $q->eq('xar_cid',$category_id);
    if(!$q->run()) return;
    $q = new xenQuery('DELETE',$xartables['commerce_categories']);
    $q->eq('categories_id',$category_id);
    if(!$q->run()) return;
    $q = new xenQuery('DELETE',$xartables['commerce_categories_description']);
    $q->eq('categories_id',$category_id);
    if(!$q->run()) return;
    $q = new xenQuery('DELETE',$xartables['commerce_products_to_categories']);
    $q->eq('categories_id',$category_id);
    if(!$q->run()) return;
}
?>