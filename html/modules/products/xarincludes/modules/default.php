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

//$default_smarty = new smarty;
//$default_smarty->assign('tpl_path','templates/'.CURRENT_TEMPLATE.'/');
//$default_smarty->assign('session',session_id());
//$main_content = '';
  // include needed functions
//  require_once(DIR_FS_INC . 'xtc_customer_greeting.inc.php');
//  require_once(DIR_FS_INC . 'xtc_get_path.inc.php');

    include_once 'modules/xen/xarclasses/xenquery.php';
    $xartables = xarDBGetTables();

    if(!xarVarFetch('manufacturers_id',   'int',  $manufacturers_id, 0, XARVAR_DONT_SET)) {return;}

    //Force this
    $category_depth = 'top';

    if ($category_depth == 'nested') {
        $category_query = new xenQuery("select cd.categories_description,cd.categories_name, c.categories_image from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.categories_id = '" . $current_category_id . "' and cd.categories_id = '" . $current_category_id . "' and cd.language_id = '" . $_SESSION['languages_id'] . "'");
        $q = new xenQuery();
        if(!$q->run()) return;
        $category = $q->output();


        if (isset($cPath) && ereg('_', $cPath)) {
        // check to see if there are deeper categories within the current category
        $category_links = array_reverse($cPath_array);
        for($i = 0, $n = sizeof($category_links); $i < $n; $i++) {
        $categories_query = new xenQuery("select c.categories_id, cd.categories_name, c.categories_image, c.parent_id from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.categories_status = '1' and c.parent_id = '" . $category_links[$i] . "' and c.categories_id = cd.categories_id and cd.language_id = '" . $_SESSION['languages_id'] . "' order by sort_order, cd.categories_name");
        if ($categories_query->getrows() < 1) {
        // do nothing, go through the loop
        } else {
        break; // we've found the deepest category the customer is in
        }
        }
        } else {
        $categories_query = new xenQuery("select c.categories_id, cd.categories_name, c.categories_image, c.parent_id from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.categories_status = '1' and c.parent_id = '" . $current_category_id . "' and c.categories_id = cd.categories_id and cd.language_id = '" . $_SESSION['languages_id'] . "' order by sort_order, cd.categories_name");
        }

        $rows = 0;
        $q = new xenQuery();
        if(!$q->run()) return;
        while ($categories = $q->output()) {
        $rows++;
        $cPath_new = xtc_get_path($categories['categories_id']);
        $width = (int)(100 / MAX_DISPLAY_CATEGORIES_PER_ROW) . '%';
        $image='';
        if ($categories['categories_image']!='') {
        $image=DIR_WS_IMAGES.$categories['categories_image'];
        }
        $categories_content[]=array(
        'CATEGORIES_NAME' => $categories['categories_name'],
        'CATEGORIES_IMAGE' => $image,
        'CATEGORIES_LINK' => xarModURL('commerce','user','default', $cPath_new),
        'CATEGORIES_DESCRIPTION' => $categories['categories_description']);


        }
        $new_products_category_id = $current_category_id;
        include(DIR_WS_MODULES . FILENAME_NEW_PRODUCTS);

        $image='';
        if ($category['categories_image']!='') {
        $image=DIR_WS_IMAGES.$category['categories_image'];
        }
        $default_smarty->assign('CATEGORIES_NAME',$category['categories_name']);
        $default_smarty->assign('CATEGORIES_IMAGE',$image);
        $default_smarty->assign('CATEGORIES_DESCRIPTION',$category['categories_description']);

        $default_smarty->assign('language', $_SESSION['language']);
        $default_smarty->assign('module_content',$categories_content);



        $default_smarty->caching = 0;
        $main_content= $default_smarty->fetch(CURRENT_TEMPLATE.'/module/categorie_listing/categorie_listing.html');
        $smarty->assign('main_content',$main_content);

    }
    elseif ($category_depth == 'products' || $manufacturers_id) {
        // show the products of a specified manufacturer
        if ($manufacturers_id) {
          if (isset($_GET['filter_id']) && xarModAPIFunc('commerce','user','not_null',array('arg' => $_GET['filter_id']))) {
            // We are asked to show only a specific category
            $listing_sql = "select p.products_model, pd.products_name, m.manufacturers_name, p.products_quantity, p.products_image, p.products_weight, pd.products_short_description, pd.products_description, p.products_id, p.manufacturers_id, p.products_price, p.products_discount_allowed, p.products_tax_class_id, IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, IF(s.status, s.specials_new_products_price, p.products_price) as final_price from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_MANUFACTURERS . " m, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id where p.products_status = '1' and p.manufacturers_id = m.manufacturers_id and m.manufacturers_id = '" . $_GET['manufacturers_id'] . "' and p.products_id = p2c.products_id and pd.products_id = p2c.products_id and pd.language_id = '" . $_SESSION['languages_id'] . "' and p2c.categories_id = '" . $_GET['filter_id'] . "'";
          } else {
            // We show them all
            $listing_sql = "select p.products_model, pd.products_name, m.manufacturers_name, p.products_quantity, p.products_image, p.products_weight, pd.products_short_description, pd.products_description, p.products_id, p.manufacturers_id, p.products_price, p.products_discount_allowed, p.products_tax_class_id, IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, IF(s.status, s.specials_new_products_price, p.products_price) as final_price from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_MANUFACTURERS . " m left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id where p.products_status = '1' and pd.products_id = p.products_id and pd.language_id = '" . $_SESSION['languages_id'] . "' and p.manufacturers_id = m.manufacturers_id and m.manufacturers_id = '" . $_GET['manufacturers_id'] . "'";
          }
        } else {
          // show the products in a given categorie
          if (isset($_GET['filter_id']) && xarModAPIFunc('commerce','user','not_null',array('arg' => $_GET['filter_id']))) {
            // We are asked to show only specific catgeory
            $listing_sql = "select p.products_model, pd.products_name, m.manufacturers_name, p.products_quantity, p.products_image, p.products_weight, pd.products_short_description, pd.products_description, p.products_id, p.manufacturers_id, p.products_price, p.products_discount_allowed, p.products_tax_class_id, IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, IF(s.status, s.specials_new_products_price, p.products_price) as final_price from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_MANUFACTURERS . " m, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id where p.products_status = '1' and p.manufacturers_id = m.manufacturers_id and m.manufacturers_id = '" . $_GET['filter_id'] . "' and p.products_id = p2c.products_id and pd.products_id = p2c.products_id and pd.language_id = '" . $_SESSION['languages_id'] . "' and p2c.categories_id = '" . $current_category_id . "'";
          } else {
            // We show them all
            $listing_sql = "select p.products_model, pd.products_name, m.manufacturers_name, p.products_quantity, p.products_image, p.products_weight, pd.products_short_description, pd.products_description, p.products_id, p.manufacturers_id, p.products_price, p.products_discount_allowed, p.products_tax_class_id, IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, IF(s.status, s.specials_new_products_price, p.products_price) as final_price from " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS . " p left join " . TABLE_MANUFACTURERS . " m on p.manufacturers_id = m.manufacturers_id, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id where p.products_status = '1' and p.products_id = p2c.products_id and pd.products_id = p2c.products_id and pd.language_id = '" . $_SESSION['languages_id'] . "' and p2c.categories_id = '" . $current_category_id . "'";
          }
        }

        // optional Product List Filter
        if (PRODUCT_LIST_FILTER > 0) {
          if (isset($_GET['manufacturers_id'])) {
            $filterlist_sql = "select distinct c.categories_id as id, cd.categories_name as name from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c, " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where p.products_status = '1' and p.products_id = p2c.products_id and p2c.categories_id = c.categories_id and p2c.categories_id = cd.categories_id and cd.language_id = '" . $_SESSION['languages_id'] . "' and p.manufacturers_id = '" . $_GET['manufacturers_id'] . "' order by cd.categories_name";
          } else {
            $filterlist_sql = "select distinct m.manufacturers_id as id, m.manufacturers_name as name from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c, " . TABLE_MANUFACTURERS . " m where p.products_status = '1' and p.manufacturers_id = m.manufacturers_id and p.products_id = p2c.products_id and p2c.categories_id = '" . $current_category_id . "' order by m.manufacturers_name";
          }
          $filterlist_query = new xenQuery($filterlist_sql);
          if ($filterlist_query->getrows() > 1) {
            $manufacturer_dropdown= xtc_draw_form('filter', FILENAME_DEFAULT, 'GET') .'&nbsp;';
            if (isset($_GET['manufacturers_id'])) {
              $manufacturer_dropdown.= xtc_draw_hidden_field('manufacturers_id', $_GET['manufacturers_id']);
              $options = array(array('text' => TEXT_ALL_CATEGORIES));
            } else {
              $manufacturer_dropdown.= xtc_draw_hidden_field('cPath', $cPath);
              $options = array(array('text' => TEXT_ALL_MANUFACTURERS));
            }
            $manufacturer_dropdown.= xtc_draw_hidden_field('sort', $_GET['sort']);
          $q = new xenQuery();
          if(!$q->run()) return;
            while ($filterlist = $q->output()) {
              $options[] = array('id' => $filterlist['id'], 'text' => $filterlist['name']);
            }
            $manufacturer_dropdown.= commerce_userapi_draw_pull_down_menu('filter_id', $options, $_GET['filter_id'], 'onchange="this.form.submit()"');
            $manufacturer_dropdown.= '</form>' . "\n";
          }
        }

        // Get the right image for the top-right
        $image = DIR_WS_IMAGES . 'table_background_list.gif';
        if (isset($_GET['manufacturers_id'])) {
          $image = new xenQuery("select manufacturers_image from " . TABLE_MANUFACTURERS . " where manufacturers_id = '" . $_GET['manufacturers_id'] . "'");
          $q = new xenQuery();
          if(!$q->run()) return;
          $image = $q->output();
          $image = $image['manufacturers_image'];
        } elseif ($current_category_id) {
          $image = new xenQuery("select categories_image from " . TABLE_CATEGORIES . " where categories_id = '" . $current_category_id . "'");
          $q = new xenQuery();
          if(!$q->run()) return;
          $image = $q->output();
          $image = $image['categories_image'];
        }

        include(DIR_WS_MODULES . FILENAME_PRODUCT_LISTING);

    }
    else {
        // default page

        $languages = xarModAPIFunc('commerce','user','get_languages');
        $localeinfo = xarLocaleGetInfo(xarMLSGetSiteLocale());
        $language = $localeinfo['lang'] . "_" . $localeinfo['country'];
        $currentlang = xarModAPIFunc('commerce','user','get_language',array('locale' => $language));
        $language_id = $currentlang['id'];

        $q = new xarQuery ('SELECT',$xartables['products_content_manager']);
        $q->addfields(array('content_title',
                    'content_heading',
                    'content_text',
                    'content_file'));
        $q->eq('content_group',5);
        $q->eq('languages_id',$language_id);
//        $q->qecho();exit;
        if(!$q->run()) return;
        $shop_content_data = $q->row();

    $data['title'] = $shop_content_data['content_heading'];
    // TODO: what's this?
    //include(DIR_WS_INCLUDES . FILENAME_CENTER_MODULES);


    $configuration = xarModAPIFunc('commerce','admin','load_configuration');
    if (xarUserIsLoggedIn()) {
    $currentuser = xarModAPIFunc('roles','user','get',array('uid' => xarSessionGetVar('uid')));
        $greeting_string = xarML('Nice to see you again #(1). Would you like to visit our #(2)?',
        '<span class="greetUser">' . $currentuser['name'] . '</span>',
        '<a href="' . xarModURL('commerce','user','products_new') . '"><u>new products</u></a>');
    }
    else {
        $greeting_string = xarML('Welcome #(1). Would you like to #(2)? Or would you like to create an #(3) ?',
        '<span class="greetUser">visitor!</span>',
        '<a href="' . xarModURL('roles','user','login') . '"><u>login</u></a>',
        '<a href="' . xarModURL('roles','user','register') . '"><u>account</u></a>');
    }

    $data['text'] = str_replace('{$greeting}',$greeting_string,$shop_content_data['content_text']);
  }
?>