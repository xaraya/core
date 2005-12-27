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

function commerce_userapi_get_products_attribute_price($attribute_price,$tax_class,$price_special,$quantity,$prefix)
    {
        if ($_SESSION['customers_status']['customers_status_show_price'] == '1') {
            $attribute_tax=xtc_get_tax_rate($tax_class);
        // check if user is allowed to see tax rates
                if ($_SESSION['customers_status']['customers_status_show_price_tax'] =='0') {
                $attribute_tax='';
                }
        // add tax
        $price_string=(xarModAPIFunc('commerce','user','add_tax',array('price' =>$attribute_price,'tax' =>$attribute_tax)))*$quantity;
        if ($_SESSION['customers_status']['customers_status_discount_attributes']=='0') {
        // format price & calculate currency
        $price_string=xtc_format_price($price_string,$price_special,$calculate_currencies=true);
            if ($price_special=='1') {
                $price_string = ' '.$prefix.' '.$price_string.' ';
            }
            } else {
            $discount=$_SESSION['customers_status']['customers_status_discount'];
            $rabatt_string = $price_string - ($price_string/100*$discount);
            $price_string=xtc_format_price($price_string,$price_special,$calculate_currencies=true);
            $rabatt_string=xtc_format_price($rabatt_string,$price_special,$calculate_currencies=true);
            if ($price_special=='1' && $price_string != $rabatt_string) {
                $price_string = ' '.$prefix.'<font color="ff0000"><s>'.$price_string.'</s></font> '.$rabatt_string.' ';
            } else {

            $price_string=$rabatt_string;
            if ($price_special=='1') $price_string=' '.$prefix.' '.$price_string;
            }
            }
        } else {
        $price_string= '  ' .NOT_ALLOWED_TO_SEE_PRICES;
        }
        return $price_string;
    } ?>