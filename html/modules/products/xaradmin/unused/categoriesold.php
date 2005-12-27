<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 XT-Commerce
//   Third Party contribution:
//   Enable_Disable_Categories 1.3               Autor: Mikel Williams | mikel@ladykatcostumes.com
//   New Attribute Manager v4b                   Autor: Mike G | mp3man@internetwork.net | http://downloads.ephing.com
//   Category Descriptions (Version: 1.5 MS2)    Original Author:   Brian Lowe <blowe@wpcusrgrp.org> | Editor: Lord Illicious <shaolin-venoms@illicious.net>
//   Customers Status v3.x  (c) 2002-2003 Copyright Elari elari@free.fr | www.unlockgsm.com/dload-osc/ | CVS : http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/elari/?sortby=date#dirlist
//  (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
//  (c) 2002-2003 osCommerce (oscommerce.sql,v 1.83); www.oscommerce.com
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------

function commerce_admin_categories()
{

   Released under the GNU General Public License
   --------------------------------------------------------------*/


  include ('includes/classes/image_manipulator.php');
  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  if ($_GET['function']) {
    switch ($_GET['function']) {
      case 'delete':
        new xenQuery("DELETE FROM personal_offers_by_customers_status_" . $_GET['statusID'] . " WHERE products_id = '" . $_GET['pID'] . "' AND quantity = '" . $_GET['quantity'] . "'");
    break;
    }
    xarRedirectResponse(xarModURL('commerce','admin',(FILENAME_CATEGORIES, 'cPath=' . $_GET['cPath'] . '&action=new_product&pID=' . $_GET['pID']));
  }
  if ($_GET['action']) {
    switch ($_GET['action']) {
      case 'setflag':
        if ( ($_GET['flag'] == '0') || ($_GET['flag'] == '1') ) {
          if ($_GET['pID']) {
            xtc_set_product_status($_GET['pID'], $_GET['flag']);
          }
          if ($_GET['cID']) {
            xtc_set_categories_status($_GET['cID'], $_GET['flag']);
          }
        }

        xarRedirectResponse(xarModURL('commerce','admin',(FILENAME_CATEGORIES, 'cPath=' . $_GET['cPath']));
        break;

      case 'new_category':
      case 'edit_category':
        if (ALLOW_CATEGORY_DESCRIPTIONS == 'true')
        $_GET['action']=$_GET['action'] . '_ACD';
        break;

      case 'insert_category':
      case 'update_category':
        if (($_POST['edit_x']) || ($_POST['edit_y'])) {
          $_GET['action'] = 'edit_category_ACD';
        } else {
        $categories_id = xtc_db_prepare_input($_POST['categories_id']);
        if ($categories_id == '') {
        $categories_id = xtc_db_prepare_input($_GET['cID']);
        }
        $sort_order = xtc_db_prepare_input($_POST['sort_order']);
        $categories_status = xtc_db_prepare_input($_POST['categories_status']);
        $q->addfield('sort_order',$sort_order, 'categories_status' => $categories_status);

        if ($_GET['action'] == 'insert_category') {
          $insert_sql_data = array('parent_id' => $current_category_id,
                                   'date_added' => 'now()');
          $sql_data_array = xtc_array_merge($sql_data_array, $insert_sql_data);
          xtc_db_perform(TABLE_CATEGORIES, $sql_data_array);
          $categories_id = xtc_db_insert_id();
        } elseif ($_GET['action'] == 'update_category') {
          $update_sql_data = array('last_modified' => 'now()');
          $sql_data_array = xtc_array_merge($sql_data_array, $update_sql_data);
          xtc_db_perform(TABLE_CATEGORIES, $sql_data_array, 'update', 'categories_id = \'' . $categories_id . '\'');
        }

        $languages = xtc_get_languages();
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
          $categories_name_array = $_POST['categories_name'];
          $language_id = $languages[$i]['id'];
          $q->addfield('categories_name',xtc_db_prepare_input($categories_name_array[$language_id]));
          if (ALLOW_CATEGORY_DESCRIPTIONS == 'true') {
              $q->addfield('categories_name',xtc_db_prepare_input($_POST['categories_name'][$language_id]));
                                      $q->addfield('categories_heading_title',xtc_db_prepare_input($_POST['categories_heading_title'][$language_id]));
                                      $q->addfield('categories_description',xtc_db_prepare_input($_POST['categories_description'][$language_id]));
                                      $q->addfield('categories_meta_title',xtc_db_prepare_input($_POST['categories_meta_title'][$language_id]));
                                      $q->addfield('categories_meta_description',xtc_db_prepare_input($_POST['categories_meta_description'][$language_id]));
                                      $q->addfield('categories_meta_keywords',xtc_db_prepare_input($_POST['categories_meta_keywords'][$language_id]));
            }

          if ($_GET['action'] == 'insert_category') {
            $insert_sql_data = array('categories_id' => $categories_id,
                                     'language_id' => $languages[$i]['id']);
            $sql_data_array = xtc_array_merge($sql_data_array, $insert_sql_data);
            xtc_db_perform(TABLE_CATEGORIES_DESCRIPTION, $sql_data_array);
          } elseif ($_GET['action'] == 'update_category') {
            xtc_db_perform(TABLE_CATEGORIES_DESCRIPTION, $sql_data_array, 'update', 'categories_id = \'' . $categories_id . '\' and language_id = \'' . $languages[$i]['id'] . '\'');
          }
        }

            if ($categories_image = new upload('categories_image', DIR_FS_CATALOG_IMAGES)) {
            new xenQuery("update " . TABLE_CATEGORIES . " set categories_image = '" . xtc_db_input($categories_image->filename) . "' where categories_id = '" . (int)$categories_id . "'");
            }

          xarRedirectResponse(xarModURL('commerce','admin',(FILENAME_CATEGORIES, 'cPath=' . $_GET['cPath'] . '&cID=' . $categories_id));
        }
        break;


      case 'delete_category_confirm':
        if ($_POST['categories_id']) {
          $categories_id = xtc_db_prepare_input($_POST['categories_id']);

          $categories = xtc_get_category_tree($categories_id, '', '0', '', true);
          $products = array();
          $products_delete = array();

          for ($i = 0, $n = sizeof($categories); $i < $n; $i++) {
            $product_ids_query = new xenQuery("select products_id from " . TABLE_PRODUCTS_TO_CATEGORIES . " where categories_id = '" . $categories[$i]['id'] . "'");
      $q = new xenQuery();
      if(!$q->run()) return;
            while ($product_ids = $q->output()) {
              $products[$product_ids['products_id']]['categories'][] = $categories[$i]['id'];
            }
          }

          reset($products);
          while (list($key, $value) = each($products)) {
            $category_ids = '';
            for ($i = 0, $n = sizeof($value['categories']); $i < $n; $i++) {
              $category_ids .= '\'' . $value['categories'][$i] . '\', ';
            }
            $category_ids = substr($category_ids, 0, -2);

            $check_query = new xenQuery("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . $key . "' and categories_id not in (" . $category_ids . ")");
      $q = new xenQuery();
      if(!$q->run()) return;
            $check = $q->output();
            if ($check['total'] < '1') {
              $products_delete[$key] = $key;
            }
          }

          // Removing categories can be a lengthy process
          @xtc_set_time_limit(0);
          for ($i = 0, $n = sizeof($categories); $i < $n; $i++) {
            xtc_remove_category($categories[$i]['id']);
          }

          reset($products_delete);
          while (list($key) = each($products_delete)) {
            xtc_remove_product($key);
          }
        }

        xarRedirectResponse(xarModURL('commerce','admin',(FILENAME_CATEGORIES, 'cPath=' . $cPath));
        break;
      case 'delete_product_confirm':
        if ( ($_POST['products_id']) && (is_array($_POST['product_categories'])) ) {
          $product_id = xtc_db_prepare_input($_POST['products_id']);
          $product_categories = $_POST['product_categories'];

          for ($i = 0, $n = sizeof($product_categories); $i < $n; $i++) {
            new xenQuery("delete from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . xtc_db_input($product_id) . "' and categories_id = '" . xtc_db_input($product_categories[$i]) . "'");
          }

          $product_categories_query = new xenQuery("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . xtc_db_input($product_id) . "'");
      $q = new xenQuery();
      if(!$q->run()) return;
          $product_categories = $q->output();

          if ($product_categories['total'] == '0') {
            xtc_remove_product($product_id);
          }
        }

        xarRedirectResponse(xarModURL('commerce','admin',(FILENAME_CATEGORIES, 'cPath=' . $cPath));
        break;
      case 'move_category_confirm':
        if ( ($_POST['categories_id']) && ($_POST['categories_id'] != $_POST['move_to_category_id']) ) {
          $categories_id = xtc_db_prepare_input($_POST['categories_id']);
          $new_parent_id = xtc_db_prepare_input($_POST['move_to_category_id']);
          new xenQuery("update " . TABLE_CATEGORIES . " set parent_id = '" . xtc_db_input($new_parent_id) . "', last_modified = now() where categories_id = '" . xtc_db_input($categories_id) . "'");
        }

        xarRedirectResponse(xarModURL('commerce','admin',(FILENAME_CATEGORIES, 'cPath=' . $new_parent_id . '&cID=' . $categories_id));
        break;
      case 'move_product_confirm':
        $products_id = xtc_db_prepare_input($_POST['products_id']);
        $new_parent_id = xtc_db_prepare_input($_POST['move_to_category_id']);

        $duplicate_check_query = new xenQuery("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . xtc_db_input($products_id) . "' and categories_id = '" . xtc_db_input($new_parent_id) . "'");
      $q = new xenQuery();
      if(!$q->run()) return;
        $duplicate_check = $q->output();
        if ($duplicate_check['total'] < 1) new xenQuery("update " . TABLE_PRODUCTS_TO_CATEGORIES . " set categories_id = '" . xtc_db_input($new_parent_id) . "' where products_id = '" . xtc_db_input($products_id) . "' and categories_id = '" . $current_category_id . "'");

        xarRedirectResponse(xarModURL('commerce','admin',(FILENAME_CATEGORIES, 'cPath=' . $new_parent_id . '&pID=' . $products_id));
        break;
      case 'insert_product':
      case 'update_product':

// START IN-SOLUTION Zurückberechung des Nettopreises falls der Bruttopreis übergeben wurde
        if (PRICE_IS_BRUTTO=='true' && $_POST['products_price']){
                $tax_query = new xenQuery("select tax_rate from " . TABLE_TAX_RATES . " where tax_class_id = '".$_POST['products_tax_class_id']."' ");
      $q = new xenQuery();
      if(!$q->run()) return;
                $tax = $q->output();
                $_POST['products_price'] = ($_POST['products_price']/($tax['tax_rate']+100)*100);
         }
        // END IN-SOLUTION



        if ( ($_POST['edit_x']) || ($_POST['edit_y']) ) {
          $_GET['action'] = 'new_product';
        } else {
          $products_id = xtc_db_prepare_input($_GET['pID']);
          $products_date_available = xtc_db_prepare_input($_POST['products_date_available']);

          $products_date_available = (date('Y-m-d') < $products_date_available) ? $products_date_available : 'null';

          $q->addfield('products_quantity',xtc_db_prepare_input($_POST['products_quantity']),
                                  $q->addfield('products_model',xtc_db_prepare_input($_POST['products_model']),
                                  $q->addfield($q->addfield('products_price',xtc_db_prepare_input($_POST['products_price']),
                                  $q->addfield('products_discount_allowed',xtc_db_prepare_input($_POST['products_discount_allowed']),
                                  $q->addfield('products_date_available',$products_date_available,
                                  $q->addfield('products_weight',xtc_db_prepare_input($_POST['products_weight']),
                                  $q->addfield('products_status',xtc_db_prepare_input($_POST['products_status']),
                                  $q->addfield('products_tax_class_id',xtc_db_prepare_input($_POST['products_tax_class_id']),
                                  $q->addfield('product_template',xtc_db_prepare_input($_POST['info_template']),
                                  $q->addfield('options_template',xtc_db_prepare_input($_POST['options_template']),
                                  $q->addfield('manufacturers_id',xtc_db_prepare_input($_POST['manufacturers_id']));


          if ($products_image = new upload('products_image', DIR_FS_CATALOG_ORIGINAL_IMAGES, '777', '', true)) {
          $products_image_name = $products_image->filename;
          $q->addfield('products_image',xtc_db_prepare_input($products_image_name));

   require(DIR_WS_INCLUDES . 'product_thumbnail_images.php');
   require(DIR_WS_INCLUDES . 'product_info_images.php');
   require(DIR_WS_INCLUDES . 'product_popup_images.php');

          } else {
          $products_image_name = $_POST['products_previous_image'];
          }

          if (isset($_POST['products_image']) && xarModAPIFunc('commerce','user','not_null',array('arg' => $_POST['products_image'])) && ($_POST['products_image'] != 'none')) {
            $q->addfield('products_image',xtc_db_prepare_input($_POST['products_image']));
          }

          if ($_GET['action'] == 'insert_product') {
            $insert_sql_data = array('products_date_added' => 'now()');
            $sql_data_array = xtc_array_merge($sql_data_array, $insert_sql_data);
            xtc_db_perform(TABLE_PRODUCTS, $sql_data_array);
            $products_id = xtc_db_insert_id();
            new xenQuery("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . $products_id . "', '" . $current_category_id . "')");
          } elseif ($_GET['action'] == 'update_product') {
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
                $tax_query = new xenQuery("select tax_rate from " . TABLE_TAX_RATES . " where tax_class_id = '" . $_POST['products_tax_class_id'] . "' ");
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
            if (PRICE_IS_BRUTTO=='true'){
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

            if ($_GET['action'] == 'insert_product') {
              $insert_sql_data = array('products_id' => $products_id,
                                       'language_id' => $language_id);
              $sql_data_array = xtc_array_merge($sql_data_array, $insert_sql_data);

              xtc_db_perform(TABLE_PRODUCTS_DESCRIPTION, $sql_data_array);
            } elseif ($_GET['action'] == 'update_product') {
              xtc_db_perform(TABLE_PRODUCTS_DESCRIPTION, $sql_data_array, 'update', 'products_id = \'' . xtc_db_input($products_id) . '\' and language_id = \'' . $language_id . '\'');
            }
          }

          xarRedirectResponse(xarModURL('commerce','admin',(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&pID=' . $products_id));
        }
        break;
      case 'copy_to_confirm':
        if ( (xarModAPIFunc('commerce','user','not_null',array('arg' => $_POST['products_id']))) && (xarModAPIFunc('commerce','user','not_null',array('arg' => $_POST['categories_id']))) ) {
          $products_id = xtc_db_prepare_input($_POST['products_id']);
          $categories_id = xtc_db_prepare_input($_POST['categories_id']);

          if ($_POST['copy_as'] == 'link') {
            if ($_POST['categories_id'] != $current_category_id) {
              $check_query = new xenQuery("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . xtc_db_input($products_id) . "' and categories_id = '" . xtc_db_input($categories_id) . "'");
      $q = new xenQuery();
      if(!$q->run()) return;
              $check = $q->output();
              if ($check['total'] < '1') {
                new xenQuery("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . xtc_db_input($products_id) . "', '" . xtc_db_input($categories_id) . "')");
              }
            } else {
              $messageStack->add_session(ERROR_CANNOT_LINK_TO_SAME_CATEGORY, 'error');
            }
          } elseif ($_POST['copy_as'] == 'duplicate') {
            $product_query = new xenQuery("select products_quantity, products_model, products_image, products_price, products_discount_allowed, products_date_available, products_weight, products_tax_class_id, manufacturers_id from " . TABLE_PRODUCTS . " where products_id = '" . xtc_db_input($products_id) . "'");
      $q = new xenQuery();
      if(!$q->run()) return;
            $product = $q->output();
            new xenQuery("insert into " . TABLE_PRODUCTS . " (products_quantity, products_model,products_image, products_price, products_discount_allowed, products_date_added, products_date_available, products_weight, products_status, products_tax_class_id, manufacturers_id) values ('" . $product['products_quantity'] . "', '" . $product['products_model'] . "', '" . $product['products_image'] . "', '" . $product['products_price'] . "', '" . $product['products_discount_allowed'] . "',  now(), '" . $product['products_date_available'] . "', '" . $product['products_weight'] . "', '0', '" . $product['products_tax_class_id'] . "', '" . $product['manufacturers_id'] . "')");
            $dup_products_id = xtc_db_insert_id();

            $description_query = new xenQuery("select language_id, products_name, products_description,products_short_description, products_meta_title, products_meta_description, products_meta_keywords, products_url from " . TABLE_PRODUCTS_DESCRIPTION . " where products_id = '" . xtc_db_input($products_id) . "'");
      $q = new xenQuery();
      if(!$q->run()) return;
            while ($description = $q->output()) {
              new xenQuery("insert into " . TABLE_PRODUCTS_DESCRIPTION . " (products_id, language_id, products_name, products_description, products_short_description, products_meta_title, products_meta_description, products_meta_keywords, products_url, products_viewed) values ('" . $dup_products_id . "', '" . $description['language_id'] . "', '" . addslashes($description['products_name']) . "', '" . addslashes($description['products_description']) . "','" . addslashes($description['products_short_description']) . "', '" . $description['products_url'] . "', '0')");
            }

            new xenQuery("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . $dup_products_id . "', '" . xtc_db_input($categories_id) . "')");
            $products_id = $dup_products_id;
          }
  }

        xarRedirectResponse(xarModURL('commerce','admin',(FILENAME_CATEGORIES, 'cPath=' . $categories_id . '&pID=' . $products_id));
        break;
    }
  }

  // check if the catalog image directory exists
  if (is_dir(DIR_FS_CATALOG_IMAGES)) {
    if (!is_writeable(DIR_FS_CATALOG_IMAGES)) $messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_NOT_WRITEABLE, 'error');
  } else {
    $messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_DOES_NOT_EXIST, 'error');
  }

<div id="spiffycalendar" class="text"></div>

  //----- new_category / edit_category (when ALLOW_CATEGORY_DESCRIPTIONS is 'true') -----
  if ($_GET['action'] == 'new_category_ACD' || $_GET['action'] == 'edit_category_ACD') {
  include('new_categorie.php');
  //----- new_category_preview (active when ALLOW_CATEGORY_DESCRIPTIONS is 'true') -----
  } elseif ($_GET['action'] == 'new_category_preview') {
  // removed
  } elseif ($_GET['action'] == 'new_product') {
  include('new_product.php');
  } elseif ($_GET['action'] == 'new_product_preview') {
  // preview removed
  } else {
  include('categories_view.php');
  }
}
?>