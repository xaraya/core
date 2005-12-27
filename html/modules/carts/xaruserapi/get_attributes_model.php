<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 XT-Commerce
// ----------------------------------------------------------------------

function commerce_userapi_get_attributes_model($product_id, $attribute_name)
    {

    $options_value_id_query=new xenQuery("SELECT
                products_options_values_id
                FROM ".TABLE_PRODUCTS_OPTIONS_VALUES."
                WHERE products_options_values_name='".$attribute_name."'");
      $q = new xenQuery();
      if(!$q->run()) return;
    $options_value_id_data=$q->output();

    $options_attr_query=new xenQuery("SELECT
                attributes_model
                FROM ".TABLE_PRODUCTS_ATTRIBUTES."
                WHERE options_values_id='".$options_value_id_data['products_options_values_id']."' AND products_id =" . $product_id);
      $q = new xenQuery();
      if(!$q->run()) return;
    $options_attr_data=$q->output();
    return     $options_attr_data['attributes_model'];

    }
?>