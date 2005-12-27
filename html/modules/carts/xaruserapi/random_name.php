<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 Mario Zanier for XTcommerce
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------

  // Returns a random name, 16 to 20 characters long
  // There are more than 10^28 combinations
  // The directory is "hidden", i.e. starts with '.'
  function commerce_userapi_random_name() {
    $letters = 'abcdefghijklmnopqrstuvwxyz';
    $dirname = '.';
    $length = floor(xarModAPIFunc('commerce','user','rand',array('min' =>16,'max' =>20)));
    for ($i = 1; $i <= $length; $i++) {
     $q = floor(xarModAPIFunc('commerce','user','rand',array('min' =>1,'max' =>26)));
     $dirname .= $letters[$q];
    }
    return $dirname;
  }
 ?>