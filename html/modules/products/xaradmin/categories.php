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
//   Third Party contribution:
//   Enable_Disable_Categories 1.3               Autor: Mikel Williams | mikel@ladykatcostumes.com
//   New Attribute Manager v4b                   Autor: Mike G | mp3man@internetwork.net | http://downloads.ephing.com
//   Category Descriptions (Version: 1.5 MS2)    Original Author:   Brian Lowe <blowe@wpcusrgrp.org> | Editor: Lord Illicious <shaolin-venoms@illicious.net>
//   Customers Status v3.x  (c) 2002-2003 Copyright Elari elari@free.fr | www.unlockgsm.com/dload-osc/ | CVS : http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/elari/?sortby=date#dirlist
// ----------------------------------------------------------------------

function products_admin_categories()
{
    include_once 'modules/xen/xarclasses/xenquery.php';
    include_once 'modules/commerce/xarclasses/object_info.php';
    include_once 'modules/commerce/xarclasses/split_page_results.php';
    xarModAPILoad('categories');
    $xartables = xarDBGetTables();
    $configuration = xarModAPIFunc('commerce','admin','load_configuration');
    $data['configuration'] = $configuration;

    if(!xarVarFetch('current_category_id',    'int',  $current_category_id, 0, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('action', 'str',  $action, '', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('page',   'int',  $page, 1, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('cPath',  'int',  $cPath, 0, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('cID',    'int',  $cID, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pID',    'int',  $pID, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('search', 'str',  $search, '', XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('move_to_category_id',    'int',  $move_to_category_id, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('edit_x',    'int',  $edit_x, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('edit_y',    'int',  $edit_y, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('products_tax_class_id',    'int',  $products_tax_class_id, NULL, XARVAR_DONT_SET)) {return;}

if (isset($action)) {
        switch ($action) {
            case 'setflag':
                if(!xarVarFetch('flag',    'int',  $flag, NULL, XARVAR_DONT_SET)) {return;}
                if ( ($flag == 0) || ($flag == 1) ) {
                    if (isset($pID)) {
                        xarModAPIFunc('products','user','set_product_status', array('pID' => $pID,
                                                                                    'status' => $flag));
                    }
                    if (isset($cID)) {
                        xarModAPIFunc('products','user','set_categories_status', array('cID' => $cID,
                                                                                    'status' => $flag));
                    }
                }

                xarResponseRedirect(xarModURL('products','admin','categories', array('cPath' => $cPath)));
                break;
            case 'new_category':
                if ($configuration['allow_category_descriptions'] == true)
                    xarResponseRedirect(xarModURL('products','admin','categories_screen', array('cPath' => $cPath)));
                break;
            case 'edit_category':
                if ($configuration['allow_category_descriptions'] == true)
                    $action =$action . '_ACD';
                break;
            case 'delete_category_confirm':
                if (isset($cID)) {
                    $categories = xarModAPIFunc('products','user','get_category_tree', array(
                                    'parent_id' =>$cID,
                                    'exclude' => 0,
                                    'include_itself' => true));
                    $products = array();
                    $products_delete = array();

                    for ($i = 0, $n = sizeof($categories); $i < $n; $i++) {
                        $q = new xenQuery('SELECT',$xartables['products_products_to_categories'],'products_id');
                        $q->eq('categories_id',$categories[$i]['id']);
                        if(!$q->run()) return;
                        foreach ($q->output() as $product_ids) {
                            $products[$product_ids['products_id']]['categories'][] = $categories[$i]['id'];
                        }
                    }

                    reset($products);
                    while (list($key, $value) = each($products)) {
                        $category_ids = array();
                        for ($i = 0, $n = sizeof($value['categories']); $i < $n; $i++) {
                            $category_ids[] = $value['categories'][$i];
                        }
                        $q = new xenQuery('SELECT',$xartables['products_products_to_categories'],'count(*) AS total');
                        $q->eq('products_id',$key);
                        $q->notin('categories_id',$category_ids);
                        if(!$q->run()) return;
                        $check = $q->row();
                        if ($check['total'] < 1) {
                            $products_delete[$key] = $key;
                        }
                    }

                    // Removing categories can be a lengthy process
                    if (!get_cfg_var('safe_mode')) @set_time_limit(0);
                    for ($i = 0, $n = sizeof($categories); $i < $n; $i++) {
                        xarModAPIFUnc('products','admin','remove_category', array('category_id' => $categories[$i]['id']));
                    }

                    reset($products_delete);
                    while (list($key) = each($products_delete)) {
                        xtc_remove_product($key);
                    }
                }

               xarResponseRedirect(xarModURL('products','admin','categories', array('cPath' => $cPath)));
                break;
            case 'new_product':
                xarResponseRedirect(xarModURL('products','admin','product_screen', array('cPath' => $cPath)));
                break;
            case 'edit_product':
                xarResponseRedirect(xarModURL('products','admin','product_screen', array('cPath' => $cPath, 'pID' => $pID)));
                break;
            case 'delete_product':
                $data['product_categories'] = xarModAPIFunc('products','user','generate_category_path', array(
                                            'id' => $pID,
                                            'from' => 'product'));
                break;
           case 'delete_product_confirm':
                if(!xarVarFetch('product_categories', 'array',  $product_categories, NULL, XARVAR_DONT_SET)) {return;}
                if (isset($pID)) {
                    for ($i = 0, $n = sizeof($product_categories); $i < $n; $i++) {
                        $q = new xenQuery('DELETE',$xartables['products_products_to_categories']);
                        $q->eq('products_id',$pID);
                        $q->eq('categories_id',$product_categories[$i]);
                        $q->qecho();
                        if(!$q->run()) return;
                    }
                    $q = new xenQuery('SELECT',$xartables['products_products_to_categories'],'count(*) AS total');
                    $q->eq('products_id',$pID);
                    if(!$q->run()) return;
                    $product_categories = $q->row();
                    if ($product_categories['total'] == 0) {
                        xarModAPIFunc('products','user','remove_product', array('pID' => $pID));
                    }
                }
                xarResponseRedirect(xarModURL('products','admin','categories', array('cPath' => $cPath)));
                break;
            case 'move_category_confirm':
                if ( ($cID) && ($cID != $move_to_category_id) ) {
                    $q = new xenQuery('UPDATE',$xartables['categories']);
                    $q->addfield('xar_parent',$move_to_category_id);
                    $q->eq('xar_cid',$cID);
                    if(!$q->run()) return;
                }

                xarResponseRedirect(xarModURL('products','admin','categories', array('cPath' => $move_to_category_id, 'cID' => $cID)));
                break;
            case 'move_product_confirm':
                $q = new xenQuery('SELECT',$xartables['products_products_to_categories'],'count(*) AS total');
                $q->eq('products_id',$pID);
                $q->eq('categories_id',$move_to_category_id);
                if(!$q->run()) return;
                $duplicate_check = $q->row();
                if ($duplicate_check['total'] < 1) {
                    $q = new xenQuery('UPDATE',$xartables['products_products_to_categories']);
                    $q->addfield('categories_id',$move_to_category_id);
                    $q->eq('products_id',$pID);
                    $q->eq('categories_id',$cPath);
                    if(!$q->run()) return;
                }

                xarResponseRedirect(xarModURL('products','admin','categories', array('cPath' => $move_to_category_id, 'pID' => $pID)));
                break;
/*            case 'insert_product':
            case 'update_product':
                if(!xarVarFetch('products_price',    'float',  $products_price, NULL, XARVAR_DONT_SET)) {return;}
                if(!xarVarFetch('products_date_available',    'str',  $products_date_available, NULL, XARVAR_DONT_SET)) {return;}

                // START IN-SOLUTION Zurückberechung des Nettopreises falls der Bruttopreis übergeben wurde
                if (PRICE_IS_BRUTTO=='true' && $products_price){
                    $tax_query = new xenQuery("select tax_rate from " . TABLE_TAX_RATES . " where tax_class_id = '".$products_tax_class_id."' ");
                    $q = new xenQuery();
                    if(!$q->run()) return;
                    $tax = $q->output();
                    $products_price = ($products_price/($tax['tax_rate']+100)*100);
                }
                // END IN-SOLUTION



                if ( ($edit_x) || ($edit_y) ) {
                    $action = 'new_product';
                }
                else {
                    $products_id = xtc_db_prepare_input($pID);

                    $products_date_available = (date('Y-m-d') < $products_date_available) ? $products_date_available : 'null';

                    if(!xarVarFetch('products_quantity',    'int',  $products_quantity, NULL, XARVAR_DONT_SET)) {return;}
                    if(!xarVarFetch('products_model',    'str',  $products_model, NULL, XARVAR_DONT_SET)) {return;}
                    if(!xarVarFetch('products_price',    'float',  $products_price, NULL, XARVAR_DONT_SET)) {return;}
                    if(!xarVarFetch('products_discount_allowed',    'int',  $products_discount_allowed, NULL, XARVAR_DONT_SET)) {return;}
                    if(!xarVarFetch('products_weight',    'float',  $products_weight, NULL, XARVAR_DONT_SET)) {return;}
                    if(!xarVarFetch('products_status',    'int',  $products_status, NULL, XARVAR_DONT_SET)) {return;}
                    if(!xarVarFetch('info_template',    'str',  $info_template, NULL, XARVAR_DONT_SET)) {return;}
                    if(!xarVarFetch('options_template',    'str',  $options_template, NULL, XARVAR_DONT_SET)) {return;}
                    if(!xarVarFetch('manufacturers_id',    'int',  $manufacturers_id, NULL, XARVAR_DONT_SET)) {return;}
                    $q->addfield('products_quantity',xtc_db_prepare_input($products_quantity),
                    $q->addfield('products_model',xtc_db_prepare_input($products_model),
                    $q->addfield($q->addfield('products_price',xtc_db_prepare_input($products_price),
                    $q->addfield('products_discount_allowed',xtc_db_prepare_input($products_discount_allowed),
                    $q->addfield('products_date_available',$products_date_available,
                    $q->addfield('products_weight',xtc_db_prepare_input($products_weight),
                    $q->addfield('products_status',xtc_db_prepare_input($products_status),
                    $q->addfield('products_tax_class_id',xtc_db_prepare_input($products_tax_class_id),
                    $q->addfield('product_template',xtc_db_prepare_input($info_template),
                    $q->addfield('options_template',xtc_db_prepare_input($options_template),
                    $q->addfield('manufacturers_id',xtc_db_prepare_input($manufacturers_id));


                    if ($products_image = new upload('products_image', DIR_FS_CATALOG_ORIGINAL_IMAGES, '777', '', true)) {
                        $products_image_name = $products_image->filename;
                        $q->addfield('products_image',xtc_db_prepare_input($products_image_name));

                        require(DIR_WS_INCLUDES . 'product_thumbnail_images.php');
                        require(DIR_WS_INCLUDES . 'product_info_images.php');
                        require(DIR_WS_INCLUDES . 'product_popup_images.php');

                    }
                    else {
                        if(!xarVarFetch('products_previous_image',    'str',  $products_previous_image, NULL, XARVAR_DONT_SET)) {return;}
                        $products_image_name = $_POST['products_previous_image'];
                    }

                    if(!xarVarFetch('products_image',    'str',  $products_image, NULL, XARVAR_DONT_SET)) {return;}
                    if (isset($products_image) && xarModAPIFunc('commerce','user','not_null',array('arg' => $products_image)) && ($products_image != 'none')) {
                        $q->addfield('products_image',xtc_db_prepare_input($products_image));
                    }

                    if ($action == 'insert_product') {
                        $insert_sql_data = array('products_date_added' => 'now()');
                        $sql_data_array = xtc_array_merge($sql_data_array, $insert_sql_data);
                        xtc_db_perform(TABLE_PRODUCTS, $sql_data_array);
                        $products_id = xtc_db_insert_id();
                        new xenQuery("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . $products_id . "', '" . $current_category_id . "')");
                    }
                    elseif ($action == 'update_product') {
                        $update_sql_data = array('products_last_modified' => 'now()');
                        $sql_data_array = xtc_array_merge($sql_data_array, $update_sql_data);
                        xtc_db_perform(TABLE_PRODUCTS, $sql_data_array, 'update', 'products_id = \'' . xtc_db_input($products_id) . '\'');
                    }

                    $languages = xtc_get_languages();
                    // Here we go, lets write Group prices into db
                    // start
                    $i = 0;
                    $group_query = new xenQuery("SELECT customers_status_id  FROM " . TABLE_CUSTOMERS_STATUS . " WHERE language_id = '" . $_SESSION['languages_id'] . "' AND customers_status_id != '0'");
                    $q = new xenQuery();
                    if(!$q->run()) return;
                    while ($group_values = $q->output()) {
                    // load data into array
                    $i++;
                    $group_data[$i] = array('STATUS_ID' => $group_values['customers_status_id']);
                    }
                    for ($col = 0, $n = sizeof($group_data); $col < $n+1; $col++) {
                    if ($group_data[$col]['STATUS_ID'] != '') {
                    $personal_price = xtc_db_prepare_input($_POST['products_price_' . $group_data[$col]['STATUS_ID']]);
                    if ($personal_price == '' or $personal_price=='0.0000') {
                    $personal_price = '0.00';
                    } else {
                    if (PRICE_IS_BRUTTO=='true'){
                    $tax_query = new xenQuery("select tax_rate from " . TABLE_TAX_RATES . " where tax_class_id = '" . $products_tax_class_id . "' ");
                    $q = new xenQuery();
                    if(!$q->run()) return;
                    $tax = $q->output();
                    $personal_price= ($personal_price/($tax['tax_rate']+100)*100);
                    }
                    $personal_price=xtc_round($personal_price,PRICE_PRECISION);
                    }

                    new xenQuery("UPDATE personal_offers_by_customers_status_" . $group_data[$col]['STATUS_ID'] . " SET personal_offer = '" . $personal_price . "' WHERE products_id = '" . $products_id . "' AND quantity = '1'");
                    }
                }
                // end
                // ok, lets check write new staffelpreis into db (if there is one)
                $i = 0;
                $group_query = new xenQuery("SELECT customers_status_id FROM " . TABLE_CUSTOMERS_STATUS . " WHERE language_id = '" . $_SESSION['languages_id'] . "' AND customers_status_id != '0'");
                $q = new xenQuery();
                if(!$q->run()) return;
                while ($group_values = $q->output()) {
                    // load data into array
                    $i++;
                    $group_data[$i]=array('STATUS_ID' => $group_values['customers_status_id']);
                }
                for ($col = 0, $n = sizeof($group_data); $col < $n+1; $col++) {
                    if ($group_data[$col]['STATUS_ID'] != '') {
                        $quantity = xtc_db_prepare_input($_POST['products_quantity_staffel_' . $group_data[$col]['STATUS_ID']]);
                        $staffelpreis = xtc_db_prepare_input($_POST['products_price_staffel_' . $group_data[$col]['STATUS_ID']]);
                        if ($configuration['price_is_brutto'] == true){
                            $tax_query = new xenQuery("select tax_rate from " . TABLE_TAX_RATES . " where tax_class_id = '" . $_POST['products_tax_class_id'] . "' ");
                            $q = new xenQuery();
                            if(!$q->run()) return;
                            $tax = $q->output();
                            $staffelpreis= ($staffelpreis/($tax['tax_rate']+100)*100);
                        }
                        $staffelpreis=xtc_round($staffelpreis,PRICE_PRECISION);
                        if ($staffelpreis!='' && $quantity!='') {
                            new xenQuery("INSERT INTO personal_offers_by_customers_status_" . $group_data[$col]['STATUS_ID'] . " (price_id, products_id, quantity, personal_offer) VALUES ('', '" . $products_id . "', '" . $quantity . "', '" . $staffelpreis . "')");
                        }
                    }
                }
                for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                    $language_id = $languages[$i]['id'];
                    $q->addfield('products_name',xtc_db_prepare_input($_POST['products_name'][$language_id]));
                    $q->addfield('products_description',xtc_db_prepare_input($_POST['products_description_'.$language_id]));
                    $q->addfield('products_short_description',xtc_db_prepare_input($_POST['products_short_description_'.$language_id]));
                    $q->addfield('products_url',xtc_db_prepare_input($_POST['products_url'][$language_id]));
                    $q->addfield('products_meta_title',xtc_db_prepare_input($_POST['products_meta_title'][$language_id]));
                    $q->addfield('products_meta_description',xtc_db_prepare_input($_POST['products_meta_description'][$language_id]));
                    $q->addfield('products_meta_keywords',xtc_db_prepare_input($_POST['products_meta_keywords'][$language_id]));

                    if ($action == 'insert_product') {
                    $insert_sql_data = array('products_id' => $products_id,
                       'language_id' => $language_id);
                    $sql_data_array = xtc_array_merge($sql_data_array, $insert_sql_data);

                    xtc_db_perform(TABLE_PRODUCTS_DESCRIPTION, $sql_data_array);
                    }
                    elseif ($action == 'update_product') {
                        xtc_db_perform(TABLE_PRODUCTS_DESCRIPTION, $sql_data_array, 'update', 'products_id = \'' . xtc_db_input($products_id) . '\' and language_id = \'' . $language_id . '\'');
                    }
                }

                xarResponseRedirect(xarModURL('products','admin','categories', array('cPath' => $cPath, 'pID' => $products_id)));
                }
                break;
*/             case 'copy_to_confirm':
                if(!xarVarFetch('copy_as',    'str',  $copy_as, NULL, XARVAR_DONT_SET)) {return;}
                if(!xarVarFetch('categories_id','int',  $categories_id, NULL, XARVAR_DONT_SET)) {return;}
                if (isset($pID) && isset($categories_id)) {
                    if ($copy_as == 'link') {
                        if ($categories_id != $cPath) {
                            $q = new xenQuery('SELECT',$xartables['products_products_to_categories'],'count(*) AS total');
                            $q->eq('products_id',$pID);
                            $q->eq('categories_id',$categories_id);
                            if(!$q->run()) return;
                            $check = $q->row();
                            if ($check['total'] < 1) {
                                $q = new xenQuery('INSERT',$xartables['products_products_to_categories']);
                                $q->addfield('categories_id',$categories_id);
                                $q->addfield('products_id',$pID);
                                if(!$q->run()) return;
                            }
                        }
                        else {
//                            $messageStack->add_session(xarML('Error: Can not link products in the same directory'), 'error');
                        }
                    }
                    elseif ($copy_as == 'duplicate') {
                        $q = new xenQuery('SELECT',$xartables['products_products']);
                        $q->addfields(array('products_quantity',
                                            'products_model',
                                            'products_image',
                                            'products_price',
                                            'products_discount_allowed',
                                            'products_date_available',
                                            'products_date_added',
                                            'products_weight',
                                            'products_status',
                                            'products_tax_class_id',
                                            'manufacturers_id'));
                        $q->eq('products_id',$pID);
                        if(!$q->run()) return;
                        $product = $q->row();

                        $q = new xenQuery('INSERT',$xartables['products_products']);
                        $q->addfield('products_quantity',$product['products_quantity']);
                        $q->addfield('products_model',$product['products_model']);
                        $q->addfield('products_image',$product['products_image']);
                        $q->addfield('products_price',$product['products_price']);
                        $q->addfield('products_discount_allowed',$product['products_discount_allowed']);
                        $q->addfield('products_date_added',mktime());
                        $q->addfield('products_date_available',$product['products_date_available']);
                        $q->addfield('products_weight',$product['products_weight']);
                        $q->addfield('products_status',0);
                        $q->addfield('products_tax_class_id',$product['products_tax_class_id']);
                        $q->addfield('manufacturers_id',$product['manufacturers_id']);
                        if(!$q->run()) return;

                        $dup_products_id = $q->lastid($xartables['products_products'],'products_id');

                        $q = new xenQuery('SELECT',$xartables['products_products_description']);
                        $q->addfields(array('language_id',
                                            'products_name',
                                            'products_description',
                                            'products_short_description',
                                            'products_meta_title',
                                            'products_meta_description',
                                            'products_meta_keywords',
                                            'products_url'));
                        $q->eq('products_id',$pID);
                        if(!$q->run()) return;
                        foreach ($q->output() as $description) {
                            $q = new xenQuery('INSERT',$xartables['products_products_description']);
                            $q->addfield('products_id',$dup_products_id);
                            $q->addfield('language_id',$description['language_id']);
                            $q->addfield('products_name',$description['products_name']);
                            $q->addfield('products_description',$description['products_description']);
                            $q->addfield('products_short_description',$description['products_short_description']);
                            $q->addfield('products_meta_title',$description['products_meta_title']);
                            $q->addfield('products_meta_description',$description['products_meta_description']);
                            $q->addfield('products_meta_keywords',$description['products_meta_keywords']);
                            $q->addfield('products_url',$description['products_url']);
                            $q->addfield('products_viewed',0);
                            if(!$q->run()) return;
                        }
                        $q = new xenQuery('INSERT',$xartables['products_products_to_categories']);
                        $q->addfield('products_id',$dup_products_id);
                        $q->addfield('categories_id',$categories_id);
                        if(!$q->run()) return;
                    }
                }
                xarResponseRedirect(xarModURL('products','admin','categories', array('cPath' => $categories_id, 'pID' => $dup_products_id)));
                break;
        }
    }


    $categories_count = 0;
    $rows = 0;

    $languages = xarModAPIFunc('commerce','user','get_languages');
    $localeinfo = xarLocaleGetInfo(xarMLSGetSiteLocale());
    $data['language'] = $localeinfo['lang'] . "_" . $localeinfo['country'];
    $currentlang = xarModAPIFunc('commerce','user','get_language',array('locale' => $data['language']));

    $q = new xenQuery('SELECT');
    $q->addtable($xartables['products_categories_description'],'cd');
    $q->addtable($xartables['products_categories'],'c');
    $q->addtable($xartables['categories'],'xc');
    $q->addfields(array('xc.xar_cid AS categories_id',
                        'cd.categories_name',
                        'c.categories_image',
                        'xc.xar_parent',
                        'c.sort_order',
                        'c.date_added',
                        'c.last_modified ',
                        'c.categories_status'));
    $q->join('c.categories_id','cd.categories_id');
    $q->join('c.categories_id','xc.xar_cid');
    $q->eq('cd.language_id',$currentlang['id']);
    $q->setorder('c.sort_order');
    $q->addorder('cd.categories_name');
    if (!empty($search)) {
        $q->like('cd.categories_name','%' . $search . '%');
    }
    else {
        $q->eq('xc.xar_parent',$cPath);
    }

//    $q->qecho();
    if(!$q->run()) return;
    $pager = new splitPageResults($page,
                                  $q->getrows(),
                                  xarModURL('commerce','admin','customers'),
                                  xarModGetVar('commerce', 'itemsperpage')
                                 );
    $data['pagermsg'] = $pager->display_count('Displaying #(1) to #(2) (of #(3) customers)');
    $data['displaylinks'] = $pager->display_links();

    $rows = $categories_count = 0;
    $items =$q->output();
    $limit = count($items);
    for ($i=0;$i<$limit;$i++) {
        $categories_count++;
        $rows++;
        if ((!isset($cID) && !isset($pID)|| $cID == $items[$i]['categories_id']) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
            $category_childs = array('childs_count' => xarModAPIFunc('products', 'user', 'childs_in_category_count', array('categories_id' => $items[$i]['categories_id'])));
            $category_products = array('products_count' => xarModAPIFunc('products', 'user', 'products_in_category_count', array('categories_id' => $items[$i]['categories_id'])));
            $cInfo_array = array_merge($items[$i], $category_childs, $category_products);
            $cInfo = new objectInfo($cInfo_array);
            $items[$i]['url'] = xarModURL('products','admin','categories',array('page' => $page,'cID' => $cInfo->categories_id, 'action' => 'edit'));
        }
        else {
            $items[$i]['url'] = xarModURL('products','admin','categories',array('page' => $page, 'cID' => $items[$i]['categories_id']));
        }
    }
    $data['categories_count'] = $limit;

    $q = new xenQuery('SELECT');
    $q->addtable($xartables['products_products_description'],'pd');
    $q->addtable($xartables['products_products'],'p');
    $q->addtable($xartables['products_products_to_categories'],'p2c');
    $q->addfields(array('p.products_tax_class_id',
                        'p.products_id',
                        'pd.products_name',
                        'p.products_quantity AS quantity',
                        'p.products_image',
                        'p.products_price',
                        'p.products_discount_allowed',
                        'p.products_date_added',
                        'p.products_last_modified',
                        'p.products_date_available',
                        'p.products_status',
                        'p2c.categories_id'));
    $q->join('p.products_id','pd.products_id');
    $q->join('p.products_id','p2c.products_id');
    $q->eq('pd.language_id',$currentlang['id']);
//    $q->setorder('pd.sort_order');
    $q->addorder('pd.products_name');
    if ($search != '') {
        $q->like('pd.products_name','%" . $search . "%');
    }
    else {
        $q->eq('p2c.categories_id',$cPath);
    }
    if(!$q->run()) return;

    $items1 = $q->output();

    $limit = count($items1);
    for ($i=0;$i<$limit;$i++) {
        $rows++;
        // Get categories_id for product if search
        if (!empty($search)) $cPath=$items1['categories_id'];

        if ((!isset($cID) && !isset($pID)|| $pID == $items1[$i]['products_id']) && !isset($pInfo) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
            // find out the rating average from customer reviews
            $q = new xenQuery('SELECT',$xartables['commerce_reviews'],array('avg(reviews_rating) / 5 * 100 as average_rating'));
            $q->eq('products_id',$items1[$i]['products_id']);
            if(!$q->run()) return;
            $row = $q->row();
            $items1[$i]['average_rating'] = $row['average_rating'];
            $pInfo = new objectInfo($items1[$i]);

            $items1[$i]['url'] = xarModURL('products','admin','categories',array('page' => $page,'pID' => $pInfo->products_id, 'action' => 'edit'));
        }
        else {
            $items1[$i]['url'] = xarModURL('products','admin','categories',array('page' => $page, 'pID' => $items1[$i]['products_id']));
        }
        $items1[$i]['check_stock'] = xarModAPIFunc('products','user','get_products_stock', array('products_id' => $items1[$i]['products_id'])) - $items1[$i]['quantity'];
    }
    $data['products_count'] = $limit;

//    <div id="spiffycalendar" class="text"></div>

    if (isset($cPath_array)) {
      $cPath_back = '';
      for($i = 0, $n = sizeof($cPath_array) - 1; $i < $n; $i++) {
        if ($cPath_back == '') {
          $cPath_back .= $cPath_array[$i];
        } else {
          $cPath_back .= '_' . $cPath_array[$i];
        }
      }
    }
    $cPath_back = (isset($cPath_back)) ? 'cPath=' . $cPath_back : '';

    $data['dropdown'] = xarModAPIFunc('commerce','user','draw_pull_down_menu',array(
                                        'name' =>'cPath',
                                        'values' => xarModAPIFunc('products','user','get_category_tree'),
                                        'default' => $current_category_id,
                                        'parameters' => 'onChange="this.form.submit();"')
                            );
    $data['items'] = $items;
    $data['items1'] = $items1;
    $data['rows'] = $rows;
    $data['cInfo'] = isset($cInfo) ? get_object_vars($cInfo) : '';
    $data['pInfo'] = isset($pInfo) ? get_object_vars($pInfo) : '';
    $data['page'] = $page;
    $data['action'] = $action;
    $data['search'] = $search;
    $data['cPath'] = $cPath;
    $data['cPath_back'] = $cPath_back;
    $data['current_category_id'] = $current_category_id;

    //----- new_category / edit_category (when ALLOW_CATEGORY_DESCRIPTIONS is 'true') -----
    if ($action == 'edit_category_ACD') {
        xarResponseRedirect(xarModURL('products','admin','categories_screen', array(
            'cPath' => $cPath,
            'cID' => $cID)));
    //----- new_category_preview (active when ALLOW_CATEGORY_DESCRIPTIONS is 'true') -----
    }
    elseif ($action == 'new_category_preview') {
    // removed
    }
    elseif ($action == 'new_product') {
        xarResponseRedirect(xarModURL('products','admin','product_screen', array('cPath' => $data['cPath'])));
    }
    elseif ($action == 'new_product_preview') {
    // preview removed
    }
    else {
        return xarTplModule('products','admin', 'categories_view',$data);
    }
}
/*    }


        <xar:comment>                     // START IN-SOLUTION Berechung des Bruttopreises</xar:comment>
                $price=$pInfo.products_price;
                $price=xtc_round($price,PRICE_PRECISION);
                $price_string=TEXT_PRODUCTS_PRICE_INFO . ' ' . $currencies->format($price);
                if (PRICE_IS_BRUTTO=='true' && ($_GET['read'] == 'only' || $_GET['action'] != 'new_product_preview') ){
                    $price_netto=xtc_round($price,PRICE_PRECISION);
                    $tax_query = new xenQuery("select tax_rate from " . TABLE_TAX_RATES . " where tax_class_id = '" . $pInfo.products_tax_class_id . "' ");
          $q = new xenQuery();
          if(!$q->run()) return;
                    $tax = $q->output();
                    $price= ($price*($tax[tax_rate]+100)/100);

                    $price_string=TEXT_PRODUCTS_PRICE_INFO . ' ' . $currencies->format($price) . ' - ' . TXT_NETTO . $currencies->format($price_netto);

              }


                <br />
                #$price_string#
                <br />
                <xar:mlstring>Max. allowed Discount</xar:mlstring>:
                #$pInfo.products_discount_allowed#
                <br />
                <xar:mlstring>Quantity</xar:mlstring>:
                #$pInfo.products_quantity#
    <xar:comment> END IN-SOLUTION</xar:comment>
*/
?>