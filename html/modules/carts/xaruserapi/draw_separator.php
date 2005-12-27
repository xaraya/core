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

// Output a separator either through whitespace, or with an image
  function commerce_userapi_draw_separator($args)
  {
    extract($args);
    if(!isset($image)) $image = 'pixel_black.gif';
    if(!isset($width)) $width = '100%';
    if(!isset($height)) $height = '1';
    return '<img src="' . xarTplGetImage($image) . '" alt="" width="' . $width . '" height="' . $height . '">';
  }
 ?>