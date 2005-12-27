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
  require_once(DIR_FS_INC . 'xtc_count_customer_address_book_entries.inc.php');
  require_once(DIR_FS_INC . 'xtc_draw_radio_field.inc.php');

  // if the customer is not logged on, redirect them to the login page
  if (!isset($_SESSION['customer_id'])) {

    xarRedirectResponse(xarModURL('commerce','user','login', '', 'SSL'));
  }

  // if there is nothing in the customers cart, redirect them to the shopping cart page
  if ($_SESSION['cart']->count_contents() < 1) {
    xarRedirectResponse(xarModURL('commerce','user','shopping_cart'));
  }


  $error = false;
  $process = false;
  if (isset($_POST['action']) && ($_POST['action'] == 'submit')) {
    // process a new billing address
    if (xarModAPIFunc('commerce','user','not_null',array('arg' => $_POST['firstname'])) && xarModAPIFunc('commerce','user','not_null',array('arg' => $_POST['lastname'])) && xarModAPIFunc('commerce','user','not_null',array('arg' => $_POST['street_address']))) {
      $process = true;

      if (ACCOUNT_GENDER == 'true') $gender = xtc_db_prepare_input($_POST['gender']);
      if (ACCOUNT_COMPANY == 'true') $company = xtc_db_prepare_input($_POST['company']);
      $firstname = xtc_db_prepare_input($_POST['firstname']);
      $lastname = xtc_db_prepare_input($_POST['lastname']);
      $street_address = xtc_db_prepare_input($_POST['street_address']);
      if (ACCOUNT_SUBURB == 'true') $suburb = xtc_db_prepare_input($_POST['suburb']);
      $postcode = xtc_db_prepare_input($_POST['postcode']);
      $city = xtc_db_prepare_input($_POST['city']);
      $country = xtc_db_prepare_input($_POST['country']);
      if (ACCOUNT_STATE == 'true') {
        $zone_id = xtc_db_prepare_input($_POST['zone_id']);
        $state = xtc_db_prepare_input($_POST['state']);
      }

      if (ACCOUNT_GENDER == 'true') {
        if ( ($gender != 'm') && ($gender != 'f') ) {
          $error = true;

          $messageStack->add('checkout_address', ENTRY_GENDER_ERROR);
        }
      }

      if (strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
        $error = true;

        $messageStack->add('checkout_address', ENTRY_FIRST_NAME_ERROR);
      }

      if (strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
        $error = true;

        $messageStack->add('checkout_address', ENTRY_LAST_NAME_ERROR);
      }

      if (strlen($street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
        $error = true;

        $messageStack->add('checkout_address', ENTRY_STREET_ADDRESS_ERROR);
      }

      if (strlen($postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
        $error = true;

        $messageStack->add('checkout_address', ENTRY_POST_CODE_ERROR);
      }

      if (strlen($city) < ENTRY_CITY_MIN_LENGTH) {
        $error = true;

        $messageStack->add('checkout_address', ENTRY_CITY_ERROR);
      }

      if (ACCOUNT_STATE == 'true') {
        $zone_id = 0;
        $check_query = new xenQuery("select count(*) as total from " . TABLE_ZONES . " where zone_country_id = '" . (int)$country . "'");
      $q = new xenQuery();
      if(!$q->run()) return;
        $check = $q->output();
        $entry_state_has_zones = ($check['total'] > 0);
        if ($entry_state_has_zones == true) {
          $zone_query = new xenQuery("select distinct zone_id from " . TABLE_ZONES . " where zone_country_id = '" . (int)$country . "' and (zone_name like '" . xtc_db_input($state) . "%' or zone_code like '%" . xtc_db_input($state) . "%')");
          if ($zone_query->getrows() == 1) {
      $q = new xenQuery();
      if(!$q->run()) return;
            $zone = $q->output();
            $zone_id = $zone['zone_id'];
          } else {
            $error = true;

            $messageStack->add('checkout_address', ENTRY_STATE_ERROR_SELECT);
          }
        } else {
          if (strlen($state) < ENTRY_STATE_MIN_LENGTH) {
            $error = true;

            $messageStack->add('checkout_address', ENTRY_STATE_ERROR);
          }
        }
      }

      if ( (is_numeric($country) == false) || ($country < 1) ) {
        $error = true;

        $messageStack->add('checkout_address', ENTRY_COUNTRY_ERROR);
      }

      if ($error == false) {
        $q->addfield('customers_id',$_SESSION['customer_id']);
                                $q->addfield('entry_firstname',$firstname);
                                $q->addfield('entry_lastname',$lastname);
                                $q->addfield('entry_street_address',$street_address);
                                $q->addfield('entry_postcode',$postcode);
                                $q->addfield('entry_city',$city);
                                $q->addfield('entry_country_id',$country);

        if (ACCOUNT_GENDER == 'true') $q->addfield('entry_gender',$gender);
        if (ACCOUNT_COMPANY == 'true') $q->addfield('entry_company',$company);
        if (ACCOUNT_SUBURB == 'true') $q->addfield('entry_suburb',$suburb);
        if (ACCOUNT_STATE == 'true') {
          if ($zone_id > 0) {
            $q->addfield('entry_zone_id',$zone_id);
            $q->addfield('entry_state','');
          } else {
            $q->addfield('entry_zone_id','0');
            $q->addfield('entry_state',$state);
          }
        }

        xtc_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);

        $_SESSION['billto'] = xtc_db_insert_id();

        if (isset($_SESSION['payment'])) unset($_SESSION['payment']);

        xarRedirectResponse(xarModURL('commerce','user',(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
      }
      // process the selected billing destination
    } elseif (isset($_POST['address'])) {
      $reset_payment = false;
      if (isset($_SESSION['billto'])) {
        if ($billto != $_POST['address']) {
          if (isset($_SESSION['payment'])) {
            $reset_payment = true;
          }
        }
      }

      $_SESSION['billto'] = $_POST['address'];

      $check_address_query = new xenQuery("select count(*) as total from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . $_SESSION['customer_id'] . "' and address_book_id = '" . $_SESSION['billto'] . "'");
      $q = new xenQuery();
      if(!$q->run()) return;
      $check_address = $q->output();

      if ($check_address['total'] == '1') {
        if ($reset_payment == true) unset($_SESSION['payment']);
        xarRedirectResponse(xarModURL('commerce','user',(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
      } else {
        unset($_SESSION['billto']);
      }
      // no addresses to select from - customer decided to keep the current assigned address
    } else {
      $_SESSION['billto'] = $_SESSION['customer_default_address_id'];

      xarRedirectResponse(xarModURL('commerce','user',(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
    }
  }

  // if no billing destination address was selected, use their own address as default
  if (!isset($_SESSION['billto'])) {
    $_SESSION['billto'] = $_SESSION['customer_default_address_id'];
  }

  $breadcrumb->add(NAVBAR_TITLE_1_PAYMENT_ADDRESS, xarModURL('commerce','user','checkout_payment'));
  $breadcrumb->add(NAVBAR_TITLE_2_PAYMENT_ADDRESS, xarModURL('commerce','user','checkout_payment_address'));

  $addresses_count = xtc_count_customer_address_book_entries();
 require(DIR_WS_INCLUDES . 'header.php');


  if ($messageStack->size('checkout_address') > 0) {
  $data['error'] = $messageStack->output('checkout_address');

  }

  if ($process == false) {
  $data['ADDRESS_LABEL'] = xarModAPIFunc('commerce','user','address_label',array(
    'address_format_id' =>$_SESSION['customer_id'],
    'address' =>$_SESSION['billto'],
    'html' =>true,
    'boln' =>' ',
    'eoln' =>'<br>'));

    if ($addresses_count > 1) {

$address_content='<table border="0" width="100%" cellspacing="0" cellpadding="0">';
      $radio_buttons = 0;

      $addresses_query = new xenQuery("select address_book_id, entry_firstname as firstname, entry_lastname as lastname, entry_company as company, entry_street_address as street_address, entry_suburb as suburb, entry_city as city, entry_postcode as postcode, entry_state as state, entry_zone_id as zone_id, entry_country_id as country_id from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . $_SESSION['customer_id'] . "'");
      $q = new xenQuery();
      if(!$q->run()) return;
      while ($addresses = $q->output()) {
        $format_id = xtc_get_address_format_id($address['country_id']);
 $address_content.=' <tr>
                <td>'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                <td colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                ';
       if ($addresses['address_book_id'] == $_SESSION['billto']) {
          $address_content.='                  <tr id="defaultSelected" class="moduleRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectRowEffect(this, ' . $radio_buttons . ')">' . "\n";
        } else {
          $address_content.= '                  <tr class="moduleRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectRowEffect(this, ' . $radio_buttons . ')">' . "\n";
        }
$address_content.='
                    <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                    <td class="main" colspan="2"><b>'. $addresses['firstname'] . ' ' . $addresses['lastname'].'</b></td>
                    <td class="main" align="right">'. xtc_draw_radio_field('address', $addresses['address_book_id'], ($addresses['address_book_id'] == $_SESSION['billto'])).'</td>
                    <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                  </tr>
                  <tr>
                    <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                    <td colspan="3"><table border="0" cellspacing="0" cellpadding="2">
                      <tr>
                        <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                        <td class="main">'. xarModAPIFunc('commerce','user','address_format',array(
    'address_format_id' =>$format_id,
    'address' =>$addresses,
    'html' =>true,
    'boln' =>' ',
    'eoln' =>', '))
.'</td>
                        <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                      </tr>
                    </table></td>
                    <td width="10">'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
                  </tr>
                </table></td>
                <td>'. xtc_draw_separator('pixel_trans.gif', '10', '1').'</td>
              </tr>';

        $radio_buttons++;
      }
      $address_content.='</table>';
$data['BLOCK_ADDRESS'] = $address_content;

    }
  }

  if ($addresses_count < MAX_ADDRESS_BOOK_ENTRIES) {

 require(DIR_WS_MODULES . 'checkout_new_address.php');
  }
  $data['BUTTON_CONTINUE'] = .
  <input type="image" src="#xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_continue.gif')#" border="0" alt=IMAGE_BUTTON_CONTINUE>;

  if ($process == true) {
  $data['BUTTON_BACK'] = '<a href="' . xarModURL('commerce','user',(FILENAME_CHECKOUT_PAYMENT_ADDRESS, '', 'SSL') . '">' .
  xarModAPIFunc('commerce','user','image',array('src' => xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_back.gif'),
        'alt' => IMAGE_BUTTON_BACK);
. '</a>';

  }

  $data['language'] = $_SESSION['language'];

  $smarty->caching = 0;
  return data;
?>