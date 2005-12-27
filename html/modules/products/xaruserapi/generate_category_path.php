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

function commerce_userapi_generate_category_path($args)
{
    //FIXME: create an API function for this stuff
    include_once 'modules/xen/xarclasses/xenquery.php';
    xarModAPILoad('categories');
    $xartables = xarDBGetTables();

    extract($args);
    if(!isset($from)) $from = 'category';
    if(!isset($categories_array)) $categories_array = '';
    if(!isset($index)) $index = 0;

    $localeinfo = xarLocaleGetInfo(xarMLSGetSiteLocale());
    $data['language'] = $localeinfo['lang'] . "_" . $localeinfo['country'];
    $currentlang = xarModAPIFunc('commerce','user','get_language',array('locale' => $data['language']));

    if (!is_array($categories_array)) $categories_array = array();

    if ($from == 'product') {
        $q = new xenQuery('SELECT',$xartables['commerce_products_to_categories'],'categories_id');
        $q->eq('products_id',$id);
        if(!$q->run()) return;
            foreach ($q->output() as $categories) {
                if ($categories['categories_id'] == '0') {
                    $categories_array[$index][] = array('id' => '0', 'text' => xarML('Top'));
               } else {
                    $q = new xenQuery('SELECT');
                    $q->addtable($xartables['categories'],'xc');
                    $q->addtable($xartables['commerce_categories_description'],'cd');
                    $q->addfields(array('xc.xar_parent AS parent',
                                        'cd.categories_name AS name'));
                    $q->join('xc.xar_cid','cd.categories_id');
                    $q->eq('xc.xar_cid',$categories['categories_id']);
                    $q->eq('cd.language_id',$currentlang['id']);
                    if(!$q->run()) return;
                    $category = $q->row();
                    $categories_array[$index][] = array('id' => $categories['categories_id'], 'text' => $category['name']);
                    if (isset($category['parent_id']) && ($category['parent_id'] != '0'))
                    $categories_array = xarModAPIFunc('commerce','user','generate_category_path', array(
                            'id' =>$category['parent'],
                            'from' => 'category',
                            'categories_array' => $categories_array,
                            'index' => $index)
                                        );
                    $categories_array[$index] = array_reverse($categories_array[$index]);
                }
                $index++;
            }
    } elseif ($from == 'category') {
        $q = new xenQuery('SELECT');
        $q->addtable($xartables['categories'],'xc');
        $q->addtable($xartables['commerce_categories_description'],'cd');
        $q->addfields(array('xc.xar_parent AS parent',
                            'cd.categories_name AS name'));
        $q->eq('xc.xar_cid',$id);
        $q->join('xc.xar_cid','cd.categories_id');
        $q->eq('cd.language_id',$currentlang['id']);
        if(!$q->run()) return;
        $category = $q->row();
        if ($category == array()) return $category;
        $categories_array[$index][] = array('id' => $id, 'text' => $category['name']);
        if ( isset($category['parent']) && ($category['parent'] != '0') )
            $categories_array = xarModAPIFunc('commerce','user','generate_category_path', array(
                    'id' =>$category['parent'],
                    'from' => 'category',
                    'categories_array' => $categories_array,
                    'index' => $index)
                    );
    }
    return $categories_array;
}
?>