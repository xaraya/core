<?php
/* -----------------------------------------------------------------------------------------
   $Id: checkout_process.php,v 1.3 2003/12/13 17:16:12 fanta2k Exp $

   XT-Commerce - community made shopping
   http://www.xt-commerce.com

   Copyright (c) 2003 XT-Commerce
   -----------------------------------------------------------------------------------------
   based on:
   (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
   (c) 2002-2003 osCommerce(checkout_process.php,v 1.128 2003/05/28); www.oscommerce.com
   (c) 2003  nextcommerce (checkout_process.php,v 1.30 2003/08/24); www.nextcommerce.org

   Released under the GNU General Public License
    ----------------------------------------------------------------------------------------
   Third Party contribution:
   Customers Status v3.x  (c) 2002-2003 Copyright Elari elari@free.fr | www.unlockgsm.com/dload-osc/ | CVS : http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/elari/?sortby=date#dirlist

   Released under the GNU General Public License
   ---------------------------------------------------------------------------------------*/

  include( 'includes/application_top.php');

  // include needed functions
  require_once(DIR_FS_INC . 'xtc_calculate_tax.inc.php');
  require_once(DIR_WS_CLASSES.'class.phpmailer.php');
  require_once(DIR_FS_INC . 'xtc_php_mail.inc.php');

  // initialize smarty
//  $smarty = new Smarty;

  // if the customer is not logged on, redirect them to the login page
