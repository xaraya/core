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

// Output a selection field - alias function for xtc_draw_checkbox_field() and xtc_draw_radio_field()

  function commerce_userapi_draw_selection_field($name, $type, $value = '', $checked = false, $parameters = '') {
    $selection = '<input type="' . strtr(trim($type), array('"' => '&quot;')) . '" name="' . strtr(trim($name), array('"' => '&quot;')) . '"';

    if (xarModAPIFunc('commerce','user','not_null',array('arg' => $value))) $selection .= ' value="' . strtr(trim($value), array('"' => '&quot;')) . '"';

    if ( ($checked == true) || ($GLOBALS[$name] == 'on') || ( (isset($value)) && ($GLOBALS[$name] == $value) ) ) {
      $selection .= ' CHECKED';
    }

    if (xarModAPIFunc('commerce','user','not_null',array('arg' => $parameters))) $selection .= ' ' . $parameters;

    $selection .= '>';

    return $selection;
  }
 ?>