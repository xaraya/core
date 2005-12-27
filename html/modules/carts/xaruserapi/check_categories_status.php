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

function commerce_userapi_check_categories_status($args)
{
    extract($args);
    if (!isset($categories_id) || $categories_id == 0) return 0;

    include_once 'modules/xen/xarclasses/xenquery.php';
    $xartables = xarDBGetTables();
    $q = new xenQuery('SELECT');
    $q->addtable($xartables['categories'],'xc');
    $q->addtable($xartables['commerce_categories'],'c');
    $q->addfields(array('xc.xar_parent AS parent_id',
                  'c.categories_status AS categories_status'));
    $q->eq('categories_id', $categories_id);
    if(!$q->run()) return;
    $categorie_data = $q->row();
    if ($categorie_data['categories_status'] == 0) {
        return 1;
    } else {
        if ($categorie_data['parent_id'] != 0) {
            if (xtc_check_categories_status($categorie_data['parent_id']) >= 1) return 1;
        }
        return 0;
    }
}
 ?>