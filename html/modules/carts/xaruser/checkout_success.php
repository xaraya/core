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

   // create smarty elements
//  $smarty = new Smarty;
  // include boxes
  require(DIR_WS_INCLUDES.'boxes.php');
  // include needed functions
  require_once(DIR_FS_INC . 'xtc_draw_checkbox_field.inc.php');
  require_once(DIR_FS_INC . 'xtc_draw_selection_field.inc.php');

  // if the customer is not logged on, redirect them to the shopping cart page
  if (!isset($_SESSION['customer_id'])) {
    xarRedirectResponse(xarModURL('commerce','user','shopping_cart'));
  }

  if (isset($_GET['action']) && ($_GET['action'] == 'update')) {
    $notify_string = 'action=notify&';
    $notify = $_POST['notify'];
    if (!is_array($notify)) $notify = array($notify);
    for ($i=0, $n=sizeof($notify); $i<$n; $i++) {
      $notify_string .= 'notify[]=' . $notify[$i] . '&';
    }
    if (strlen($notify_string) > 0) $notify_string = substr($notify_string, 0, -1);
    if ($_SESSION['account_type']!=1) {
    xarRedirectResponse(xarModURL('commerce','user','default', $notify_string));
    } else {
    xarRedirectResponse(xarModURL('commerce','user',(FILENAME_LOGOFF, $notify_string));
    }

  }

 $breadcrumb->add(NAVBAR_TITLE_1_CHECKOUT_SUCCESS);
  $breadcrumb->add(NAVBAR_TITLE_2_CHECKOUT_SUCCESS);

  $global_query = new xenQuery("select global_product_notifications from " . TABLE_CUSTOMERS_INFO . " where customers_info_id = '" . $_SESSION['customer_id'] . "'");
      $q = new xenQuery();
      if(!$q->run()) return;
  $global = $q->output();

  if ($global['global_product_notifications'] != '1') {
    $orders_query = new xenQuery("select orders_id from " . TABLE_ORDERS . " where customers_id = '" . $_SESSION['customer_id'] . "' order by date_purchased desc limit 1");
      $q = new xenQuery();
      if(!$q->run()) return;
    $orders = $q->output();

    $products_array = array();
    $products_query = new xenQuery("select products_id, products_name from " . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . $orders['orders_id'] . "' order by products_name");
      $q = new xenQuery();
      if(!$q->run()) return;
    while ($products = $q->output()) {
      $products_array[] = array('id' => $products['products_id'],
                                'text' => $products['products_name']);
    }
  }

 require(DIR_WS_INCLUDES . 'header.php');


  if ($global['global_product_notifications'] != '1') {
    $notifications= '<p class="productsNotifications">';

    $products_displayed = array();
    for ($i=0, $n=sizeof($products_array); $i<$n; $i++) {
      if (!in_array($products_array[$i]['id'], $products_displayed)) {
        $notifications.=  xtc_draw_checkbox_field('notify[]', $products_array[$i]['id']) . ' ' . $products_array[$i]['text'] . '<br>';
        $products_displayed[] = $products_array[$i]['id'];
      }
    }

    $notifications.=  '</p>';
  } else {
    $notifications.=  TEXT_SEE_ORDERS . '<br><br>' . TEXT_CONTACT_STORE_OWNER;
  }
 $data['NOTIFICATION_BLOCK'] = $notifications;

 $data['BUTTON_CONTINUE'] = <input type="image" src="#xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_continue.gif')#" border="0" alt=IMAGE_BUTTON_CONTINUE>;
 $data['BUTTON_PRINT'] = '<img src="'$language .'/buttons/button_print.gif" style="cursor:hand" onClick="window.open(\''. xarModURL('commerce','user',(FILENAME_PRINT_ORDER,'oID='.$orders['orders_id']).'\', \'popup\', \'toolbar=0, width=640, height=600\')">';

// if (DOWNLOAD_ENABLED == 'true') include(DIR_WS_MODULES . 'downloads.php');
  $data['language'] = $_SESSION['language'];
  $data['PAYMENT_BLOCK'] = $payment_block;
  $smarty->caching = 0;
  return data;
?>