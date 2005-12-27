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

// Output a form input field
  function commerce_userapi_draw_input_field($name, $value = '', $parameters = '', $type = 'text', $reinsert_value = true) {
    $field = '<input type="' . strtr(trim($type), array('"' => '&quot;')) . '" name="' . xtc_parse_input_field_data($name, array('"' => '&quot;')) . '"';

    if ( (isset($GLOBALS[$name])) && ($reinsert_value == true) ) {
      $field .= ' value="' . strtr(trim($GLOBALS[$name]), array('"' => '&quot;')) . '"';
    } elseif (xarModAPIFunc('commerce','user','not_null',array('arg' => $value))) {
      $field .= ' value="' . strtr(trim($value), array('"' => '&quot;')) . '"';
    }

    if (xarModAPIFunc('commerce','user','not_null',array('arg' => $parameters))) $field .= ' ' . $parameters;

    $field .= '>';

    return $field;
  }
 ?>