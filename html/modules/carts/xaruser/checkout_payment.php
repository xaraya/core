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
  require_once(DIR_FS_INC . 'xtc_get_address_format_id.inc.php');
  require_once(DIR_FS_INC . 'xtc_draw_radio_field.inc.php');
  require_once(DIR_FS_INC . 'xtc_draw_textarea_field.inc.php');
  require_once(DIR_FS_INC . 'xtc_draw_checkbox_field.inc.php');
  require_once(DIR_FS_INC . 'xtc_check_stock.inc.php');


  // if the customer is not logged on, redirect them to the login page
//  if (!isset($_SESSION['customer_id'])) || ($customer_status_value['customers_status'] == DEFAULT_CUSTOMERS_STATUS_ID_GUEST) ) {
  if (!isset($_SESSION['customer_id'])) {

    xarRedirectResponse(xarModURL('commerce','user','login', '', 'SSL'));
  }

  // if there is nothing in the customers cart, redirect them to the shopping cart page
  if ($_SESSION['cart']->count_contents() < 1) {
    xarRedirectResponse(xarModURL('commerce','user','shopping_cart'));
  }

  // if no shipping method has been selected, redirect the customer to the shipping method selection page
  if (!isset($_SESSION['shipping'])) {
    xarRedirectResponse(xarModURL('commerce','user',(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
  }

  // avoid hack attempts during the checkout procedure by checking the internal cartID
  if (isset($_SESSION['cart']->cartID) && isset($_SESSION['cartID'])) {
    if ($_SESSION['cart']->cartID != $_SESSION['cartID']) {
      xarRedirectResponse(xarModURL('commerce','user',(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
    }
  }

  // Stock Check
  if ( (STOCK_CHECK == 'true') && (STOCK_ALLOW_CHECKOUT != 'true') ) {
    $products = $_SESSION['cart']->get_products();
    $any_out_of_stock = 0;
    for ($i=0, $n=sizeof($products); $i<$n; $i++) {
      if (xtc_check_stock($products[$i]['id'], $products[$i]['quantity'])) {
        $any_out_of_stock = 1;
      }
    }
    if ($any_out_of_stock == 1) {
      xarRedirectResponse(xarModURL('commerce','user','shopping_cart'));
    }
  }

  // if no billing destination address was selected, use the customers own address as default
  if (!isset($_SESSION['billto'])) {
    $_SESSION['billto'] = $_SESSION['customer_default_address_id'];
  } else {
    // verify the selected billing address
    $check_address_query = new xenQuery("select count(*) as total from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . $_SESSION['customer_id'] . "' and address_book_id = '" . $_SESSION['billto'] . "'");
      $q = new xenQuery();
      if(!$q->run()) return;
    $check_address = $q->output();

    if ($check_address['total'] != '1') {
      $_SESSION['billto'] = $_SESSION['customer_default_address_id'];
      if (isset($_SESSION['payment'])) unset($_SESSION['payment']);
    }
  }

  require(DIR_WS_CLASSES . 'order.php');
  $order = new order;

//  $_SESSION['comments'] = xtc_db_prepare_input($_POST['comments']);

  $total_weight = $_SESSION['cart']->show_weight();
  $total_count = $_SESSION['cart']->count_contents();

  if ($order->billing['country']['iso_code_2'] != '') {
    $_SESSION['delivery_zone'] = $order->billing['country']['iso_code_2'];
  }
  // load all enabled payment modules
  require(DIR_WS_CLASSES . 'payment.php');
  $payment_modules = new payment;


  $breadcrumb->add(NAVBAR_TITLE_1_CHECKOUT_PAYMENT, xarModURL('commerce','user','checkout_shipping'));
  $breadcrumb->add(NAVBAR_TITLE_2_CHECKOUT_PAYMENT, xarModURL('commerce','user','checkout_payment'));

$data['ADDRESS_LABEL'] = xarModAPIFunc('commerce','user','address_label',array(
    'address_format_id' =>$_SESSION['customer_id'],
    'address' =>$_SESSION['billto'],
    'html' =>true,
    'boln' =>' ',
    'eoln' =>'<br>'));
$data['BUTTON_ADDRESS'] = '<a href="' . xarModURL('commerce','user',(FILENAME_CHECKOUT_PAYMENT_ADDRESS, '', 'SSL') . '">' .
xarModAPIFunc('commerce','user','image',array('src' => xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_change_address.gif'),
        'alt' => IMAGE_BUTTON_CHANGE_ADDRESS);
. '</a>';
$data['BUTTON_CONTINUE'] = <input type="image" src="#xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_continue.gif')#" border="0" alt=IMAGE_BUTTON_CONTINUE>;

?>


<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<?php
  if (isset($_GET['payment_error']) && is_object(${$_GET['payment_error']}) && ($error = ${$_GET['payment_error']}->get_error())) {

$data['error'] = '
<table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b>'.  htmlspecialchars($error['title']).'</b></td>
          </tr>
        </table>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBoxNotice">
          <tr class="infoBoxNoticeContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td>'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                <td class="main" width="100%" valign="top">'. htmlspecialchars($error['error']).'</td>
                <td>'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
              </tr>
            </table></td>
          </tr>
        </table>';

  }

$payment_block .= '
<table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
';

  $selection = $payment_modules->selection();


  $radio_buttons = 0;
  for ($i=0, $n=sizeof($selection); $i<$n; $i++) {
$payment_block .= '
              <tr>
                <td>'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                <td colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                ';

    if ( ($selection[$i]['id'] == $payment) || ($n == 1) ) {
      $payment_block .= '                  <tr id="defaultSelected" class="moduleRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectRowEffect(this, ' . $radio_buttons . ')">' . "\n";
    } else {
      $payment_block .= '                   <tr class="moduleRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectRowEffect(this, ' . $radio_buttons . ')">' . "\n";
    }
$payment_block .= '
                    <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                    <td class="main" colspan="3"><b>'. $selection[$i]['module'].'</b></td>
                    <td class="main" align="right">
';

    if (sizeof($selection) > 1) {
      $payment_block .=   xtc_draw_radio_field('payment', $selection[$i]['id'], ($selection[$i]['id'] == $_SESSION['payment']));
    } else {
      $payment_block .=  xtc_draw_hidden_field('payment', $selection[$i]['id']);
    }
$payment_block .= '
                    </td>
                    <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                  </tr>
';
    if (isset($selection[$i]['error'])) {
$payment_block .= '
                  <tr>
                    <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                    <td class="main" colspan="4">'.$selection[$i]['error'].'</td>
                    <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                  </tr>
';
    } else {
$payment_block .= '
                  <tr>
                    <td width="10">'.xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                    <td colspan="4"><table border="0" cellspacing="0" cellpadding="2">
';
      for ($j=0, $n2=sizeof($selection[$i]['fields']); $j<$n2; $j++) {
$payment_block .= '
                      <tr>
                        <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                        <td class="main">'. $selection[$i]['fields'][$j]['title'].'</td>
                        <td>'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                        <td class="main">'. $selection[$i]['fields'][$j]['field'].'</td>
                        <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                      </tr>
';
      }
$payment_block .= '
                    </table></td>
                    <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                  </tr>
';
      $radio_buttons++;
    }
$payment_block .= '
                </table></td>
                <td>'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
              </tr>
';

  }
  $data['COMMENTS'] = xtc_draw_textarea_field('comments', 'soft', '60', '5', $_SESSION['comments']) .
  xtc_draw_hidden_field('comments_added', 'YES');

  //check if display conditions on checkout page is true
  if (DISPLAY_CONDITIONS_ON_CHECKOUT == 'true') {

         $shop_content_query=new xenQuery("SELECT
                    content_title,
                    content_heading,
                    content_text,
                    content_file
                    FROM ".TABLE_CONTENT_MANAGER."
                    WHERE content_group='3'
                    AND languages_id='".$_SESSION['languages_id']."'");
      $q = new xenQuery();
      if(!$q->run()) return;
    $shop_content_data=$q->output();


     if ($shop_content_data['content_file']!=''){

$conditions= '<iframe SRC="'.DIR_WS_CATALOG.'media/content/'.$shop_content_data['content_file'].'" width="100%" height="300">';
$conditions.= '</iframe>';
 } else {

 $conditions= '<textarea name="blabla" cols="60" rows="10" readonly="readonly">'.  strip_tags(str_replace('<br>',"\n",$shop_content_data['content_text'])).'</textarea>';
}

$data['AGB'] = $conditions;
$data['AGB_checkbox'] = '<input type="checkbox" name="conditions" id="1">';

  }

$payment_block .= '
        </table></td>
      </tr>
    </table>';




  $data['language'] = $_SESSION['language'];
  $data['PAYMENT_BLOCK'] = $payment_block;
  $smarty->caching = 0;
  return data;
?>