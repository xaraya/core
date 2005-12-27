<?php
/* --------------------------------------------------------------
   $Id: new_attributes_change.php,v 1.3 2003/12/31 20:13:48 fanta2k Exp $

   XT-Commerce - community made shopping
   http://www.xt-commerce.com

   Copyright (c) 2003 XT-Commerce
   --------------------------------------------------------------
   based on:
   (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
   (c) 2002-2003 osCommerce(new_attributes_change); www.oscommerce.com
   (c) 2003  nextcommerce (new_attributes_change.php,v 1.8 2003/08/14); www.nextcommerce.org

   Released under the GNU General Public License
   --------------------------------------------------------------
   Third Party contributions:
   New Attribute Manager v4b                Autor: Mike G | mp3man@internetwork.net | http://downloads.ephing.com

   Released under the GNU General Public License
   --------------------------------------------------------------*/
   require_once(DIR_FS_INC .'xtc_get_tax_rate.inc.php');
   require_once(DIR_FS_INC .'xtc_get_tax_class_id.inc.php');
   require_once(DIR_FS_INC .'xtc_format_price.inc.php');
  // I found the easiest way to do this is just delete the current attributes & start over =)
  new xenQuery("DELETE FROM products_attributes WHERE products_id = '" . $_POST['current_product_id'] . "'" );

  // Simple, yet effective.. loop through the selected Option Values.. find the proper price & prefix.. insert.. yadda yadda yadda.
  for ($i = 0; $i < sizeof($_POST['optionValues']); $i++) {
    $query = "SELECT * FROM products_options_values_to_products_options where products_options_values_id = '" . $_POST['optionValues'][$i] . "'";
    $result = mysql_query($query) or die(mysql_error());
    $matches = mysql_num_rows($result);
    while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
      $optionsID = $line['products_options_id'];
    }

    $cv_id = $_POST['optionValues'][$i];
    $value_price =  $_POST[$cv_id . '_price'];

    if (PRICE_IS_BRUTTO=='true'){

    $value_price= ($value_price/((xtc_get_tax_rate(xtc_get_tax_class_id($_POST['current_product_id'])))+100)*100);
    }
          $value_price=xtc_round($value_price,PRICE_PRECISION);


    $value_prefix = $_POST[$cv_id . '_prefix'];
    $value_weight_prefix = $_POST[$cv_id . '_weight_prefix'];
    $value_model =  $_POST[$cv_id . '_model'];
    $value_stock =  $_POST[$cv_id . '_stock'];
    $value_weight =  $_POST[$cv_id . '_weight'];

    if ($optionTypeInstalled == '1') {
      $value_type = $_POST[$cv_id . '_type'];
      $value_qty = $_POST[$cv_id . '_qty'];
      $value_order = $_POST[$cv_id . '_order'];
      $value_linked = $_POST[$cv_id . '_linked'];

      new xenQuery("INSERT INTO products_attributes (products_id, options_id, options_values_id, options_values_price, price_prefix, attributes_model, attributes_stock, options_type_id, options_values_qty, attribute_order, collegamento) VALUES ('" . $_POST['current_product_id'] . "', '" . $optionsID . "', '" . $_POST['optionValues'][$i] . "', '" . $value_price . "', '" . $value_model . "', '" . $value_stock . "', '" . $value_prefix . "', '" . $value_type . "', '" . $value_qty . "', '" . $value_order . "', '" . $value_linked . "')") or die(mysql_error());
    } elseif ($optionSortCopyInstalled == '1') {
      $value_sort = $_POST[$cv_id . '_sort'];
      $value_weight = $_POST[$cv_id . '_weight'];
      $value_weight_prefix = $_POST[$cv_id . '_weight_prefix'];

      new xenQuery("INSERT INTO products_attributes (products_id, options_id, options_values_id, options_values_price, price_prefix, products_options_sort_order, products_attributes_weight, products_attributes_weight_prefix) VALUES ('" . $_POST['current_product_id'] . "', '" . $optionsID . "', '" . $_POST['optionValues'][$i] . "', '" . $value_price . "', '" . $value_prefix . "', '" . $value_sort . "', '" . $value_weight . "', '" . $value_weight_prefix . "')") or die(mysql_error());
    } else {
      new xenQuery("INSERT INTO products_attributes (products_id, options_id, options_values_id, options_values_price, price_prefix ,attributes_model, attributes_stock, options_values_weight, weight_prefix) VALUES ('" . $_POST['current_product_id'] . "', '" . $optionsID . "', '" . $_POST['optionValues'][$i] . "', '" . $value_price . "', '" . $value_prefix . "', '" . $value_model . "', '" . $value_stock . "', '" . $value_weight . "', '" . $value_weight_prefix . "')") or die(mysql_error());
    }
  }

  // For text input option type feature by chandra
  if ($optionTypeTextInstalled == '1' && is_array($_POST['optionValuesText'])) {
    for ($i = 0; $i < sizeof($_POST['optionValuesText']); $i++) {
      $value_price =  $_POST[$cv_id . '_price'];
      $value_prefix = $_POST[$cv_id . '_prefix'];
      $value_product_id = $_POST[$cv_id . '_options_id'];

      new xenQuery("INSERT INTO products_attributes (products_id, options_id, options_values_id, options_values_price, price_prefix) VALUES ('" . $_POST['current_product_id'] . "', '" . $value_product_id . "', '" . $optionTypeTextInstalledID . "', '" . $value_price . "', '" . $value_prefix . "')") or die(mysql_error());
    }
  }
?>