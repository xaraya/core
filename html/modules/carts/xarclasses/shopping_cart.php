<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 XT-Commerce
//  (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
//  (c) 2002-2003 osCommerce(shopping_cart.php,v 1.32 2003/02/11); www.oscommerce.com
//  (c) 2003  nextcommerce (shopping_cart.php,v 1.21 2003/08/17); www.nextcommerce.org
//   Third Party contributions:
//   Customers Status v3.x  (c) 2002-2003 Copyright Elari elari@free.fr | www.unlockgsm.com/dload-osc/ | CVS : http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/elari/?sortby=date#dirlist
// ----------------------------------------------------------------------

    class shoppingCart
    {
        var $contents, $total, $weight;
        var $userid;

        function shoppingCart()
        {
            $this->reset();
            $userid = xarSessionGetVar('uid');
        }

        function restore_contents()
        {
            if (!$this->userid) return 0;

            // insert current cart contents in database
            if ($this->contents) {
                reset($this->contents);
                while (list($products_id, ) = each($this->contents)) {
                $qty = $this->contents[$products_id]['qty'];
                new xenQuery('SELECT', $xartables['commerce_customers_basket'],array('products_id'));
                $q->eq('customers_id', $this->userid);
                $q->eq('products_id', $products_id);
                if(!$q->run()) return;

                if ($q->output() != array()) {
                    new xenQuery('INSERT', $xartables['commerce_customers_basket']);
                    $q->addfield('customers_id', $this->userid);
                    $q->addfield('products_id', $products_id);
                    $q->addfield('customers_basket_quantity', $qty);
                    $q->addfield('customers_basket_date_added', date('Ymd'));
                    if(!$q->run()) return;

                    if ($this->contents[$products_id]['attributes']) {
                        reset($this->contents[$products_id]['attributes']);
                        while (list($option, $value) = each($this->contents[$products_id]['attributes'])) {
                            new xenQuery('INSERT', $xartables['commerce_customers_basket_attributes']);
                            $q->addfield('customers_id', $this->userid);
                            $q->addfield('products_id', $products_id);
                            $q->addfield('products_options_id', $option);
                            $q->addfield('products_options_value_id', value);
                            if(!$q->run()) return;
                        }
                    }
                }
                else {
                    new xenQuery('UPDATE', $xartables['commerce_customers_basket']);
                    $q->addfield('customers_basket_quantity', $qty);
                    $q->eq('customers_id', $this->userid);
                    $q->eq('products_id', $products_id);
                    if(!$q->run()) return;
                }
            }
        }

        // reset per-session cart contents, but not the database contents
        $this->reset(FALSE);

        new xenQuery('SELECT', $xartables['commerce_customers_basket'],array('products_id','customers_basket_quantity'));
        $q->eq('customers_id', $this->userid);
        if(!$q->run()) return;

        while ($products = $q->output()) {
            $this->contents[$products['products_id']] = array('qty' => $products['customers_basket_quantity']);
            // attributes
            new xenQuery('SELECT', $xartables['commerce_customers_basket_attributes']);
            $q->addfields(array('products_options_id', 'products_options_value_id'));
            $q->eq('customers_id', $this->userid);
            $q->eq('products_id', $products_id);
            if(!$q->run()) return;

            while ($attributes = $q->output()) {
                  $this->contents[$products['products_id']]['attributes'][$attributes['products_options_id']] = $attributes['products_options_value_id'];
                }
            }

            $this->cleanup();
        }

        function reset($reset_database = false)
        {
            $this->contents = array();
            $this->total = 0;

            if ($this->userid && $reset_database) {
                new xenQuery('DELETE', $xartables['commerce_customers_basket']);
                $q->eq('customers_id', $this->userid);
                if(!$q->run()) return;
                new xenQuery('DELETE', $xartables['commerce_customers_basket_attributes']);
                $q->eq('customers_id', $this->userid);
                if(!$q->run()) return;
            }
        }

        function add_cart($products_id, $qty = '', $attributes = '')
        {

            $products_id = xtc_get_uprid($products_id, $attributes);

            if ($this->in_cart($products_id)) {
                $this->update_quantity($products_id, $qty, $attributes);
            }
            else {
                if ($qty == '') $qty = '1'; // if no quantity is supplied, then add '1' to the customers basket

                $this->contents[] = array($products_id);
                $this->contents[$products_id] = array('qty' => $qty);
                // insert into database
                if ($this->userid) {
                    new xenQuery('INSERT', $xartables['commerce_customers_basket']);
                    $q->addfield('customers_id', $this->userid);
                    $q->addfield('products_id', $products_id);
                    $q->addfield('customers_basket_quantity', $qty);
                    $q->addfield('customers_basket_date_added', date('Ymd'));
                    if(!$q->run()) return;
                }
                if (is_array($attributes)) {
                    reset($attributes);
                    while (list($option, $value) = each($attributes)) {
                        $this->contents[$products_id]['attributes'][$option] = $value;
                        // insert into database
                        if ($this->userid) {
                            new xenQuery('INSERT', $xartables['commerce_customers_basket_attributes']);
                            $q->addfield('customers_id', $this->userid);
                            $q->addfield('products_id', $products_id);
                            $q->addfield('products_options_id', $option);
                            $q->addfield('products_options_value_id', value);
                            if(!$q->run()) return;
                        }
                    }
                }
                $_SESSION['new_products_id_in_cart'] = $products_id;
            }
            $this->cleanup();
        }

        function update_quantity($products_id, $quantity = '', $attributes = '')
        {
            if ($quantity == '') return true; // nothing needs to be updated if theres no quantity, so we return true..

            $this->contents[$products_id] = array('qty' => $quantity);
            // update database
            if ($this->userid) {
                new xenQuery('UPDATE', $xartables['commerce_customers_basket']);
                $q->addfield('customers_basket_quantity', $quantity);
                $q->eq('customers_id', $this->userid);
                $q->eq('products_id', $products_id);
                if(!$q->run()) return;
            }

            if (is_array($attributes)) {
                reset($attributes);
                while (list($option, $value) = each($attributes)) {
                    $this->contents[$products_id]['attributes'][$option] = $value;
                    // update database
                    if ($this->userid) {
                        new xenQuery('UPDATE', $xartables['commerce_customers_basket_attributes']);
                        $q->addfield('products_options_value_id', value);
                        $q->eq('customers_id', $this->userid);
                        $q->eq('products_id', $products_id);
                        $q->eq('products_options_id', $option);
                        if(!$q->run()) return;
                    }
                }
            }
        }

        function cleanup()
        {
            reset($this->contents);
            while (list($key,) = each($this->contents)) {
                if ($this->contents[$key]['qty'] < 1) {
                    unset($this->contents[$key]);
                    // remove from database
                    if ($this->userid) {
                        new xenQuery('DELETE', $xartables['commerce_customers_basket']);
                        $q->eq('customers_id', $this->userid);
                        $q->eq('products_id', $key);
                        if(!$q->run()) return;
                        new xenQuery('DELETE', $xartables['commerce_customers_basket_attributes']);
                        $q->eq('customers_id', $this->userid);
                        $q->eq('products_id', $key);
                        if(!$q->run()) return;
                    }
                }
            }
        }

        function count_contents()
        {  // get total number of items in cart
            $total_items = 0;
            if (is_array($this->contents)) {
                reset($this->contents);
                while (list($products_id, ) = each($this->contents)) {
                    $total_items += $this->get_quantity($products_id);
                }
            }
            return $total_items;
        }

        function get_quantity($products_id)
        {
            if ($this->contents[$products_id]) {
                return $this->contents[$products_id]['qty'];
            }
            else {
                return 0;
            }
        }

        function in_cart($products_id)
        {
            if ($this->contents[$products_id]) {
                return true;
            }
            else {
                return false;
            }
        }

        function remove($products_id)
        {
            unset($this->contents[$products_id]);
            // remove from database
            if ($this->userid) {
                new xenQuery('DELETE', $xartables['commerce_customers_basket']);
                $q->eq('customers_id', $this->userid);
                $q->eq('products_id', $products_id);
                if(!$q->run()) return;
                new xenQuery('DELETE', $xartables['commerce_customers_basket_attributes']);
                $q->eq('customers_id', $this->userid);
                $q->eq('products_id', $products_id);
                if(!$q->run()) return;
            }
        }

        function remove_all()
        {
              $this->reset();
        }

        function get_product_id_list()
        {
            $product_id_list = '';
            if (is_array($this->contents)) {
                reset($this->contents);
                while (list($products_id, ) = each($this->contents)) {
                    $product_id_list .= ', ' . $products_id;
                }
            }
            return substr($product_id_list, 2);
        }

        function calculate()
        {
            $this->total = 0;
            $this->weight = 0;
            if (!is_array($this->contents)) return 0;

            reset($this->contents);
            while (list($products_id, ) = each($this->contents)) {
                $qty = $this->contents[$products_id]['qty'];

                // products price
                new xenQuery('SELECT', $xartables['commerce_customers_basket']);
                $q->addwfields(array('products_id', 'products_price', 'products_tax_class_id', 'products_weight'));
                $q->eq('products_id', xtc_get_prid($products_id));
                if(!$q->run()) return;

                if ($q->output() != array()) {
                    $product = $q->output();
                    $prid = $product['products_id'];
                    $products_tax = xtc_get_tax_rate($product['products_tax_class_id']);
                    $products_price = $product['products_price'];
                    $products_weight = $product['products_weight'];

                    new xenQuery('SELECT', $xartables['commerce_specials'],array('specials_new_products_price'));
                    $q->eq('products_id', $prid);
                    $q->eq('status', 1);
                    if(!$q->run()) return;

                    if ($q->output() != array()) {
                        $specials = $q->output();
                        $products_price = $specials['specials_new_products_price'];
                    }
                    $this->total += xarModAPIFunc('commerce','user','add_tax',array('price' =>$products_price,'tax' =>$products_tax)) * $qty;
                    $this->weight += ($qty * $products_weight);
                }

                // attributes price
                if ($this->contents[$products_id]['attributes']) {
                    reset($this->contents[$products_id]['attributes']);
                    include_once 'modules/xen/xarclasses/xenquery.php';
                    $xartables = xarDBGetTables();
                    while (list($option, $value) = each($this->contents[$products_id]['attributes'])) {
                        $q = new xenQuery('SELECT', $xartables['commerce_products_attributes']);
                        $q->addfields(array('options_values_price', 'price_prefix'));
                        $q->eq(products_id, $prid);
                        $q->eq(options_id, $option);
                        $q->eq(options_values_id, $value);
                        if(!$q->run()) return;

                        $attribute_price = $q->output();
                        if ($attribute_price['price_prefix'] == '+') {
                            $this->total += $qty * xarModAPIFunc('commerce','user','add_tax',array('price' =>$attribute_price['options_values_price'],'tax' =>$products_tax));
                        }
                        else {
                            $this->total -= $qty * xarModAPIFunc('commerce','user','add_tax',array('price' =>$attribute_price['options_values_price'],'tax' =>$products_tax));
                        }
                    }
                }
            }
        }

        function attributes_price($products_id)
        {
            if ($this->contents[$products_id]['attributes']) {
                reset($this->contents[$products_id]['attributes']);
                while (list($option, $value) = each($this->contents[$products_id]['attributes'])) {
                    $q = new xenQuery('SELECT', $xartables['commerce_products_attributes']);
                    $q->addfields(array('options_values_price', 'price_prefix'));
                    $q->eq(products_id, $products_id);
                    $q->eq(options_id, $option);
                    $q->eq(options_values_id, $value);
                    if(!$q->run()) return;

                    $attribute_price = $q->output();
                    if ($attribute_price['price_prefix'] == '+') {
                        $attributes_price += $attribute_price['options_values_price'];
                    }
                    else {
                        $attributes_price -= $attribute_price['options_values_price'];
                    }
                }
            }
            return $attributes_price;
        }

        function get_products()
        {
            if (!is_array($this->contents)) return 0;
            $products_array = array();
            reset($this->contents);

            $languages = xarModAPIFunc('commerce','user','get_languages');
            $localeinfo = xarLocaleGetInfo(xarMLSGetSiteLocale());
            $language = $localeinfo['lang'] . "_" . $localeinfo['country'];
            $currentlang = xarModAPIFunc('commerce','user','get_language',array('locale' => $language));
            $language_id = $currentlang['id'];

            while (list($products_id, ) = each($this->contents)) {
                $q = new xenQuery('SELECT');
                $q->addtable($xartables['commerce_products'], 'p');
                $q->addtable($xartables['commerce_products_description'], 'pd');
                $q->addfields(array('p.products_id', 'pd.products_name', 'p.products_model', 'p.products_price', 'p.products_weight', 'p.products_tax_class_id'));
                $q->eq('p.products_id', xtc_get_prid($products_id));
                $q->join('pd.products_id', 'p.products_id');
                $q->eq('pd.language_id', $currentlang['id']);
                if(!$q->run()) return;

                $products = $q->output();
                if ($products != array()) {
                    $prid = $products['products_id'];
                    $products_price = $products['products_price'];

                    $q = new xenQuery('SELECT',$xartables['commerce_specials']);
                    $q->addfield('specials_new_products_price');
                    $q->eq('products_id', $prid);
                    $q->eq('status', 1);
                    if(!$q->run()) return;

                    if ($q->output() != array()) {
                        $specials = $q->output();
                        $products_price = $specials['specials_new_products_price'];
                    }

                    $products_array[] = array('id' => $products_id,
                                            'name' => $products['products_name'],
                                            'model' => $products['products_model'],
                                            'price' => $products_price,
                                            'quantity' => $this->contents[$products_id]['qty'],
                                            'weight' => $products['products_weight'],
                                            'final_price' => ($products_price + $this->attributes_price($products_id)),
                                            'tax_class_id' => $products['products_tax_class_id'],
                                            'attributes' => $this->contents[$products_id]['attributes']);
                }
            }
            return $products_array;
        }

        function show_total()
        {
            $this->calculate();
            return $this->total;
        }

        function show_weight()
        {
            $this->calculate();
            return $this->weight;
        }

        function unserialize($broken)
        {
            for(reset($broken);$kv=each($broken);) {
                $key=$kv['key'];
                if (gettype($this->$key)!="user function")
                    $this->$key=$kv['value'];
            }
        }
    }
?>