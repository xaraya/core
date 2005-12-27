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

  require_once(DIR_FS_INC . 'xtc_set_specials_status.inc.php');
// Auto expire products on special
  function commerce_userapi_expire_specials() {
    $specials_query = new xenQuery("select specials_id from " . TABLE_SPECIALS . " where status = '1' and now() >= expires_date and expires_date > 0");
    if ($specials_query->getrows()) {
      $q = new xenQuery();
      if(!$q->run()) return;
      while ($specials = $q->output() {
        xtc_set_specials_status($specials['specials_id'], '0');
      }
    }
  }
 ?>