//  if (!isset($_SESSION['customer_id'])) || ($customer_status_value['customers_status'] == DEFAULT_CUSTOMERS_STATUS_ID_GUEST) ) {  //Warum funzt das bloß nicht? Staun!
  if (!isset($_SESSION['customer_id'])) {
    $$_SESSION['navigation']->set_snapshot(array('mode' => 'SSL', 'page' => FILENAME_CHECKOUT_PAYMENT));
    xarRedirectResponse(xarModURL('commerce','user','login', '', 'SSL'));
  }

  if ($_SESSION['customers_status']['customers_status_show_price'] !='1'){
    xarRedirectResponse(xarModURL('commerce','user','default', '', ''));
  }

  if (!isset($_SESSION['sendto'])) {
    xarRedirectResponse(xarModURL('commerce','user',(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
  }

  if ( (xarModAPIFunc('commerce','user','not_null',array('arg' => MODULE_PAYMENT_INSTALLED))) && (!isset($_SESSION['payment'])) ) {
    xarRedirectResponse(xarModURL('commerce','admin',(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
 }

  // avoid hack attempts during the checkout procedure by checking the internal cartID
  if (isset($_SESSION['cart']->cartID) && isset($_SESSION['cartID'])) {
    if ($_SESSION['cart']->cartID != $_SESSION['cartID']) {
      xarRedirectResponse(xarModURL('commerce','user',(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
    }
  }

  //include(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . FILENAME_CHECKOUT_PROCESS);

  // load selected payment module
  require(DIR_WS_CLASSES . 'payment.php');
  $payment_modules = new payment($_SESSION['payment']);

  // load the selected shipping module
  require(DIR_WS_CLASSES . 'shipping.php');
  $shipping_modules = new shipping($_SESSION['shipping']);

  require(DIR_WS_CLASSES . 'order.php');
  $order = new order;

  // load the before_process function from the payment modules
  $payment_modules->before_process();

  require(DIR_WS_CLASSES . 'order_total.php');
  $order_total_modules = new order_total;

  $order_totals = $order_total_modules->process();

  if ($_SESSION['customers_status']['customers_status_ot_discount_flag']==1) {
  $discount=$_SESSION['customers_status']['customers_status_ot_discount'];
  } else {
  $discount='0.00';
  }

  $q->addfield('customers_id',$_SESSION['customer_id']);
                          $q->addfield('customers_name',$order->customer['firstname'] . ' ' . $order->customer['lastname']);
                          $q->addfield('customers_company',$order->customer['company']);
                          $q->addfield('customers_status',$order['status']);
                          $q->addfield('customers_status_name',$_SESSION['customers_status']['customers_status_name']);
                          $q->addfield('customers_status_image',$order['status_image']);
                          $q->addfield('customers_status_discount',$discount);
                          $q->addfield('customers_status',$customer_status_value['customers_status']);
                          $q->addfield('customers_street_address',$order->customer['street_address']);
                          $q->addfield('customers_suburb',$order->customer['suburb']);
                          $q->addfield('customers_city',$order->customer['city']);
                          $q->addfield('customers_postcode',$order->customer['postcode']);
                          $q->addfield('customers_state',$order->customer['state']);
                          $q->addfield('customers_country',$order->customer['country']['title']);
                          $q->addfield('customers_telephone',$order->customer['telephone']);
                          $q->addfield('customers_email_address',$order->customer['email_address']);
                          $q->addfield('customers_address_format_id',$order->customer['format_id']);
                          $q->addfield('delivery_name',$order->delivery['firstname'] . ' ' . $order->delivery['lastname']);
                          $q->addfield('delivery_company',$order->delivery['company']);
                          $q->addfield('delivery_street_address',$order->delivery['street_address']);
                          $q->addfield('delivery_suburb',$order->delivery['suburb']);
                          $q->addfield('delivery_city',$order->delivery['city']);
                          $q->addfield('delivery_postcode',$order->delivery['postcode']);
                          $q->addfield('delivery_state',$order->delivery['state']);
                          $q->addfield('delivery_country',$order->delivery['country']['title']);
                          $q->addfield('delivery_address_format_id',$order->delivery['format_id']);
                          $q->addfield('billing_name',$order->billing['firstname'] . ' ' . $order->billing['lastname']);
                          $q->addfield('billing_company',$order->billing['company']);
                          $q->addfield('billing_street_address',$order->billing['street_address']);
                          $q->addfield('billing_suburb',$order->billing['suburb']);
                          $q->addfield('billing_city',$order->billing['city']);
                          $q->addfield('billing_postcode',$order->billing['postcode']);
                          $q->addfield('billing_state',$order->billing['state']);
                          $q->addfield('billing_country',$order->billing['country']['title']);
                          $q->addfield('billing_address_format_id',$order->billing['format_id']);
                          $q->addfield('payment_method',$order->info['payment_method']);
                          // modifcations for CAO-Faktura Export
                          $q->addfield('payment_class',$order->info['payment_class']);
                          $q->addfield('shipping_method',$order->info['shipping_method']);
                          $q->addfield('shipping_class',$order->info['shipping_class']);
                          // modifications end
                          $q->addfield('cc_type',$order->info['cc_type']);
                          $q->addfield('cc_owner',$order->info['cc_owner']);
                          $q->addfield('cc_number',$order->info['cc_number']);
                          $q->addfield('cc_expires',$order->info['cc_expires']);
                          $q->addfield('date_purchased','now()');
                          $q->addfield('orders_status',$order->info['order_status']);
                          $q->addfield('currency',$order->info['currency']);
                          $q->addfield('currency_value',$order->info['currency_value']);
                          $q->addfield('comments',$order->info['comments']);
  xtc_db_perform(TABLE_ORDERS, $sql_data_array);
  $insert_id = xtc_db_insert_id();
  for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
    $q->addfield('orders_id',$insert_id);
                            $q->addfield('title',$order_totals[$i]['title']);
                            $q->addfield('text',$order_totals[$i]['text']);
                            $q->addfield('value',$order_totals[$i]['value']);
                            $q->addfield('class',$order_totals[$i]['code']);
                            $q->addfield('sort_order',$order_totals[$i]['sort_order']);
    xtc_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
  }

  $customer_notification = (SEND_EMAILS == 'true') ? '1' : '0';
  $q->addfield('orders_id',$insert_id);
                          $q->addfield('orders_status_id',$order->info['order_status']);
                          $q->addfield('date_added','now()');
                          $q->addfield('customer_notified',$customer_notification);
                          $q->addfield('comments',$order->info['comments']);
  xtc_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

  // initialized for the email confirmation
  $products_ordered = '';
  $products_ordered_html = '';
  $subtotal = 0;
  $total_tax = 0;

  for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
    // Stock Update - Joao Correia
    if (STOCK_LIMITED == 'true') {
      if (DOWNLOAD_ENABLED == 'true') {
        $stock_query_raw = "SELECT products_quantity, pad.products_attributes_filename
                            FROM " . TABLE_PRODUCTS . " p
                            LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                             ON p.products_id=pa.products_id
                            LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                             ON pa.products_attributes_id=pad.products_attributes_id
                            WHERE p.products_id = '" . xtc_get_prid($order->products[$i]['id']) . "'";
        // Will work with only one option for downloadable products
        // otherwise, we have to build the query dynamically with a loop
        $products_attributes = $order->products[$i]['attributes'];
        if (is_array($products_attributes)) {
          $stock_query_raw .= " AND pa.options_id = '" . $products_attributes[0]['option_id'] . "' AND pa.options_values_id = '" . $products_attributes[0]['value_id'] . "'";
        }
        $stock_query = new xenQuery($stock_query_raw);
      } else {
        $stock_query = new xenQuery("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . xtc_get_prid($order->products[$i]['id']) . "'");
      }
      if ($stock_query->getrows() > 0) {
      $q = new xenQuery();
      if(!$q->run()) return;
        $stock_values = $q->output();
        // do not decrement quantities if products_attributes_filename exists
        if ((DOWNLOAD_ENABLED != 'true') || (!$stock_values['products_attributes_filename'])) {
          $stock_left = $stock_values['products_quantity'] - $order->products[$i]['qty'];
        } else {
          $stock_left = $stock_values['products_quantity'];
        }
        new xenQuery("update " . TABLE_PRODUCTS . " set products_quantity = '" . $stock_left . "' where products_id = '" . xtc_get_prid($order->products[$i]['id']) . "'");
        if ( ($stock_left < 1) && (STOCK_ALLOW_CHECKOUT == 'false') ) {
          new xenQuery("update " . TABLE_PRODUCTS . " set products_status = '0' where products_id = '" . xtc_get_prid($order->products[$i]['id']) . "'");
        }
      }
    }

    // Update products_ordered (for bestsellers list)
    new xenQuery("update " . TABLE_PRODUCTS . " set products_ordered = products_ordered + " . sprintf('%d', $order->products[$i]['qty']) . " where products_id = '" . xtc_get_prid($order->products[$i]['id']) . "'");

    $q->addfield('orders_id',$insert_id);
                            $q->addfield('products_id',xtc_get_prid($order->products[$i]['id']));
                            $q->addfield('products_model',$order->products[$i]['model']);
                            $q->addfield('products_name',$order->products[$i]['name'],
                            $q->addfield('products_price',$order->products[$i]['price']);
                            $q->addfield('final_price',$order->products[$i]['final_price']);
                            $q->addfield('products_tax',$order->products[$i]['tax']);
                            $q->addfield('products_discount_made',$order->$products[$i]['discount_allowed']);
                            $q->addfield('products_quantity',$order->products[$i]['qty']);
                            $q->addfield('allow_tax',$_SESSION['customers_status']['customers_status_show_price_tax']);

    xtc_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);
    $order_products_id = xtc_db_insert_id();

    //------insert customer choosen option to order--------
    $attributes_exist = '0';
    $products_ordered_attributes = '';
    if (isset($order->products[$i]['attributes'])) {
      $attributes_exist = '1';
      for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
        if (DOWNLOAD_ENABLED == 'true') {
          $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                               from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                               left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                on pa.products_attributes_id=pad.products_attributes_id
                               where pa.products_id = '" . $order->products[$i]['id'] . "'
                                and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                                and pa.options_id = popt.products_options_id
                                and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                                and pa.options_values_id = poval.products_options_values_id
                                and popt.language_id = '" . $_SESSION['languages_id'] . "'
                                and poval.language_id = '" . $_SESSION['languages_id'] . "'";
          $attributes = new xenQuery($attributes_query);
        } else {
          $attributes = new xenQuery("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $_SESSION['languages_id'] . "' and poval.language_id = '" . $_SESSION['languages_id'] . "'");
        }
      $q = new xenQuery();
      if(!$q->run()) return;
        $attributes_values = $q->output();

        $q->addfield('orders_id',$insert_id);
                                $q->addfield('orders_products_id',$order_products_id);
                                $q->addfield('products_options',$attributes_values['products_options_name']);
                                $q->addfield('products_options_values',$attributes_values['products_options_values_name']);
                                $q->addfield('options_values_price',$attributes_values['options_values_price']);
                                $q->addfield('price_prefix',$attributes_values['price_prefix']);
        xtc_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);

        if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && xarModAPIFunc('commerce','user','not_null',array('arg' => $attributes_values['products_attributes_filename']))) {
          $q->addfield('orders_id',$insert_id);
                                  $q->addfield('orders_products_id',$order_products_id);
                                  $q->addfield('orders_products_filename',$attributes_values['products_attributes_filename']);
                                  $q->addfield('download_maxdays',$attributes_values['products_attributes_maxdays']);
                                  $q->addfield('download_count',$attributes_values['products_attributes_maxcount']);
          xtc_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
        }
      }
    }
    //------insert customer choosen option eof ----
    $total_weight += ($order->products[$i]['qty'] * $order->products[$i]['weight']);
    $total_tax += xtc_calculate_tax($total_products_price, $products_tax) * $order->products[$i]['qty'];
    $total_cost += $total_products_price;

  }

// NEW EMAIL configuration !

include('send_order.php');


  // load the after_process function from the payment modules
  $payment_modules->after_process();

  $_SESSION['cart']->reset(true);

  // unregister session variables used during checkout
  unset($_SESSION['sendto']);
  unset($_SESSION['billto']);
  unset($_SESSION['shipping']);
  unset($_SESSION['payment']);
  unset($_SESSION['comments']);
  unset($_SESSION['last_order']);
  $last_order = $insert_id;

  if (!isset($mail_error)) {
      xarRedirectResponse(xarModURL('commerce','user',(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
  }
  else {
      echo $mail_error;
  }

  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>