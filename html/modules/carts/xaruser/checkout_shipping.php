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

  require_once(DIR_FS_INC . 'xtc_get_address_format_id.inc.php');
  require_once(DIR_FS_INC . 'xtc_count_shipping_modules.inc.php');
  require_once(DIR_FS_INC . 'xtc_draw_textarea_field.inc.php');
  require_once(DIR_FS_INC . 'xtc_draw_radio_field.inc.php');

  require(DIR_WS_CLASSES.'http_client.php');

  // check if checkout is allowed
  if ($_SESSION['allow_checkout']=='false') xarRedirectResponse(xarModURL('commerce','user','shopping_cart'));

  // if the customer is not logged on, redirect them to the login page
  if (!isset($_SESSION['customer_id'])) {

    xarRedirectResponse(xarModURL('commerce','user','login', '', 'SSL'));
  }

  // if there is nothing in the customers cart, redirect them to the shopping cart page
  if ($_SESSION['cart']->count_contents() < 1) {
    xarRedirectResponse(xarModURL('commerce','user','shopping_cart'));
  }

  // if no shipping destination address was selected, use the customers own address as default
  if (!isset($_SESSION['sendto'])) {
    $_SESSION['sendto'] = $_SESSION['customer_default_address_id'];
  } else {
    // verify the selected shipping address
    $check_address_query = new xenQuery("select count(*) as total from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . $_SESSION['customer_id'] . "' and address_book_id = '" . $_SESSION['sendto'] . "'");
      $q = new xenQuery();
      if(!$q->run()) return;
    $check_address = $q->output();

    if ($check_address['total'] != '1') {
      $_SESSION['sendto'] = $_SESSION['customer_default_address_id'];
      if (isset($_SESSION['shipping'])) unset($_SESSION['shipping']);
    }
  }

  require(DIR_WS_CLASSES . 'order.php');
  $order = new order;

  // register a random ID in the session to check throughout the checkout procedure
  // against alterations in the shopping cart contents
  $_SESSION['cartID'] = $_SESSION['cart']->cartID;

  // if the order contains only virtual products, forward the customer to the billing page as
  // a shipping address is not needed
  if ($order->content_type == 'virtual') {
    $_SESSION['shipping'] = false;
    $_SESSION['sendto'] = false;
    xarRedirectResponse(xarModURL('commerce','user',(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
  }

  $total_weight = $_SESSION['cart']->show_weight();
  //  $total_weight = $_SESSION['cart']['weight'];
  $total_count = $_SESSION['cart']->count_contents();

  if ($order->delivery['country']['iso_code_2'] != '') {
    $_SESSION['delivery_zone'] = $order->delivery['country']['iso_code_2'];
  }
  // load all enabled shipping modules
  require(DIR_WS_CLASSES . 'shipping.php');
  $shipping_modules = new shipping;

  if ( defined('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING') && (MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING == 'true') ) {
    switch (MODULE_ORDER_TOTAL_SHIPPING_DESTINATION) {
      case 'national':
        if ($order->delivery['country_id'] == STORE_COUNTRY) $pass = true; break;
      case 'international':
        if ($order->delivery['country_id'] != STORE_COUNTRY) $pass = true; break;
      case 'both':
        $pass = true; break;
      default:
        $pass = false; break;
    }

    $free_shipping = false;
    if ( ($pass == true) && ($order->info['total'] >= MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER) ) {
      $free_shipping = true;

      include(DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/order_total/ot_shipping.php');
    }
  } else {
    $free_shipping = false;
  }

  // process the selected shipping method
  if ( isset($_POST['action']) && ($_POST['action'] == 'process') ) {

    if ( (xtc_count_shipping_modules() > 0) || ($free_shipping == true) ) {
      if ( (isset($_POST['shipping'])) && (strpos($_POST['shipping'], '_')) ) {
        $_SESSION['shipping'] = $_POST['shipping'];

        list($module, $method) = explode('_', $_SESSION['shipping']);
        if ( is_object($$module) || ($_SESSION['shipping'] == 'free_free') ) {
          if ($_SESSION['shipping'] == 'free_free') {
            $quote[0]['methods'][0]['title'] = FREE_SHIPPING_TITLE;
            $quote[0]['methods'][0]['cost'] = '0';
          } else {
            $quote = $shipping_modules->quote($method, $module);
          }
          if (isset($quote['error'])) {
            unset($_SESSION['shipping']);
          } else {
            if ( (isset($quote[0]['methods'][0]['title'])) && (isset($quote[0]['methods'][0]['cost'])) ) {
              $_SESSION['shipping'] = array('id' => $_SESSION['shipping'],
                                'title' => (($free_shipping == true) ?  $quote[0]['methods'][0]['title'] : $quote[0]['module'] . ' (' . $quote[0]['methods'][0]['title'] . ')'),
                                'cost' => $quote[0]['methods'][0]['cost']);


              xarRedirectResponse(xarModURL('commerce','user',(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
            }
          }
        } else {
          unset($_SESSION['shipping']);
        }
      }
    } else {
      $_SESSION['shipping'] = false;

      xarRedirectResponse(xarModURL('commerce','user',(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
    }
  }

  // get all available shipping quotes
  $quotes = $shipping_modules->quote();

  // if no shipping method has been selected, automatically select the cheapest method.
  // if the modules status was changed when none were available, to save on implementing
  // a javascript force-selection method, also automatically select the cheapest shipping
  // method if more than one module is now enabled
  if ( !isset($_SESSION['shipping']) || ( isset($_SESSION['shipping']) && ($_SESSION['shipping'] == false) && (xtc_count_shipping_modules() > 1) ) ) $_SESSION['shipping'] = $shipping_modules->cheapest();


  $breadcrumb->add(NAVBAR_TITLE_1_CHECKOUT_SHIPPING, xarModURL('commerce','user','checkout_shipping'));
  $breadcrumb->add(NAVBAR_TITLE_2_CHECKOUT_SHIPPING, xarModURL('commerce','user','checkout_shipping'));

 require(DIR_WS_INCLUDES . 'header.php');

$data['ADDRESS_LABEL'] = xarModAPIFunc('commerce','user','address_label',array(
    'address_format_id' =>$_SESSION['customer_id'],
    'address' =>$_SESSION['sendto'],
    'html' =>true,
    'boln' =>' ',
    'eoln' =>'<br>'));
$data['BUTTON_ADDRESS'] = '<a href="' . xarModURL('commerce','user',(FILENAME_CHECKOUT_SHIPPING_ADDRESS, '', 'SSL') . '">' .
xarModAPIFunc('commerce','user','image',array('src' => xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_change_address.gif'),
        'alt' => IMAGE_BUTTON_CHANGE_ADDRESS);
. '</a>';
$data['BUTON_CONTINUE'] = <input type="image" src="#xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_continue.gif')#" border="0" alt=IMAGE_BUTTON_CONTINUE>;



  if (xtc_count_shipping_modules() > 0) {

$shipping_block ='
<table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">';



   if ($free_shipping == true) {

$shipping_block .='
              <tr>
                <td>'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                <td colspan="2" width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                    <td class="main" colspan="3"><b>'. FREE_SHIPPING_TITLE.'</b>&nbsp;'. $quotes[$i]['icon'].'</td>
                    <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                  </tr>
                  <tr id="defaultSelected" class="moduleRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectRowEffect(this, 0)">
                    <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                    <td class="main" width="100%">'. sprintf(FREE_SHIPPING_DESCRIPTION, $currencies->format(MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER)) . xtc_draw_hidden_field('shipping', 'free_free').'</td>
                    <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                  </tr>
                </table></td>
                <td>'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
              </tr>';

    } else {
      $radio_buttons = 0;
      for ($i=0, $n=sizeof($quotes); $i<$n; $i++) {

$shipping_block .='
              <tr>
                <td>'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                <td colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                    <td class="main" colspan="3"><b>'. $quotes[$i]['module'].'</b>&nbsp;'. $quotes[$i]['icon'].'</td>
                    <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                  </tr>';

        if (isset($quotes[$i]['error'])) {
$shipping_block .='
                  <tr>
                    <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                    <td class="main" colspan="3">'. $quotes[$i]['error'].'</td>
                    <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                  </tr>';
        } else {
          for ($j=0, $n2=sizeof($quotes[$i]['methods']); $j<$n2; $j++) {
            // set the radio button to be checked if it is the method chosen
            $checked = (($quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id'] == $_SESSION['shipping']['id']) ? true : false);

            if ( ($checked == true) || ($n == 1 && $n2 == 1) ) {
              $shipping_block .='                  <tr id="defaultSelected" class="moduleRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectRowEffect(this, ' . $radio_buttons . ')">' . "\n";
            } else {
              $shipping_block .= '                 <tr class="moduleRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectRowEffect(this, ' . $radio_buttons . ')">' . "\n";
            }
$shipping_block .='
                    <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                    <td class="main" width="75%">'. $quotes[$i]['methods'][$j]['title'].'</td>
';
            if ( ($n > 1) || ($n2 > 1) ) {
              if ($_SESSION['customers_status']['customers_status_show_price_tax'] == 0 ) $quotes[$i]['tax'] = '';
if ($_SESSION['customers_status']['customers_status_show_price_tax']==0)  $quotes[$i]['tax']=0;
              $shipping_block .='
                    <td class="main">'. xtc_format_price(xarModAPIFunc('commerce','user','add_tax',array('price' =>$quotes[$i]['methods'][$j]['cost'],'tax' =>$quotes[$i]['tax'])),$price_special=1,$calculate_currencies=true).'</td>
                    <td class="main" align="right">'. xtc_draw_radio_field('shipping', $quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id'], $checked).'</td>
';
            } else {

if ($_SESSION['customers_status']['customers_status_show_price_tax']==0)  $quotes[$i]['tax']=0;
$shipping_block .='
                    <td class="main" align="right" colspan="2">'. xtc_format_price(xarModAPIFunc('commerce','user','add_tax',array('price' =>$quotes[$i]['methods'][$j]['cost'],'tax' =>$quotes[$i]['tax'])),$price_special=1,$calculate_currencies=true) .
                    xtc_draw_hidden_field('shipping', $quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id']).'
                    </td>
';
            }
$shipping_block .='
                    <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                  </tr>
';
            $radio_buttons++;
          }
        }
$shipping_block .='
                </table></td>
                <td>'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
              </tr>
';
      }
    }

$shipping_block .='
            </table></td>
          </tr>
        </table>
';

  }





  $data['language'] = $_SESSION['language'];
  $data['SHIPPING_BLOCK'] = $shipping_block;
  $smarty->caching = 0;
  return data;
  ?>