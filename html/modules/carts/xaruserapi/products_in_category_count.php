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

function commerce_userapi_products_in_category_count($args)
{
    include_once 'modules/xen/xarclasses/xenquery.php';
    $xartables = xarDBGetTables();
    extract($args);
    if (!isset($include_deactivated)) $include_deactivated = false;

    $q = new xenQuery('SELECT');
    $q->addtable($xartables['commerce_products'], 'p');
    $q->addtable($xartables['commerce_products_to_categories'], 'p2c');
    $q->addfield('count(*) as total');
    $q->eq('p2c.categories_id', $categories_id);
    $q->join('p.products_id', 'p2c.products_id');
    if (isset($include_deactivated) && !$include_deactivated) $q->eq('p.products_status', 1);

    if(!$q->run()) return;
    $products_count = 0;
    foreach ($q->output() as $products) {
        $products_count += $products['total'];
        $q1 = new xenQuery('SELECT', $xartables['commerce_categories'], 'categories_id');
        $q1->eq('parent_id', $categories_id);
        if(!$q1->run()) return;

        foreach ($q1->output() as $children) {
            $products_count += xarModAPIFunc('commerce', 'user', 'products_in_category_count', array('categories_id' => $children['categories_id'],
                  'include_deactivated' => $include_deactivated));
        }
    }
    return $products_count;
}
?>