<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 Mario Zanier for XTcommerce
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------

// parameters $price, $discount

function commerce_userapi_recalculate_price($args)
{
    extract($args);
    if(!isset($discount)) $discount = 100;
    $price = -100*$price/($discount-100)/100*$discount;
    return $price;
}
?>