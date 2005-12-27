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

function products_userapi_get_category_tree($args)
{
//FIXME: create an API function for this stuff
    include_once 'modules/xen/xarclasses/xenquery.php';
    xarModAPILoad('categories');
    $xartables = xarDBGetTables();

    extract($args);
    if(!isset($parent_id)) $parent_id = 0;
    if(!isset($spacing)) $spacing = '';
    if(!isset($exclude)) $exclude = '';
    if(!isset($category_tree_array)) $category_tree_array = '';
    if(!isset($include_itself)) $include_itself = false;

    $localeinfo = xarLocaleGetInfo(xarMLSGetSiteLocale());
    $data['language'] = $localeinfo['lang'] . "_" . $localeinfo['country'];
    $currentlang = xarModAPIFunc('commerce','user','get_language',array('locale' => $data['language']));

    if (!is_array($category_tree_array)) $category_tree_array = array();
    if ( (sizeof($category_tree_array) < 1) && ($exclude != '0') ) $category_tree_array[] = array('id' => '0', 'text' => 'Top');

    if ($include_itself) {
        $q = new xenQuery('SELECT',
                          $xartables['products_categories_description'],'categories_name');
        $q->eq('language_id',$currentlang['id']);
        $q->eq('categories_id',$parent_id);
        if(!$q->run()) return;
        $category = $q->row();
        $category_tree_array[] = array('id' => $parent_id, 'text' => $category['categories_name']);
    }

    $q = new xenQuery('SELECT');
    $q->addtable($xartables['categories'],'c');
    $q->addtable($xartables['products_categories_description'],'cd');
    $q->addfields(array('c.xar_cid', 'cd.categories_name', 'c.xar_parent', 'cd.categories_id'));
    $q->join('c.xar_cid','cd.categories_id');
    $q->eq('language_id',$currentlang['id']);
    $q->eq('c.xar_parent',$parent_id);
//    $q->addorder('c.sort_order');
    $q->addorder('cd.categories_name');
    if(!$q->run()) return;
    foreach ($q->output() as $categories) {
        if ($exclude != $categories['categories_id']) $category_tree_array[] = array('id' => $categories['categories_id'], 'text' => $spacing . $categories['categories_name']);
        $category_tree_array = xarModAPIFunc('products','user','get_category_tree', array(
                                    'parent_id' => $categories['categories_id'],
                                    'spacing' => $spacing . '&nbsp;&nbsp;&nbsp;',
                                    'exclude' => $exclude,
                                    'category_tree_array' => $category_tree_array));
    }
    return $category_tree_array;
}
?>