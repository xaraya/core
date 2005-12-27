<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 Mario Zanier for XTcommerce
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------
// include needed functions
require_once(DIR_FS_INC . 'xtc_precision.inc.php');

// parameters $price_string,$price_special,$calculate_currencies,$show_currencies=1

function commerce_userapi_format_price ($args)
{
    include_once 'modules/xen/xarclasses/xenquery.php';
    $xartables = xarDBGetTables();
    extract($args);

    $q = new xenQuery('SELECT', $xartables['commerce_currencies']);
    $q->addfields(array('symbol_left',
                        'symbol_right',
                        'decimal_places',
                        'value'));
    $q->eq('code',$_SESSION['currency']);
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

    // round price
    $price_string=xtc_precision($price_string,$currencies_data['DECIMAL_PLACES']);


    if ($price_special=='1') {
        $q = new xenQuery('SELECT', $xartables['commerce_currencies']);
        $q->addfields(array('symbol_left',
                            'decimal_point',
                            'thousands_point',
                            'value'));
        $q->eq('code',$_SESSION['currency']);
        if(!$q->run()) return;
        $currencies_value = $q->output();
        $price_string = number_format($price_string,$currencies_data['DECIMAL_PLACES'], $currencies_value['decimal_point'], $currencies_value['thousands_point']);
        if ($show_currencies == 1) {
         $price_string = $currencies_data['SYMBOL_LEFT']. ' '.$price_string.' '.$currencies_data['SYMBOL_RIGHT'];
        }
    }
    return $price_string;
}
?>