<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 Mario Zanier for XTcommerce
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------

function commerce_userapi_format_price_graduated($price_string,$price_special,$calculate_currencies,$products_tax_class)
    {
    $currencies_query = new xenQuery("SELECT symbol_left,
                                            symbol_right,
                                            decimal_places,
                                            decimal_point,
                                                thousands_point,
                                            value
                                            FROM ". TABLE_CURRENCIES ." WHERE
                                            code = '".$_SESSION['currency'] ."'");
      $q = new xenQuery();
      if(!$q->run()) return;
    $currencies_value=$q->output();
    $currencies_data=array();
    $currencies_data=array(
                            'SYMBOL_LEFT'=>$currencies_value['symbol_left'] ,
                            'SYMBOL_RIGHT'=>$currencies_value['symbol_right'] ,
                            'DECIMAL_PLACES'=>$currencies_value['decimal_places'] ,
                            'VALUE'=> $currencies_value['value']);
    if ($calculate_currencies=='true') {
    $price_string=$price_string * $currencies_data['VALUE'];
    }
    // add tax
    $products_tax=xtc_get_tax_rate($products_tax_class);
    if ($_SESSION['customers_status']['customers_status_show_price_tax'] =='0') {
        $products_tax='';
    }
    $price_string= (xarModAPIFunc('commerce','user','add_tax',array('price' =>$price_string,'tax' =>$products_tax)));
    // round price
    $price_string=xtc_precision($price_string,$currencies_data['DECIMAL_PLACES']);

    if ($price_special=='1') {
    $price_string=number_format($price_string,$currencies_data['DECIMAL_PLACES'], $currencies_data['DEC_POINT'], $currencies_data['THD_POINT']);

    $price_string = $currencies_data['SYMBOL_LEFT']. ' '.$price_string.' '.$currencies_data['SYMBOL_RIGHT'];
    }
    return $price_string;
    }
 ?>