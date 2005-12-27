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

  include( 'includes/application_top.php');
     // create smarty elements
//  $smarty = new Smarty;
  // include boxes
  require(DIR_WS_INCLUDES.'boxes.php');
  // include needed function
  require_once(DIR_FS_INC . 'xtc_draw_textarea_field.inc.php');
  require_once(DIR_FS_INC . 'xtc_draw_radio_field.inc.php');
  require_once(DIR_FS_INC . 'xtc_draw_selection_field.inc.php');
/*
  if (!isset($_SESSION['customer_id'])) {

    xarRedirectResponse(xarModURL('commerce','user','login', '', 'SSL'));
  }
*/
  $product_query = new xenQuery("select pd.products_name, p.products_image from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = '" . (int)$_GET['products_id'] . "' and pd.products_id = p.products_id and pd.language_id = '" . $_SESSION['languages_id'] . "' and p.products_status = '1'");
  $valid_product = ($product_query->getrows() > 0);

  if (isset($_GET['action']) && $_GET['action'] == 'process') {
    if ($valid_product == true) { // We got to the process but it is an illegal product, don't write
      $customer = new xenQuery("select customers_firstname, customers_lastname from " . TABLE_CUSTOMERS . " where customers_id = '" . $_SESSION['customer_id'] . "'");
      $q = new xenQuery();
      if(!$q->run()) return;
      $customer_values = $q->output();
      $date_now = date('Ymd');
      if ($customer_values['customers_lastname']=='') $customer_values['customers_lastname']=TEXT_GUEST ;
      new xenQuery("insert into " . TABLE_REVIEWS . " (products_id, customers_id, customers_name, reviews_rating, date_added) values ('" . $_GET['products_id'] . "', '" . $_SESSION['customer_id'] . "', '" . addslashes($customer_values['customers_firstname']) . ' ' . addslashes($customer_values['customers_lastname']) . "', '" . $_POST['rating'] . "', now())");
      $insert_id = xtc_db_insert_id();
      new xenQuery("insert into " . TABLE_REVIEWS_DESCRIPTION . " (reviews_id, languages_id, reviews_text) values ('" . $insert_id . "', '" . $_SESSION['languages_id'] . "', '" . $_POST['review'] . "')");
    }

    xarRedirectResponse(xarModURL('commerce','user','product_reviews', $_POST['get_params']));
  }

  // lets retrieve all $HTTP_GET_VARS keys and values..
  $get_params = xtc_get_all_get_params();
  $get_params_back = xtc_get_all_get_params(array('reviews_id')); // for back button
  $get_params = substr($get_params, 0, -1); //remove trailing &
  if (xarModAPIFunc('commerce','user','not_null',array('arg' => $get_params_back))) {
    $get_params_back = substr($get_params_back, 0, -1); //remove trailing &
  } else {
    $get_params_back = $get_params;
  }


  $breadcrumb->add(NAVBAR_TITLE_REVIEWS_WRITE, xarModURL('commerce','user','product_reviews', array('x' => $get_params));

  $customer_info_query = new xenQuery("select customers_firstname, customers_lastname from " . TABLE_CUSTOMERS . " where customers_id = '" . $_SESSION['customer_id'] . "'");
      $q = new xenQuery();
      if(!$q->run()) return;
  $customer_info = $q->output();

 require(DIR_WS_INCLUDES . 'header.php');

  if ($valid_product == false) {
  $data['error'] =ERROR_INVALID_PRODUCT;

  } else {
      $q = new xenQuery();
      if(!$q->run()) return;
    $product_info = $q->output()($product_query);
    $name = $customer_info['customers_firstname'] . ' ' . $customer_info['customers_lastname'];
    if ($name==' ') $customer_info['customers_lastname'] = TEXT_GUEST;
    $data['PRODUCTS_NAME'] = $product_info['products_name'];
    $data['AUTHOR'] = $customer_info['customers_firstname'] . ' ' . $customer_info['customers_lastname'];
    $data['INPUT_TEXT'] = xtc_draw_textarea_field('review', 'soft', 60, 15;
    $data['INPUT_RATING'] = xtc_draw_radio_field('rating', '1') . ' ' . xtc_draw_radio_field('rating', '2') . ' ' . xtc_draw_radio_field('rating', '3') . ' ' . xtc_draw_radio_field('rating', '4') . ' ' . xtc_draw_radio_field('rating', '5');
    $data['BUTTON_BACK'] = '<a href="' . xarModURL('commerce','user','product_reviews', $get_params_back) . '">' .
    xarModAPIFunc('commerce','user','image',array('src' => xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_back.gif'),
        'alt' => IMAGE_BUTTON_BACK);
. '</a>';
    $data['BUTTON_SUBMIT'] = <input type="image" src="#xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_continue.gif')#" border="0" alt=IMAGE_BUTTON_CONTINUE>.

}
  $data['language'] = $_SESSION['language'];

  $smarty->caching = 0;
  return data;
  ?>