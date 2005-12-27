<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 XT-Commerce
//   Third Party contributions:
//   Customers Status v3.x  (c) 2002-2003 Copyright Elari elari@free.fr | www.unlockgsm.com/dload-osc/ | CVS : http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/elari/?sortby=date#dirlist
//  (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
//  (c) 2002-2003 osCommerce (oscommerce.sql,v 1.83); www.oscommerce.com
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------

    require_once('modules/commerce/xarclasses/shopping_cart.php');
    $cart_empty=false;

  // include needed functions
//  require_once(DIR_FS_INC . 'xtc_array_to_string.inc.php');
//  require_once(DIR_FS_INC . 'xtc_recalculate_price.inc.php');

function commerce_user_shopping_cart()
{

//  $breadcrumb->add(NAVBAR_TITLE_SHOPPING_CART, xarModURL('commerce','user','shopping_cart'));

// require(DIR_WS_INCLUDES . 'header.php');

    $languages = xarModAPIFunc('commerce','user','get_languages');
    $localeinfo = xarLocaleGetInfo(xarMLSGetSiteLocale());
    $data['language'] = $localeinfo['lang'] . "_" . $localeinfo['country'];

    $cart = new shoppingCart();
//    if ($_SESSION['cart']->count_contents() > 0) {
    if ($cart->count_contents() > 0) {

        $hidden_options='';
        $_SESSION['any_out_of_stock'] = 0;
        $products = $cart->get_products();

        echo var_dump($products);exit;
        for ($i=0, $n=sizeof($products); $i<$n; $i++) {
            // Push all attributes information in an array
            if (isset($products[$i]['attributes'])) {
                while (list($option, $value) = each($products[$i]['attributes'])) {
                    $hidden_options.= xtc_draw_hidden_field('id[' . $products[$i]['id'] . '][' . $option . ']', $value);
                    $attributes = new xenQuery('SELECT');
                    $q->addfields(array(
                                    'popt.products_options_name',
                                    'poval.products_options_values_name',
                                    'pa.options_values_price',
                                    'pa.price_prefix',
                                    'pa.attributes_stock',
                                    'pa.products_attributes_id',
                                    'pa.attributes_model',
                                    ));
                    $q->addtable('TABLE_PRODUCTS_OPTIONS','popt');
                    $q->addtable('TABLE_PRODUCTS_OPTIONS_VALUES','poval');
                    $q->addtable('TABLE_PRODUCTS_ATTRIBUTES','pa');
                    $q->eq('pa.products_id', $products[$i]['id']);
                    $q->eq('pa.options_id', $$option);
                    $q->eq('pa.options_values_id', $value);
    //              $q->eq('popt.language_id', $_SESSION['languages_id']);
    //              $q->eq('poval.language_id', $_SESSION['languages_id']);
                    $q->join('pa.options_id', 'popt.products_options_id');
                    $q->join('pa.options_values_id', 'poval.products_options_values_id');
                    if(!$q->run()) return;

                    $attributes_values = $q->output();

                    $products[$i][$option]['products_options_name'] = $attributes_values['products_options_name'];
                    $products[$i][$option]['options_values_id'] = $value;
                    $products[$i][$option]['products_options_values_name'] = $attributes_values['products_options_values_name'];
                    $products[$i][$option]['options_values_price'] = $attributes_values['options_values_price'];
                    $products[$i][$option]['price_prefix'] = $attributes_values['price_prefix'];
                    $products[$i][$option]['weight_prefix'] = $attributes_values['weight_prefix'];
                    $products[$i][$option]['options_values_weight'] = $attributes_values['options_values_weight'];
                    $products[$i][$option]['attributes_stock'] = $attributes_values['attributes_stock'];
                    $products[$i][$option]['products_attributes_id'] = $attributes_values['products_attributes_id'];
                    $products[$i][$option]['products_attributes_model'] = $attributes_values['products_attributes_model'];
                }
            }
        }

        $data['HIDDEN_OPTIONS'] = $hidden_options;
    //    require(DIR_WS_MODULES. 'order_details_cart.php');

        if (STOCK_CHECK == 'true') {
            if ($_SESSION['any_out_of_stock']== 1) {
                if (STOCK_ALLOW_CHECKOUT == 'true') {
                    // write permission in session
                    $_SESSION['allow_checkout'] = 'true';
                    $data['info_message'] = OUT_OF_STOCK_CAN_CHECKOUT;
                } else {
                    $_SESSION['allow_checkout'] = 'false';
                $data['info_message'] = OUT_OF_STOCK_CANT_CHECKOUT;
                }
            } else {
                $_SESSION['allow_checkout'] = 'true';
            }
        }
    } else {
        // empty cart
        $data['cart_empty'] = true;
    }
    return $data;
}
?>