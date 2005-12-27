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

  require_once(DIR_FS_INC . 'xtc_draw_selection_field.inc.php');

  function commerce_userapi_draw_radio_field($name, $value = '', $checked = false, $parameters = '') {
    return xtc_draw_selection_field($name, 'radio', $value, $checked, $parameters);
  }
 ?>