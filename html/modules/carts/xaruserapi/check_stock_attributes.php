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

    function commerce_userapi_check_stock_attributes($attribute_id, $products_quantity) {

        $stock_query = new xenQuery("SELECT
            attributes_stock
            FROM ".TABLE_PRODUCTS_ATTRIBUTES."
            WHERE products_attributes_id='".$attribute_id."'");
        $q = new xenQuery();
        if(!$q->run()) return;
        $stock_data=$q->output();
        $stock_left = $stock_data['attributes_stock'] - $products_quantity;
        $out_of_stock = '';

        if ($stock_left < 0) {
            $out_of_stock = '<span class="markProductOutOfStock">' . STOCK_MARK_PRODUCT_OUT_OF_STOCK . '</span>';
        }

        return $out_of_stock;
    }
 ?>