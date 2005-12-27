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
//   Third Party contributions:
//   Enable_Disable_Categories 1.3            Autor: Mikel Williams | mikel@ladykatcostumes.com
//   Customers Status v3.x  (c) 2002-2003 Copyright Elari elari@free.fr | www.unlockgsm.com/dload-osc/ | CVS : http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/elari/?sortby=date#dirlist

//   ---------------------------------------------------------------------------------------*/

function commerce_user_default()
{
    $main_content = '';

    xarModAPILoad('categories');
    include_once 'modules/xen/xarclasses/xenquery.php';
    $xartables = xarDBGetTables();
    $configuration = xarModAPIFunc('commerce','admin','load_configuration');
    $localeinfo = xarLocaleGetInfo(xarMLSGetSiteLocale());
    $data['language'] = $localeinfo['lang'] . "_" . $localeinfo['country'];
    $currentlang = xarModAPIFunc('commerce','user','get_language',array('locale' => $data['language']));

    if(!xarVarFetch('category_depth',   'str',  $category_depth, 'nested', XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('manufacturer_id',   'int',  $manufacturers_id, 0, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('filter_id',   'int',  $filter_id, 0, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('cPath',   'str',  $cPath, 0, XARVAR_DONT_SET)) {return;}

    $category_links = array_reverse(explode('_', $cPath));
    $current_category_id = $category_links[0];

    //Check where we are in the category tree
    $category_depth = 'top';
    if (isset($cPath)) {
        $q = new xenQuery('SELECT',$xartables['commerce_products_to_categories']);
        $q->addfield('count(*) AS total');
        $q->eq('categories_id',$current_category_id);
        if(!$q->run()) return;
        $cateqories_products = $q->row();
        if ($cateqories_products['total'] > 0) {
            $category_depth = 'products'; // display products
        }
        else {
            $q = new xenQuery('SELECT',$xartables['categories']);
            $q->addfield('count(*) AS total');
            $q->eq('xar_parent',$current_category_id);
            if(!$q->run()) return;
            $category_parent = $q->row();
            if ($category_parent['total'] > 0) {
                $category_depth = 'nested'; // navigate through the categories
            }
            else {
                $category_depth = 'products'; // category has no products, but display the 'no products' message
            }
        }
    }

    if (xarModAPIFunc('commerce','user','check_categories_status', array('categories_id' =>$current_category_id)) >= 1) {
//        $error = CATEGORIE_NOT_FOUND;
//        include(DIR_WS_MODULES . FILENAME_ERROR_HANDLER);
    } else {
        if ($category_depth == 'nested') {
            $q = new xenQuery('SELECT');
            $q->addtable($xartables['categories'],'xc');
            $q->addtable($xartables['commerce_categories'],'c');
            $q->addtable($xartables['commerce_categories_description'],'cd');
            $q->addfields(array('cd.categories_name AS categories_name',
                                'cd.categories_description AS categories_description',
                                'c.categories_template AS categories_template',
                                'c.categories_image AS categories_image'));
        //    if ($configuration['group_check'] == true) {
        //        $q->like('c.group_ids', "'%c_".$_SESSION['customers_status']['customers_status_id']."_group%'");
        //    }
            $q->eq('c.categories_id', $current_category_id);
            $q->eq('cd.language_id', $currentlang['id']);
            $q->join('xc.xar_cid', 'cd.categories_id');
            $q->join('c.categories_id', 'cd.categories_id');
            if(!$q->run()) return;

            $category = $q->row();

            // Get the subcategories
                for($i = 0, $n = sizeof($category_links); $i < $n; $i++) {
                $q = new xenQuery('SELECT');
                $q->addtable($xartables['categories'],'xc');
                $q->addtable($xartables['commerce_categories'],'c');
                $q->addtable($xartables['commerce_categories_description'],'cd');
                $q->addfields(array('xc.xar_cid AS cid',
                                    'cd.categories_name AS categories_name',
                                    'xc.xar_parent AS parent',
                                    'cd.categories_description AS categories_description',
                                    'c.categories_image AS categories_image'));
            //    if ($configuration['group_check'] == true) {
            //        $q->like('c.group_ids', "'%c_".$_SESSION['customers_status']['customers_status_id']."_group%'");
            //    }
                $q->eq('c.categories_status', 1);
                $q->join('c.categories_id', 'xc.xar_cid');
                $q->join('c.categories_id', 'cd.categories_id');
                $q->eq('xc.xar_parent', $current_category_id);
                $q->eq('xc.xar_parent', $category_links[$i]);
                $q->eq('cd.language_id', $currentlang['id']);
                $q->setorder('c.sort_order');
                $q->addorder('cd.categories_name');
                if(!$q->run()) return;

                if ($q->getrows() < 1) {
                  // do nothing, go through the loop
                } else {
                  break; // we've found the deepest category the customer is in
                }
            }

            $rows = 0;
            $categories_content = array();
            foreach ($q->output() as $categories) {
                $rows++;
//                $cPath_new = xarModAPIFunc('commerce','user','get_path', array('nodeid' => $categories['cid']));
                $cPath_new = $cPath . "_" . $categories['cid'];
                $width = (int)(100 /$configuration['max_display_categories_per_row']) . '%';
                $image='';
                if ($categories['categories_image']!='') {
                    $image=DIR_WS_IMAGES.'categories/'.$categories['categories_image'];
                }
                $categories_content[]=array(
                      'categories_name' => $categories['categories_name'],
                      'categories_image' => $image,
                      'categories_link' => xarModURL('commerce','user','default', array('cPath' => $cPath_new)),
                      'categories_description' => $categories['categories_description']);

            }
            $new_products_category_id = $current_category_id;
            $image='';
//            echo var_dump($category);exit;
            if ($category['categories_image']!='') {
                $image=DIR_WS_IMAGES.'categories/'.$category['categories_image'];
            }
            $data['categories_name'] = $category['categories_name'];
            $data['categories_image'] = $image;
            $data['categories_description'] = $category['categories_description'];

            $data['language'] = $currentlang['id'];
            $data['module_content'] = $categories_content;

            // get default template
            /*
            if ($category['categories_template']=='' || $category['categories_template']=='default') {
                $files=array();
                if ($dir= opendir('modules/commerce/xartemplates/category_listing/')){
                    while  (($file = readdir($dir)) !==false) {
                        if (is_file('modules/commerce/xartemplates/category_listing/' . $file) and ($file !="index.html")){
                            $files[]=array(
                            'id' => $file,
                            'text' => $file);
                        }//if
                    } // while
                    closedir($dir);
                }
                $category['categories_template'] = $files[0]['id'];
            }
            */
                $category['categories_template'] = "category_listing";
//            echo var_dump($data);exit;
            return xarTplModule('commerce','user', $category['categories_template'],$data);

        } elseif ($category_depth == 'products' || $manufacturers_id != 0) {
            //fsk18 lock
            $fsk_lock='';
/*
            if ($_SESSION['customers_status']['customers_fsk18_display']=='0') {
                $fsk_lock=' and p.products_fsk18!=1';
            }
*/
            // show the products of a specified manufacturer
            if ($manufacturers_id != 0) {
                if ($filter_id != 0) {

                // sorting query
                    $q = new xenQuery('SELECT');
                    $q->addtable($xartables['commerce_categories'],'c');
                    $q->addfields(array('products_sorting',
                                        'products_sorting2'));
                    $q->eq('categories_id', $filter_id);
                    if (!$q->run()) return;
                    $sorting_data = $q->row();
                    if (!isset($sorting_data['products_sorting']))
                        $sorting_data['products_sorting']='pd.products_name';
                    $q->addorder($sorting_data['products_sorting']);
                    $q->addorder($sorting_data['products_sorting2']);
                // We are asked to show only a specific category
        //          if ($configuration['group_check'] == 'true') {
        //           $group_check="and p.group_ids LIKE '%c_".$_SESSION['customers_status']['customers_status_id']."_group%'";
        //           }
    //STOPPED HERE
                    $q = new xenQuery('SELECT');
                    $q->addtable($xartables['categories'],'xc');
                    $q->addtable($xartables['commerce_categories'],'c');
                    $q->addtable($xartables['commerce_categories_description'],'cd');
                    $q->addfields(array('xc.xar_cid AS cid',
                                        'cd.categories_name AS categories_name',
                                        'xc.xar_parent AS parent',
                                        'd.categories_image AS categories_image'));
                //    if ($configuration['group_check'] == true) {
                //        $q->like('c.group_ids', "'%c_".$_SESSION['customers_status']['customers_status_id']."_group%'");
                //    }
                    $q->eq('c.categories_status', 1);
                    $q->join('c.categories_id', 'xc.xar_cid');
                    $q->join('c.categories_id', 'cd.categories_id');
                    $q->eq('c.categories_id', $current_category_id);
                    $q->eq('xc.xar_parent', $category_links[$i]);
                    $q->eq('cd.language_id', $currentlang['id']);
                    $q->setorder('c.sort_order');
                    $q->addorder('cd.categories_name');
                    if(!$q->run()) return;

                    $q = new xenQuery('SELECT');
                    $q->setdistinct('DISTINCT');
                    $q->addtable($xartables['commerce_products'],'p');
                    $q->addtable($xartables['commerce_products_description'],'pd');
                    $q->addtable($xartables['commerce_manufacturers'],'m');
                    $q->addtable($xartables['commerce_products_to_categories'],'p2c');
                    $q->addtable($xartables['commerce_specials'],'s');
                    $q->leftjoin('p.products_id', 's.products_id');
                    $q->join('p.manufacturers_id', 'm.manufacturers_id');
                    $q->join('p.products_id', 'pd.products_id');
                    $q->join('pd.products_id', 'p2c.products_id');
                    $q->eq('p.products_status', 1);
                    $q->eq('m.manufacturers_id', $m.manufacturers_id);
                    $q->eq('pd.language_id', $currentlang['id']);
                    $q->eq('p2c.categories_id', $filter_id);
                    $q->addfields(array('p.products_fsk18',
                                       'p.products_shippingtime',
                                       'p.products_model',
                                       'pd.products_name',
                                       'm.manufacturers_name',
                                       'p.products_quantity',
                                       'p.products_image',
                                       'p.products_weight',
                                       'pd.products_short_description',
                                       'pd.products_description',
                                       'p.products_id',
                                       'p.manufacturers_id',
                                       'p.products_price',
                                       'p.products_discount_allowed',
                                       'p.products_tax_class_id'
                                ));
                    //add the sorting here
                                       //".$group_check."
                                       //and pd.language_id = '" . (int)$_SESSION['languages_id'] . "' //".$fsk_lock."
                                       //and p2c.categories_id = '" . (int)$_GET['filter_id'] . "'".$sorting;
                } else {
                // We show them all
        //          if ($configuration['group_check'] == 'true') {
        //          $group_check="and p.group_ids LIKE '%c_".$_SESSION['customers_status']['customers_status_id']."_group%'";
        //          }
                    $q = new xenQuery('SELECT');
                    $q->addtable($xartables['commerce_products'],'p');
                    $q->addtable($xartables['commerce_products_description'],'pd');
                    $q->addtable($xartables['commerce_manufacturers'],'m');
                    $q->addtable($xartables['commerce_specials'],'s');
                    $q->leftjoin('p.products_id', 's.products_id');
                    $q->join('p.manufacturers_id', 'm.manufacturers_id');
                    $q->join('p.products_id', 'pd.products_id');
                    $q->eq('p.products_status', 1);
                    $q->eq('m.manufacturers_id', $m.manufacturers_id);
                    $q->eq('pd.language_id', $currentlang['id']);
                    $q->eq('p2c.categories_id', $filter_id);
                    $q->addfields(array('p.products_fsk18',
                                       'p.products_shippingtime',
                                       'p.products_model',
                                       'pd.products_name',
                                       'm.manufacturers_name',
                                       'p.products_quantity',
                                       'p.products_image',
                                       'p.products_weight',
                                       'pd.products_short_description',
                                       'pd.products_description',
                                       'p.products_id',
                                       'p.manufacturers_id',
                                       'p.products_price',
                                       'p.products_discount_allowed',
                                       'p.products_tax_class_id'
                                ));
                    //add the sorting here
                                       //".$group_check."
                                       //and pd.language_id = '" . (int)$_SESSION['languages_id'] . "' //".$fsk_lock."
                                       //and p2c.categories_id = '" . (int)$_GET['filter_id'] . "'".$sorting;
                }
            } else {
                // show the products in a given category
                if ($filter_id != 0) {

                // sorting query
                    $q = new xenQuery('SELECT');
                    $q->addtable($xartables['commerce_categories'],'c');
                    $q->addfields(array('products_sorting',
                                        'products_sorting2'));
                    $q->eq('categories_id', $current_category_id);
                    if (!$q->run()) return;
                    $sorting_data = $q->row();
                    if (!isset($sorting_data['products_sorting']))
                        $sorting_data['products_sorting']='pd.products_name';
                    $q->addorder($sorting_data['products_sorting']);
                    $q->addorder($sorting_data['products_sorting2']);

                    //          if ($configuration['group_check'] == 'true') {
                    //           $group_check="and p.group_ids LIKE '%c_".$_SESSION['customers_status']['customers_status_id']."_group%'";
                    //           }

                $listing_sql = "select p.products_fsk18,
                                       p.products_shippingtime,
                                       p.products_model,
                                       pd.products_name,
                                       m.manufacturers_name,
                                       p.products_quantity,
                                       p.products_image,
                                       p.products_weight,
                                       pd.products_short_description,
                                       pd.products_description,
                                       p.products_id,
                                       p.manufacturers_id,
                                       p.products_price,
                                       p.products_discount_allowed,
                                       p.products_tax_class_id
                                       from " . TABLE_PRODUCTS . " p, " .
                                       TABLE_PRODUCTS_DESCRIPTION . " pd, " .
                                       TABLE_MANUFACTURERS . " m, " .
                                       TABLE_PRODUCTS_TO_CATEGORIES . " p2c left join " .
                                       TABLE_SPECIALS . " s on p.products_id = s.products_id
                                       where p.products_status = '1'
                                       and p.manufacturers_id = m.manufacturers_id
                                       and m.manufacturers_id = '" . (int)$_GET['filter_id'] . "'
                                       and p.products_id = p2c.products_id
                                       and pd.products_id = p2c.products_id
                                       ".$group_check."
                                       and pd.language_id = '" . (int)$_SESSION['languages_id'] . "' ".$fsk_lock."
                                       and p2c.categories_id = '" . $current_category_id . "'".$sorting;
            } else {

                // sorting query
                    $q = new xenQuery('SELECT');
                    $q->addtable($xartables['commerce_categories'],'c');
                    $q->addfields(array('products_sorting',
                                        'products_sorting2'));
                    $q->eq('categories_id', $current_category_id);
                    if (!$q->run()) return;
                    $sorting_data = $q->row();
                    if (!isset($sorting_data['products_sorting']))
                        $sorting_data['products_sorting']='pd.products_name';
                    $q->addorder($sorting_data['products_sorting']);
                    $q->addorder($sorting_data['products_sorting2']);

                    // We show them all
                    //          if ($configuration['group_check'] == 'true') {
                    //           $group_check="and p.group_ids LIKE '%c_".$_SESSION['customers_status']['customers_status_id']."_group%'";
                    //           }


                    $q = new xenQuery('SELECT');
                    $q->addtable($xartables['commerce_products_description'],'pd');
                    $q->addtable($xartables['commerce_products'],'p');
                    $q->addtable($xartables['commerce_manufacturers'],'m');
                    $q->addtable($xartables['commerce_products_to_categories'],'p2c');
                    $q->addtable($xartables['commerce_specials'],'s');
                    $q->leftjoin('p.manufacturers_id', 'm.manufacturers_id');
                    $q->leftjoin('p.products_id', 's.products_id');
                    $q->join('p.products_id', 'p2c.products_id');
                    $q->join('pd.products_id', 'p2c.products_id');
                    $q->eq('p.products_status', 1);
                    $q->eq('pd.language_id', (int)$currentlang['id']);
                    $q->eq('p2c.categories_id', (int)$current_category_id);
                    $q->addfields(array('p.products_fsk18',
                                       'p.products_shippingtime',
                                       'p.products_model',
                                       'pd.products_name',
                                       'm.manufacturers_name',
                                       'p.products_quantity',
                                       'p.products_image',
                                       'p.products_weight',
                                       'pd.products_short_description',
                                       'pd.products_description',
                                       'p.products_id',
                                       'p.manufacturers_id',
                                       'p.products_price',
                                       'p.products_discount_allowed',
                                       'p.products_tax_class_id'
                                ));
//need to add fsk18 and sorting
                }
            }
            // optional Product List Filter
            if ($configuration['product_list_filter'] > 0) {
                if ($manufacturers_id != 0) {
                    $q = new xenQuery('SELECT DISTINCT');
                    $q->addtable($xartables['commerce_products'],'p');
                    $q->addtable($xartables['commerce_products_to_categories'],'p2c');
                    $q->addtable($xartables['categories'],'xc');
                    $q->addtable($xartables['commerce_categories_description'],'cd');
                    $q->join('p.products_id', 'p2c.products_id');
                    $q->join('p2c.categories_id', 'xc.xar_id');
                    $q->join('p2c.categories_id', 'cd.categories_id');
                    $q->eq('p.products_status', 1);
                    $q->eq('pd.language_id', (int)$currentlang['id']);
                    $q->eq('p.manufacturers_id', $manufacturers_id);
                    $q->setorder('cd.categories_name');
                    $q->addfields(array('xc.xar_cid AS id',
                                       'xc.xar_name AS name',
                                ));
                } else {
                    $q = new xenQuery('SELECT');
                    $q->setdistinct();
                    $q->addtable($xartables['commerce_products'],'p');
                    $q->addtable($xartables['commerce_products_to_categories'],'p2c');
                    $q->addtable($xartables['commerce_manufacturers'],'m');
                    $q->join('p.manufacturers_id', 'm.manufacturers_id');
                    $q->join('p.products_id', 'p2c.products_id');
                    $q->eq('p2c.categories_id', $current_category_id);
                    $q->addfields(array('m.manufacturers_id AS id',
                                       'm.manufacturers_name AS name',
                                ));
                    $q->setorder('m.manufacturers_name');
                }
                if(!$q->run()) return;

                if ($q->getrows() > 1) {
                    $manufacturer_dropdown= xtc_draw_form('filter', FILENAME_DEFAULT, 'GET') .'&nbsp;';
                    if ($manufacturers_id != 0) {
                        $manufacturer_dropdown.= xtc_draw_hidden_field('manufacturers_id', $_GET['manufacturers_id']);
                        $options = array(array('text' => TEXT_ALL_CATEGORIES));
                    } else {
                        $manufacturer_dropdown.= xtc_draw_hidden_field('cPath', $cPath);
                        $options = array(array('text' => TEXT_ALL_MANUFACTURERS));
                    }
                    $manufacturer_dropdown.= xtc_draw_hidden_field('sort', $_GET['sort']);
                    foreach ($q->output() as $filterlist) {
                        $options[] = array('id' => $filterlist['id'], 'text' => $filterlist['name']);
                    }
                    $manufacturer_dropdown.= xtc_draw_pull_down_menu('filter_id', $options, $_GET['filter_id'], 'onchange="this.form.submit()"');
                    $manufacturer_dropdown.= '</form>' . "\n";
                }
            }

            // Get the right image for the top-right
            $image = xarTplGetImage('table_background_list.gif');
            if ($manufacturers_id != 0) {
                $q = new xenQuery('SELECT', $xartables['commerce_manufacturers'], 'manufacturers_image');
                $q->eq('manufacturers_id', $manufacturers_id);
                if(!$q->run()) return;
                $image = $q->row();
                $image = $image['manufacturers_image'];
            }
            elseif ($current_category_id) {
                $q = new xenQuery('SELECT', $xartables['categories'], 'xar_image');
                $q->eq('xar_cid', $current_category_id);
                if(!$q->run()) return;
                $image = $q->row();
                $image = $image['xar_image'];
            }

// include(DIR_WS_MODULES . FILENAME_PRODUCT_LISTING);

        } else {
            // default page
            $q = new xenQuery('SELECT', $xartables['commerce_content_manager']);
            $q->addfields(array('content_title',
                                 'content_heading',
                                 'content_text',
                                 'content_file'
                        ));
            $q->eq('content_group', 5);
            $q->eq('languages_id', $currentlang['id']);
            if(!$q->run()) return;
            $shop_content_data = $q->row();

            $data['title'] = $shop_content_data['content_heading'];
        //     include(DIR_WS_INCLUDES . FILENAME_CENTER_MODULES);

            if ($shop_content_data['content_file'] != '') {
               ob_start();
                          if (strpos($shop_content_data['content_file'],'.txt')) echo '<pre>';
                               include(DIR_FS_CATALOG.'media/content/'.$shop_content_data['content_file']);
                          if (strpos($shop_content_data['content_file'],'.txt')) echo '</pre>';
                          $shop_content_data['content_text']=ob_get_contents();
               ob_end_clean();
            }


            $data['text'] = str_replace('{$greeting}',xtc_customer_greeting(),$shop_content_data['content_text']);
            $data['language'] = $curlang['id'];
        }
    }
}
?>