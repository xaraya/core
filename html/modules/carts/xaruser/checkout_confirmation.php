<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 XT-Commerce
//   Third Party contributions:
//   agree_conditions_1.01            Autor:  Thomas Plänkers (webmaster@oscommerce.at)
//   Customers Status v3.x  (c) 2002-2003 Copyright Elari elari@free.fr | www.unlockgsm.com/dload-osc/ | CVS : http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/elari/?sortby=date#dirlist
//  (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
//  (c) 2002-2003 osCommerce (oscommerce.sql,v 1.83); www.oscommerce.com
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------

  // create smarty elements
//  $smarty = new Smarty;
  // include boxes
  require(DIR_WS_INCLUDES.'boxes.php');
  // include needed functions
  require_once(DIR_FS_INC . 'xtc_calculate_tax.inc.php');
  require_once(DIR_FS_INC . 'xtc_check_stock.inc.php');
  require_once(DIR_FS_INC . 'xtc_display_tax_value.inc.php');
  require_once(DIR_FS_INC . 'xtc_get_products_attribute_price_checkout.inc.php');

  // if the customer is not logged on, redirect them to the login page

  if (!isset($_SESSION['customer_id'])) {
 //   $_SESSION['navigation']->set_snapshot(array('mode' => 'SSL', 'page' => FILENAME_CHECKOUT_PAYMENT));
    xarRedirectResponse(xarModURL('commerce','user','login', '', 'SSL'));
  }

// if there is nothing in the customers cart, redirect them to the shopping cart page
  if ($_SESSION['cart']->count_contents() < 1) {
    xarRedirectResponse(xarModURL('commerce','user','shopping_cart'));
  }

