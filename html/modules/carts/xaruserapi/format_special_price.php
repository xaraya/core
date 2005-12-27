<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 Mario Zanier for XTcommerce
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------

function commerce_uerapi_format_special_price ($special_price,$price,$price_special,$calculate_currencies,$quantity,$products_tax)
    {
    // calculate currencies

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
                            'DECIMAL_PLACES'=>$currencies_value['decimal_places'],
                            'DEC_POINT'=>$currencies_value['decimal_point'],
                            'THD_POINT'=>$currencies_value['thousands_point'],
                            'VALUE'=> $currencies_value['value'])                           ;
    if ($_SESSION['customers_status']['customers_status_show_price_tax'] =='0') {
        $products_tax='';
    }
    //$special_price= (xarModAPIFunc('commerce','user','add_tax',array('price' =>$special_price,'tax' =>$products_tax)))*$quantity;
    //$price= (xarModAPIFunc('commerce','user','add_tax',array('price' =>$price,'tax' =>$products_tax)))*$quantity;
    $price=$price*$quantity;
    $special_price=$special_price*$quantity;

    if ($calculate_currencies=='true') {
    $special_price=$special_price * $currencies_data['VALUE'];
    $price=$price * $currencies_data['VALUE'];

    }
    // round price
    $special_price=xtc_precision($special_price,$currencies_data['DECIMAL_PLACES'] );
    $price=xtc_precision($price,$currencies_data['DECIMAL_PLACES'] );

    if ($price_special=='1') {
    $price=number_format($price,$currencies_data['DECIMAL_PLACES'], $currencies_data['DEC_POINT'], $currencies_data['THD_POINT']);
    $special_price=number_format($special_price,$currencies_data['DECIMAL_PLACES'], $currencies_data['DEC_POINT'], $currencies_data['THD_POINT']);

    $special_price ='<font color="ff0000"><s>'. $currencies_data['SYMBOL_LEFT'].' '.$price.' '.$currencies_data['SYMBOL_RIGHT'].' </s></font>'. $currencies_data['SYMBOL_LEFT']. ' '.$special_price.' '.$currencies_data['SYMBOL_RIGHT'];
    }
    return $special_price;
    }
 ?>