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

 // include needed functions
// The HTML image wrapper function
  function commerce_userapi_image($src, $alt = '', $width = '', $height = '', $parameters = '')
  {
    extract($args);
    if ( (empty($src) || ($src == DIR_WS_IMAGES)) && (IMAGE_REQUIRED == 'false') ) {
      return false;
    }
    if(!isset($alt)) $alt = '';
    if(!isset($width)) $width = '';
    if(!isset($height)) $height = '';
    if(!isset($parameters)) $parameters = '';

// alt is added to the img tag even if it is null to prevent browsers from outputting
// the image filename as default
    $image = '<img src="' . strtr(trim($src), array('"' => '&quot;')) . '" border="0" alt="' . xtc_parse_input_field_data($alt, array('"' => '&quot;')) . '"';

    if (xarModAPIFunc('commerce','user','not_null',array('arg' =>$alt))) {
      $image .= ' title=" ' . strtr(trim($alt), array('"' => '&quot;')) . ' "';
    }

    if ( (CONFIG_CALCULATE_IMAGE_SIZE == 'true') && (empty($width) || empty($height)) ) {
      if ($image_size = @getimagesize($src)) {
        if (empty($width) && xarModAPIFunc('commerce','user','not_null',array('arg' =>$height))) {
          $ratio = $height / $image_size[1];
          $width = $image_size[0] * $ratio;
        } elseif (xarModAPIFunc('commerce','user','not_null',array('arg' =>(
        $width)) && empty($height)) {
          $ratio = $width / $image_size[0];
          $height = $image_size[1] * $ratio;
        } elseif (empty($width) && empty($height)) {
          $width = $image_size[0];
          $height = $image_size[1];
        }
      } elseif (IMAGE_REQUIRED == 'false') {
        return false;
      }
    }

    if (xarModAPIFunc('commerce','user','not_null',array('arg' =>$width)) && xarModAPIFunc('commerce','user','not_null',array('arg' =>$height))) {
      $image .= ' width="' . strtr(trim($width), array('"' => '&quot;')) . '" height="' . xtc_parse_input_field_data($height, array('"' => '&quot;')) . '"';
    }

    if (xarModAPIFunc('commerce','user','not_null',array('arg' =>$parameters))) $image .= ' ' . $parameters;

    $image .= '>';

    return $image;
  }
 ?>