// avoid hack attempts during the checkout procedure by checking the internal cartID
  if (isset($_SESSION['cart']->cartID) && isset($_SESSION['cartID'])) {
    if ($_SESSION['cart']->cartID != $_SESSION['cartID']) {
      xarRedirectResponse(xarModURL('commerce','user',(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
    }
  }

// if no shipping method has been selected, redirect the customer to the shipping method selection page
  if (!isset($_SESSION['shipping'])) {
    xarRedirectResponse(xarModURL('commerce','user',(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
  }

//check if display conditions on checkout page is true

  if (isset($_POST['payment'])) $_SESSION['payment'] = $_POST['payment'];

  if ($_POST['comments_added'] != '') {
    $_SESSION['comments'] = xtc_db_prepare_input($_POST['comments']);
  }

//-- TheMedia Begin check if display conditions on checkout page is true
// if conditions are not accepted, redirect the customer to the payment method selection page
  if (DISPLAY_CONDITIONS_ON_CHECKOUT == 'true') {
    if ($_POST['conditions'] == false) {
      xarRedirectResponse(xarModURL('commerce','user',(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode(ERROR_CONDITIONS_NOT_ACCEPTED), 'SSL', true, false));
    }
  }

// load the selected payment module
  require(DIR_WS_CLASSES . 'payment.php');
  $payment_modules = new payment($_SESSION['payment']);

  require(DIR_WS_CLASSES . 'order.php');
  $order = new order;

  $payment_modules->update_status();

  if ( ( is_array($payment_modules->modules) && (sizeof($payment_modules->modules) > 1) && !is_object($$_SESSION['payment']) ) || (is_object($$_SESSION['payment']) && ($$_SESSION['payment']->enabled == false)) ) {
    xarRedirectResponse(xarModURL('commerce','user',(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode(ERROR_NO_PAYMENT_MODULE_SELECTED), 'SSL'));
  }

  if (is_array($payment_modules->modules)) {
    $payment_modules->pre_confirmation_check();
  }

// load the selected shipping module
  require(DIR_WS_CLASSES . 'shipping.php');
  $shipping_modules = new shipping($_SESSION['shipping']);

  require(DIR_WS_CLASSES . 'order_total.php');
  $order_total_modules = new order_total;

// Stock Check
  $any_out_of_stock = false;
  if (STOCK_CHECK == 'true') {
    for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
      if (xtc_check_stock($order->products[$i]['id'], $order->products[$i]['qty'])) {
        $any_out_of_stock = true;
      }
    }
    // Out of Stock
    if ( (STOCK_ALLOW_CHECKOUT != 'true') && ($any_out_of_stock == true) ) {
      xarRedirectResponse(xarModURL('commerce','user','shopping_cart'));
    }
  }


  $breadcrumb->add(NAVBAR_TITLE_1_CHECKOUT_CONFIRMATION, xarModURL('commerce','user','checkout_shipping'));
  $breadcrumb->add(NAVBAR_TITLE_2_CHECKOUT_CONFIRMATION);


 require(DIR_WS_INCLUDES . 'header.php');

 $data['DELIVERY_LABEL'] = xarModAPIFunc('commerce','user','address_format',array(
    'address_format_id' =>$order->delivery['format_id'],
    'address' =>$order->delivery,
    'html' =>1,
    'boln' =>' ',
    'eoln' =>'<br>'));
 $data['BILLING_LABEL'] = xarModAPIFunc('commerce','user','address_format',array(
    'address_format_id' =>$order->billing['format_id'],
    'address' =>$order->billing,
    'html' =>1,
    'boln' =>' ',
    'eoln' =>'<br>'));
 $data['PRODUCTS_EDIT'] = xarModURL('commerce','user','shopping_cart', '', 'SSL');
 $data['SHIPPING_ADDRESS_EDIT'] = xarModURL('commerce','user',(FILENAME_CHECKOUT_SHIPPING_ADDRESS, '', 'SSL');
 $data['BILLING_ADDRESS_EDIT'] = xarModURL('commerce','user',(FILENAME_CHECKOUT_PAYMENT_ADDRESS, '', 'SSL');


  if ($_SESSION['sendto'] != false) {

    if ($order->info['shipping_method']) {
        $data['SHIPPING_METHOD'] = $order->info['shipping_method'];
        $data['SHIPPING_EDIT'] = xarModURL('commerce','user',(FILENAME_CHECKOUT_SHIPPING, '', 'SSL');

    }

  }

  if (sizeof($order->info['tax_groups']) > 1) {

  if ($_SESSION['customers_status']['customers_status_show_price_tax']== 0 && $_SESSION['customers_status']['customers_status_add_tax_ot']==1) {


}

  } else {

  }
$data_products = '<table width="100%" border="0" cellspacing="0" cellpadding="0">';
  for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {


    $data_products .= '          <tr>' . "\n" .
         '            <td class="main" nowrap align="left" valign="top" width="">' . $order->products[$i]['qty'] .' x '.$order->products[$i]['name']. '</td>' . "\n" .
     '              <td class="main" align="right" valign="top">' .xarModAPIFunc('commerce','user','get_products_price',array('products_id' =>$order->products[$i]['id'],'price_special' =>$price_special=1,'quantity' =>$quantity=$order->products[$i]['qty'])). '</td></tr>' . "\n" ;


    if ( (isset($order->products[$i]['attributes'])) && (sizeof($order->products[$i]['attributes']) > 0) ) {
      for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
        $data_products .= '<tr>
        <td class="main" align="left" valign="top">
        <nobr><small>&nbsp;<i> - '
        . $order->products[$i]['attributes'][$j]['option'] . ': ' . $order->products[$i]['attributes'][$j]['value'] .'
        </i></small></td>
        <td class="main" align="right" valign="top"><i><small>'
        .xtc_get_products_attribute_price_checkout($order->products[$i]['attributes'][$j]['price'],$order->products[$i]['tax'],1,$order->products[$i]['qty'],$order->products[$i]['attributes'][$j]['prefix']).
        '</i></small></nobr></td></tr>';
      }
    }

    $data_products .= '' . "\n";

    if ($_SESSION['customers_status']['customers_status_show_price_tax'] == 0 && $_SESSION['customers_status']['customers_status_add_tax_ot'] == 1) {
      if (sizeof($order->info['tax_groups']) > 1) $data_products .= '            <td class="main" valign="top" align="right">' . xtc_display_tax_value($order->products[$i]['tax']) . '%</td>' . "\n";
    }
     $data_products .=    '          </tr>' . "\n";
  }
  $data_products .= '</table>';
    $data['PRODUCTS_BLOCK'] = $data_products;
    include(DIR_WS_LANGUAGES . '/' . $_SESSION['language'] . '/modules/payment/' . $order->info['payment_method'] . '.php');
        $data['PAYMENT_METHOD'] = constant(MODULE_PAYMENT_ . strtoupper($order->info['payment_method']) . _TEXT_TITLE);
        $data['PAYMENT_EDIT'] = xarModURL('commerce','user',(FILENAME_CHECKOUT_PAYMENT, '', 'SSL');

$total_block='<table>';
  if (MODULE_ORDER_TOTAL_INSTALLED) {
    $order_total_modules->process();
      $q = new xenQuery();
      if(!$q->run()) return;
    $total_block.= $q->output();
  }
  $total_block.='</table>';
  $data['TOTAL_BLOCK'] = $total_block;


  if (is_array($payment_modules->modules)) {
    if ($confirmation = $payment_modules->confirmation()) {




$payment_info=$confirmation['title'];
      for ($i=0, $n=sizeof($confirmation['fields']); $i<$n; $i++) {

$payment_info .=
          '<table>
        <tr>
                <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                <td class="main">'. $confirmation['fields'][$i]['title'].'</td>
                <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                <td class="main">'. stripslashes($confirmation['fields'][$i]['field']).'</td>
              </tr></table>';

      }
      $data['PAYMENT_INFORMATION'] = $payment_info;

    }
  }

  if (xarModAPIFunc('commerce','user','not_null',array('arg' => $order->info['comments']))) {
  $data['ORDER_COMMENTS'] = nl2br(htmlspecialchars($order->info['comments'])) .

  }

  if (isset($$_SESSION['payment']->form_action_url)) {
    $form_action_url = $$_SESSION['payment']->form_action_url;
  } else {
    $form_action_url = xarModURL('commerce','user',(FILENAME_CHECKOUT_PROCESS, '', 'SSL');
  }
  $payment_button='';
  if (is_array($payment_modules->modules)) {
    $payment_button.= $payment_modules->process_button();
  }
  $data['MODULE_BUTTONS'] = $payment_button;
  $data['CHECKOUT_BUTTON'] =
<input type="image" src="#xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_confirm_order.gif')#" border="0" alt=IMAGE_BUTTON_CONFIRM_ORDER>
</form>' . "\n";


  $data['language'] = $_SESSION['language'];
  $data['PAYMENT_BLOCK'] = $payment_block;
  $smarty->caching = 0;
  return data;

?>