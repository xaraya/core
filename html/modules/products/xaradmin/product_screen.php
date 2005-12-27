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

function products_admin_product_screen()
{
    include_once 'modules/commerce/xarclasses/object_info.php';

    if(!xarVarFetch('action', 'str',  $action, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pID',    'int',  $data['pID'],   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('cPath',  'int',  $data['cPath'], 0, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('products_tax_class_id',  'int',  $products_tax_class_id, '', XARVAR_NOT_REQUIRED)) {return;}
    $configuration = xarModAPIFunc('commerce','admin','load_configuration');
    $xartables = xarDBGetTables();

    $languages = xarModAPIFunc('commerce','user','get_languages');
    $localeinfo = xarLocaleGetInfo(xarMLSGetSiteLocale());
    $data['language'] = $localeinfo['lang'] . "_" . $localeinfo['country'];
    $currentlang = xarModAPIFunc('commerce','user','get_language',array('locale' => $data['language']));
    $data['languages'] = $languages;
    $data['currentlang'] = $currentlang;
    if(!xarVarFetch('langid',    'int',  $data['langid'], $currentlang['id'], XARVAR_DONT_SET)) {return;}

    if (isset($action)) {
        $q = new xenQuery();
        switch ($action) {
            case 'insert_product':
            case 'update_product':
                if(!xarVarFetch('products_price',    'float',  $products_price, NULL, XARVAR_DONT_SET)) {return;}
                if(!xarVarFetch('products_date_available',    'str',  $products_date_available, NULL, XARVAR_DONT_SET)) {return;}
                // START IN-SOLUTION Zurückberechung des Nettopreises falls der Bruttopreis übergeben wurde
                if ($configuration['price_is_brutto'] == 'true' && $products_price){
                    $tax_rate = xarModAPIFunc('commerce','user','get_tax_rate', array('class_id' => $products_tax_class_id));
                    $products_price = ($products_price/($tax_rate+100)*100);
                }
                // END IN-SOLUTION

                if ( isset($edit_x) || isset($edit_y) ) {
                    $action = 'new_product';
                }
                else {
                    $products_id = $data['pID'];
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
                    $q->addfield('products_quantity',$products_quantity);
                    $q->addfield('products_model',$products_model);
                    $q->addfield('products_price',$products_price);
                    $q->addfield('products_discount_allowed',$products_discount_allowed);
                    $q->addfield('products_date_available',$products_date_available);
                    $q->addfield('products_weight',$products_weight);
                    $q->addfield('products_status',$products_status);
                    $q->addfield('products_tax_class_id',$products_tax_class_id);
                    $q->addfield('product_template',$info_template);
                    $q->addfield('options_template',$options_template);
                    $q->addfield('manufacturers_id',$manufacturers_id);
/*                    if ($products_image = new upload('products_image', DIR_FS_CATALOG_ORIGINAL_IMAGES, '777', '', true)) {
                        $products_image_name = $products_image->filename;
                        $q->addfield('products_image',xtc_db_prepare_input($products_image_name));

                        require(DIR_WS_INCLUDES . 'product_thumbnail_images.php');
                        require(DIR_WS_INCLUDES . 'product_info_images.php');
                        require(DIR_WS_INCLUDES . 'product_popup_images.php');

                    }
                    else {
*/                        if(!xarVarFetch('products_previous_image',    'str',  $products_previous_image, NULL, XARVAR_DONT_SET)) {return;}
//                    }

                    if(!xarVarFetch('products_image',    'str',  $products_image, NULL, XARVAR_DONT_SET)) {return;}
                    if (isset($products_image) && isset($products_image) && ($products_image != 'none')) {
                        $q->addfield('products_image', $products_image);
                    }

                    $q->addtable($xartables['products_products']);
                    if ($action == 'insert_product') {
                        $q->settype('INSERT');
                        $q->addfield('products_date_added', mktime());
                        if(!$q->run()) return;

                        $q = new xenQuery('INSERT', $xartables['products_products_to_categories']);
                        $lastid = $q->lastid($xartables['products_products'], 'products_id');
                        $q->addfield('products_id', $lastid);
                        $q->addfield('categories_id', $data['cPath']);
                        if(!$q->run()) return;
                    }
                    elseif ($action == 'update_product') {
                        $q->settype('UPDATE');
                        $q->addfield('products_last_modified', mktime());
                        $q->eq('products_id', $data['pID']);
                        if(!$q->run()) return;
                    }
                    // Here we go, lets write Group prices into db
                    // start
                    $q = new xenQuery('SELECT',$xartables['products_customers_status'], 'customers_status_id');
                    $q->eq('language_id', $currentlang['id']);
                    $q->ne('customers_status_id', 0);
                    if(!$q->run()) return;
                    foreach ($q->output() as $group_values) {
                        // load data into array
                        $group_data[] = array('STATUS_ID' => $group_values['customers_status_id']);
                    }
/*                    for ($col = 0, $n = sizeof($group_data); $col < $n+1; $col++) {
                        if ($group_data[$col]['STATUS_ID'] != '') {
                            if(!xarVarFetch('products_price_' . $group_data[$col]['STATUS_ID'],    'str',  $personal_price, '', XARVAR_NOT_REQUIRED)) {return;}
                            if ($personal_price == '' or $personal_price =='0.0000') {
                                $personal_price = '0.00';
                            } else {
                                if ($configuration['price_is_brutto'] == 'true'){
                                    $tax_rate = xarModAPIFunc('commerce','user','get_tax_rate', array('class_id' => $products_tax_class_id));
                                    $personal_price= ($personal_price/($tax_rate+100)*100);
                                }
                                $personal_price=xtc_round($personal_price,PRICE_PRECISION);
                            }
                                    $q = new xenQuery('UPDATE',$xartables['products_personal_offers_by_customers_status_' . $group_data[$col]['STATUS_ID']]);
                                    $q->addfield('personal_offer', $personal_price);
                                    $q->eq('products_id', $products_id);
                                    $q->eq('quantity', 1);
                                    if(!$q->run()) return;
                        }
                    }
                    */
                // end
                // ok, lets check write new staffelpreis into db (if there is one)
                $q = new xenQuery('SELECT',$xartables['products_customers_status'], 'customers_status_id');
                $q->eq('language_id', $currentlang['id']);
                $q->ne('customers_status_id', 0);
                if(!$q->run()) return;
                foreach ($q->output() as $group_values) {
                    // load data into array
                    $group_data[]=array('STATUS_ID' => $group_values['customers_status_id']);
                }
/*                for ($col = 0, $n = sizeof($group_data); $col < $n+1; $col++) {
                    if ($group_data[$col]['STATUS_ID'] != '') {
                        $quantity = xtc_db_prepare_input($_POST['products_quantity_staffel_' . $group_data[$col]['STATUS_ID']]);
                        $staffelpreis = xtc_db_prepare_input($_POST['products_price_staffel_' . $group_data[$col]['STATUS_ID']]);
                        if ($configuration['price_is_brutto'] == true){
                            $tax_rate = xarModAPIFunc('commerce','user','get_tax_rate', array('class_id' => $products_tax_class_id));
                            $staffelpreis= ($staffelpreis/($tax_rate+100)*100);
                        }
                        $staffelpreis=xtc_round($staffelpreis,PRICE_PRECISION);
                        if ($staffelpreis!='' && $quantity!='') {
                            $q = new xenQuery('INSERT',$xartables['products_personal_offers_by_customers_status_'] . $group_data[$col]['STATUS_ID']);
                            $q->addfield('price_id', '');
                            $q->addfield('products_id', $products_id);
                            $q->addfield('quantity', $quantity);
                            $q->addfield('personal_offer', $staffelpreis);
                            if(!$q->run()) return;
                        }
                    }
                }
*/
                $q = new xenQuery();
                $q->addtable($xartables['products_products_description']);
                if(!xarVarFetch('products_name',              'array',  $products_name,   '', XARVAR_NOT_REQUIRED)) {return;}
                if(!xarVarFetch('products_url',               'array',  $products_url,   '', XARVAR_NOT_REQUIRED)) {return;}
                if(!xarVarFetch('products_description',       'array',  $products_description,   '', XARVAR_NOT_REQUIRED)) {return;}
                if(!xarVarFetch('products_short_description', 'array',  $products_short_description,   '', XARVAR_NOT_REQUIRED)) {return;}
                if(!xarVarFetch('products_meta_title',        'array',  $products_meta_title,   '', XARVAR_NOT_REQUIRED)) {return;}
                if(!xarVarFetch('products_meta_description',  'array',  $products_meta_description,   '', XARVAR_NOT_REQUIRED)) {return;}
                if(!xarVarFetch('products_meta_keywords',     'array',  $products_meta_keywords,   '', XARVAR_NOT_REQUIRED)) {return;}
                for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                    $language_id = $languages[$i]['id'];
                    if (isset($products_name[$language_id])) {
                        $q->addfield('products_name',$products_name[$language_id]);
                        $q->addfield('products_description',$products_description[$language_id]);
                        $q->addfield('products_short_description',$products_short_description[$language_id]);
                        $q->addfield('products_url',$products_url[$language_id]);
                        $q->addfield('products_meta_title',$products_meta_title[$language_id]);
                        $q->addfield('products_meta_description',$products_meta_description[$language_id]);
                        $q->addfield('products_meta_keywords',$products_meta_keywords[$language_id]);
                        if ($action == 'insert_product') {
    //                        $q->qecho();exit;
                            $q->settype('INSERT');
                            $q->addfield('products_id', $products_id);
                            $q->addfield('language_id', $language_id);
                            if(!$q->run()) return;
                        }
                        elseif ($action == 'update_product') {
                            $q->settype('UPDATE');
                            $q->addfield('products_id', $products_id);
                            $q->addfield('language_id', $language_id);
//    $q->qecho();exit;
                            if(!$q->run()) return;
                        }
                    }
                }
                xarResponseRedirect(xarModURL('products','admin','categories', array('cPath' => $data['cPath'], 'pID' => $products_id)));
            }
            break;
        }
    }

    if (isset($data['pID'])) {
        $q = new xenQuery('SELECT');
        $q->addtable($xartables['products_products_description'],'pd');
        $q->addtable($xartables['products_products'],'p');
        $q->addfields(array('p.products_fsk18',
                            'p.product_template',
                            'p.options_template',
                            'p.products_id',
                            'p.group_ids',
                            'p.products_sort',
                            'p.products_shippingtime',
                            'p.products_quantity',
                            'p.products_model',
                            'p.products_image',
                            'p.products_price',
                            'p.products_discount_allowed',
                            'p.products_weight',
                            'p.products_date_added',
                            'p.products_date_available',
                            'p.products_last_modified',
                            'p.products_status',
                            'p.products_tax_class_id',
                            "date_format(p.products_date_available, '%Y-%m-%d') as products_date_available",
                            'p.manufacturers_id'));
        $q->addfields(array('pd.products_name',
                            'pd.products_description',
                            'pd.products_short_description',
                            'pd.products_meta_title',
                            'pd.products_meta_description',
                            'pd.products_meta_keywords',
                            'pd.products_url'));

        $q->join('p.products_id','pd.products_id');
        $q->eq('pd.products_id',$data['pID']);
        $q->eq('pd.language_id',$currentlang['id']);
        if(!$q->run()) return;
        $pInfo = new objectInfo($q->row());
    }
        if(!xarVarFetch('manufacturers_id',         'int',  $pInfo->manufacturers_id,   0, XARVAR_NOT_REQUIRED)) {return;}
        if(!xarVarFetch('products_fsk18',           'int',  $pInfo->products_fsk18,   0, XARVAR_NOT_REQUIRED)) {return;}
        if(!xarVarFetch('products_shippingtime',    'str',  $pInfo->products_shippingtime,   '', XARVAR_NOT_REQUIRED)) {return;}
        if(!xarVarFetch('product_template',         'str',  $pInfo->product_template,   '', XARVAR_NOT_REQUIRED)) {return;}
        if(!xarVarFetch('options_template',         'str',  $pInfo->options_template,   '', XARVAR_NOT_REQUIRED)) {return;}
        $data['pInfo'] = $pInfo;

/*    else {
        $pInfo = array();
        foreach($languages as $language) {
            $id = $language['id'];
        }
        if(!xarVarFetch('products_sort',              'str',  $products_sort'][$id],   '', XARVAR_NOT_REQUIRED)) {return;}
        if(!xarVarFetch('products_model',               'str',  $products_model,   '', XARVAR_NOT_REQUIRED)) {return;}
        if(!xarVarFetch('products_weight',               'float',  $products_weight,   '', XARVAR_NOT_REQUIRED)) {return;}
        if(!xarVarFetch('products_image',               'str',  $products_image,   '', XARVAR_NOT_REQUIRED)) {return;}
        if(!xarVarFetch('products_date_available',               'str',  $products_date_available,   '', XARVAR_NOT_REQUIRED)) {return;}
        if(!xarVarFetch('products_quantity',               'float',  $products_quantity,   '', XARVAR_NOT_REQUIRED)) {return;}
        if(!xarVarFetch('products_discount_allowed',         'str',  $products_discount_allowed,   '', XARVAR_NOT_REQUIRED)) {return;}

        $products_name = $products_name'];
        $products_description = $products_description'];
        $products_short_description = $products_short_description'];
        $products_meta_title = $products_meta_title'];
        $products_meta_description = $products_meta_description'];
        $products_meta_keywords = $products_meta_keywords'];
        $products_url = $products_url'];

    }
    */
//        echo var_dump($pInfo);exit;

    if(!xarVarFetch('product_price',         'float',  $product_price,   0, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('product_tax_class_id',         'int',  $product_tax_class_id,   '', XARVAR_NOT_REQUIRED)) {return;}

    $default_array=array();
    // set default value in dropdown!
    if (isset($content['content_file']) && $content['content_file'] == '') {
        $default_array[]=array('id' => 'default','text' => xarML('--Select--'));
    } else {
        $default_array[]=array('id' => 'default','text' => xarML('--No files available--'));
    }
    $data['producttemplatefiles'] = $default_array;
    $dirname = 'modules/products/xartemplates/product_info/';
    if (isset($dirname) && $dir = opendir($dirname)){
        $files = array();
        while  (($file = readdir($dir)) !==false) {
            if (is_file('modules/products/xartemplates/product_info/'.$file) and ($file !="index.html")){
            $files[]=array(
                        'id' => $file,
                        'text' => $file);
            }
        }
        closedir($dir);
        $data['producttemplatefiles'] = array_merge($default_array,$files);
    }

    $default_array=array();
    // set default value in dropdown!
    if (isset($content['content_file']) && $content['content_file'] == '') {
        $default_array[]=array('id' => 'default','text' => xarML('--Select--'));
    } else {
        $default_array[]=array('id' => 'default','text' => xarML('--No files available--'));
    }
    $data['optionstemplatefiles'] = $default_array;
    $dirname = 'modules/products/xartemplates/product_options/';
    if (isset($dirname) && $dir = opendir($dirname)){
        $files = array();
        while  (($file = readdir($dir)) !==false) {
            if (is_file('modules/products/xartemplates/product_options/'.$file) and ($file !="index.html")){
            $files[]=array(
                        'id' => $file,
                        'text' => $file);
            }
        }
        closedir($dir);
        $data['optionstemplatefiles'] = array_merge($default_array,$files);
    }

/*
    $customers_statuses_array = xarModAPIFunc('products','user', 'get_customers_statuses');
    $customers_statuses_array=array_merge(array(array('id'=>'all','text'=> xarML('All'))),$customers_statuses_array);
    $data['customers_statuses_array'] = $customers_statuses_array;
*/
    $q = new xenQuery('SELECT',$xartables['commerce_customers_status']);
    $q->addfields(array('customers_status_image AS status_image',
                                   'customers_status_id AS status_id',
                                   'customers_status_name AS status_name'));
    $q->eq('language_id',$currentlang['id']);
    $q->ne('customers_status_id',0);
    if(!$q->run()) return;
    $data['group_data'] = $q->output();

// calculate brutto price for display

if ($configuration['price_is_brutto']){
    $tax = xarModAPIFunc('commerce','user','get_tax_rate', array('class_id' => $product_tax_class_id));
    $products_price = round($product_price * ((100 + $tax)/100),$configuration['price_precision']);
//    echo "ss".var_dump($pInfo['product_tax_class_id']);exit;
}
else {
    $products_price = round($products_price,$configuration['price_precision']);
}
$data['products_price'] = $products_price;


/*    $manufacturers_array = array(array('id' => '', 'text' => TEXT_NONE));
    $manufacturers_query = xtc_db_query("select manufacturers_id, manufacturers_name from " . TABLE_MANUFACTURERS . " order by manufacturers_name");
    while ($manufacturers = xtc_db_fetch_array($manufacturers_query)) {
      $manufacturers_array[] = array('id' => $manufacturers['manufacturers_id'],
                                     'text' => $manufacturers['manufacturers_name']);
    }
*/
    $tax_class_array = array(array('id' => '0', 'text' => xarML('--none--')));
    $q = new xenQuery('SELECT',$xartables['commerce_tax_class']);
    $q->addfields(array('tax_class_id AS id', 'tax_class_title AS text'));
    $q->setorder('tax_class_title');
    if(!$q->run()) return;
    $data['tax_class_array'] = array_merge($tax_class_array,$q->output());
//    echo var_dump($tax_class_array);exit;

/*    $shipping_statuses = array();
    $shipping_statuses=xtc_get_shipping_status();
    $languages = xtc_get_languages();

    switch ($pInfo->products_status) {
      case '0': $status = false; $out_status = true; break;
      case '1':
      default: $status = true; $out_status = false;
    }
*/
if(!xarVarFetch('action', 'str',  $action, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('cPath',  'int',  $data['cPath'], 0, XARVAR_NOT_REQUIRED)) {return;}


    if (isset($action)) {
        switch ($action) {
            case 'insert_category':
            case 'update_category':
                if(!xarVarFetch('sort_order',    'str',  $sort_order, NULL, XARVAR_DONT_SET)) {return;}
                if(!xarVarFetch('categories_status',    'str',  $categories_status, NULL, XARVAR_DONT_SET)) {return;}
                if(!xarVarFetch('categories_name',    'array',  $categories_name, NULL, XARVAR_DONT_SET)) {return;}

                if (($edit_x) || ($edit_y)) {
                    $action = 'edit_category_ACD';
                }
                else {
                    if ($categories_id == '') {
                        $categories_id = $cID;
                    }
                    $q->addfield('sort_order',$sort_order);
                    $q->addfield('categories_status',$categories_status);

                    $q->addtable('products_categories');
                    if ($action == 'insert_category') {
                        $q->addfield('parent_id',$current_category_id);
                        $q->addfield('date_added',mktime());
//                        $categories_id = xtc_db_insert_id();
                    }
                    elseif ($action == 'update_category') {
                        $q->addfield('last_modified',mktime());
                        $q->eq('categories_id',$categories_id);
                    }

                    if(!xarVarFetch('categories_name',    'array',  $categories_name, NULL, XARVAR_DONT_SET)) {return;}
                    if(!xarVarFetch('categories_heading_title',    'array',  $categories_heading_title, NULL, XARVAR_DONT_SET)) {return;}
                    if(!xarVarFetch('categories_description',    'array',  $categories_description, NULL, XARVAR_DONT_SET)) {return;}
                    if(!xarVarFetch('categories_meta_title',    'array',  $categories_meta_title, NULL, XARVAR_DONT_SET)) {return;}
                    if(!xarVarFetch('categories_meta_description',    'array',  $categories_meta_description, NULL, XARVAR_DONT_SET)) {return;}
                    if(!xarVarFetch('categories_meta_keywords',    'array',  $categories_meta_keywords, NULL, XARVAR_DONT_SET)) {return;}
                    if(!xarVarFetch('categories_name',    'array',  $categories_name, NULL, XARVAR_DONT_SET)) {return;}

                    for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                        $language_id = $languages[$i]['id'];
                        if (isset($categories_name[$language_id])) {
                            $q->addfield('categories_name',$categories_name[$language_id]);
                            if ($configuration['allow_category_descriptions'] == true) {
                                $q->addfield('categories_heading_title',$categories_heading_title[$language_id]);
                                $q->addfield('categories_description',$categories_description[$language_id]);
                                $q->addfield('categories_meta_title',$categories_meta_title[$language_id]);
                                $q->addfield('categories_meta_description',$categories_meta_description[$language_id]);
                                $q->addfield('categories_meta_keywords',$categories_meta_keywords[$language_id]);
                            }
                        }

                        $q1 = new xenQuery('SELECT');
                        if ($action == 'insert_category') {
                            $q1->addfield('categories_id',$categories_id);
                            $q1->addfield('language_id',$language_id);
                        }
                        elseif ($action == 'update_category') {
                            $q1->eq('categories_id',$categories_id);
                            $q1->eq('language_id',$language_id);
                        }
                            $q1->addtable('products_categories_description');
                            $q1->run();
                    }

                    if ($categories_image = new upload('categories_image', DIR_FS_CATALOG_IMAGES)) {
                        $q = new xenQuery('SELECT','products_categories');
                        $q->addfield('categories_image',$categories_image->filename);
                        $q->eq('categories_id',$categories_id);
                        if(!$q->run()) return;
                    }

                }
                xarResponseRedirect(xarModURL('products','admin','categories', array('cPath' => $cPath, 'cID' => $categories_id)));
            }
//            break;
//        }
    }

    $configuration = xarModAPIFunc('commerce','admin','load_configuration');
    $data['configuration'] = $configuration;
//    echo var_dump($configuration);exit;
    return $data;
}
?>