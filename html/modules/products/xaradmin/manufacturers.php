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

function products_admin_manufacturers()
{
    include_once 'modules/xen/xarclasses/xenquery.php';
    include_once 'modules/commerce/xarclasses/object_info.php';
    include_once 'modules/commerce/xarclasses/split_page_results.php';
    $xartables = xarDBGetTables();

    if(!xarVarFetch('action', 'str',  $action, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('page',   'int',  $page, 1, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('cID',    'int',  $cID, NULL, XARVAR_DONT_SET)) {return;}
    if (isset($action)) {
        switch ($action) {
            case 'insert':
                // Write to the manufacturers table
                if(!xarVarFetch('manufacturers_name','str',$manufacturers_name)) {return;}
                $q = new xenQuery('INSERT');
                $q->settable($xartables['products_manufacturers']);
                $q->addfield('manufacturers_name',$manufacturers_name);
                $q->addfield('date_added',mktime());
                if(!$q->run()) return;
                $lastID = $q->lastid($xartables['products_manufacturers'], 'manufacturers_id');

                $q->settable($xartables['products_manufacturers_info']);
                $q->clearfields();
                // Write to the manufacturers info table
                if(!xarVarFetch('manufacturers_url','array',$manufacturers_url_array)) {return;}
                $languages = xarModAPIFunc('commerce','user','get_languages');
                for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                    $language_id = $languages[$i]['id'];
                    $q->addfield('manufacturers_id',$lastID);
                    $q->addfield('manufacturers_url',$manufacturers_url_array[$language_id]);
                    $q->addfield('languages_id',$language_id);
                    if(!$q->run()) return;
                }
                xarResponseRedirect(xarModURL('products','admin','manufacturers'));
                break;
            case 'save':
                // Write to the manufacturers table
                if(!xarVarFetch('manufacturers_name','str',$manufacturers_name)) {return;}
                $q = new xenQuery('UPDATE', $xartables['products_manufacturers']);
                $q->addfield('manufacturers_name',$manufacturers_name);
                $q->addfield('last_modified',mktime());
                $q->eq('manufacturers_id',$cID);
                if(!$q->run()) return;

                $q->settable($xartables['products_manufacturers_info']);
                $q->clearfields();
                // Write to the manufacturers info table
                if(!xarVarFetch('manufacturers_url','array',$manufacturers_url_array)) {return;}
                $languages = xarModAPIFunc('commerce','user','get_languages');
                for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                    $language_id = $languages[$i]['id'];
                    $q->addfield('manufacturers_url',$manufacturers_url_array[$language_id]);
                    $q->eq('languages_id',$language_id);
                    if(!$q->run()) return;
                }
                xarResponseRedirect(xarModURL('products','admin','manufacturers',array('page' => $page,'cID' => $cID)));
                break;
            case 'deleteconfirm':
                if(!xarVarFetch('delete_image','str',$delete_image)) {return;}
                if ($delete_image == 'on') {
                    $q = new xenQuery('SELECT', $xartables['products_manufacturers'],array('manufacturers_image'));
                    $q->eq('manufacturers_id',$cID);
                    if(!$q->run()) return;
                    $manufacturer = $q->row();
                    $image_location = 'modules/products/xarimages/' . $manufacturer['manufacturers_image'];
                    if (file_exists($image_location)) @unlink($image_location);
                }
                $q = new xenQuery('DELETE', $xartables['products_manufacturers']);
                $q->eq('manufacturers_id',$cID);
                if(!$q->run()) return;
                $q = new xenQuery('DELETE', $xartables['products_manufacturers_info']);
                $q->eq('manufacturers_id',$cID);
                if(!$q->run()) return;
                if(!xarVarFetch('delete_products','str',$delete_products)) {return;}
                if ($delete_products == 'on') {
                    $q = new xenQuery('SELECT', $xartables['products_products']);
                    $q->eq('manufacturers_id',$cID);
                    if(!$q->run()) return;
                    foreach ($q->output() as $product) {
                      xarModAPIFunc('products','admin','remove_product',array('id' =>$products['products_id']));
                    }
                }
                else {
                    $q = new xenQuery('UPDATE', $xartables['products_products']);
                    $q->addfield('manufacturers_id','');
                    $q->eq('manufacturers_id',$cID);
                    if(!$q->run()) return;
                }
                xarResponseRedirect(xarModURL('products','admin','manufacturers',array('page' => $page)));
                break;
        }
    }

    $localeinfo = xarLocaleGetInfo(xarMLSGetSiteLocale());
    $data['language'] = $localeinfo['lang'] . "_" . $localeinfo['country'];
    $currentlang = xarModAPIFunc('commerce','user','get_language',array('locale' => $data['language']));

    $q = new xenQuery('SELECT');
    $q->addtable($xartables['products_manufacturers'], 'm');
    $q->addtable($xartables['products_manufacturers_info'], 'mi');
    $q->addfields(array('m.manufacturers_id', 'm.manufacturers_name', 'm.manufacturers_image', 'm.date_added', 'm.last_modified'));
    $q->join('mi.manufacturers_id','m.manufacturers_id');
    $q->eq('mi.languages_id',$currentlang['id']);
    $q->setorder('manufacturers_name');
    $q->setrowstodo(xarModGetVar('commerce', 'itemsperpage'));
    $q->setstartat(($page - 1) * xarModGetVar('commerce', 'itemsperpage') + 1);
    if(!$q->run()) return;

    $pager = new splitPageResults($page,
                                  $q->getrows(),
                                  xarModURL('products','admin','manufacturers'),
                                  xarModGetVar('commerce', 'itemsperpage')
                                 );
    $data['pagermsg'] = $pager->display_count('Displaying #(1) to #(2) (of #(3) manufacturers)');
    $data['displaylinks'] = $pager->display_links();

    $items =$q->output();
    $limit = count($items);
    for ($i=0;$i<$limit;$i++) {
        if ((!isset($cID) || $cID == $items[$i]['manufacturers_id']) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
            $q = new xenQuery('SELECT',$xartables['products_products']);
            $q->addfields('count(*) as products_count');
            $q->eq('manufacturers_id',$items[$i]['manufacturers_id']);
            if(!$q->run()) return;
            $manufacturer_products = $q->row();
            $items[$i] = array_merge($items[$i],$manufacturer_products);
            $cInfo = new objectInfo($items[$i]);
            $items[$i]['url'] = xarModURL('products','admin','manufacturers',array('page' => $page,'cID' => $cInfo->manufacturers_id, 'action' => 'edit'));
        }
        else {
            $items[$i]['url'] = xarModURL('products','admin','manufacturers',array('page' => $page, 'cID' => $items[$i]['manufacturers_id']));
        }
    }

    $data['items'] = $items;
    $data['cInfo'] = isset($cInfo) ? get_object_vars($cInfo) : '';
    $data['page'] = $page;
    $data['action'] = $action;

    $languages = xarModAPIFunc('commerce','user','get_languages');
    $data['languages'] = $languages;
    return $data;
}
?>