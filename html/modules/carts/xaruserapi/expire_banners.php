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

require_once(DIR_FS_INC . 'xtc_set_banner_status.inc.php');

// Auto expire banners
  function commerce_userapi_expire_banners() {
    $banners_query = new xenQuery("select b.banners_id, b.expires_date, b.expires_impressions, sum(bh.banners_shown) as banners_shown from " . TABLE_BANNERS . " b, " . TABLE_BANNERS_HISTORY . " bh where b.status = '1' and b.banners_id = bh.banners_id group by b.banners_id");
    if ($banners_query->getrows()) {
      $q = new xenQuery();
      if(!$q->run()) return;
      while ($banners = $q->output()) {
        if (xarModAPIFunc('commerce','user','not_null',array('arg' => $banners['expires_date']))) {
          if (date('Y-m-d H:i:s') >= $banners['expires_date']) {
            xtc_set_banner_status($banners['banners_id'], '0');
          }
        } elseif (xarModAPIFunc('commerce','user','not_null',array('arg' => $banners['expires_impressions']))) {
          if ($banners['banners_shown'] >= $banners['expires_impressions']) {
            xtc_set_banner_status($banners['banners_id'], '0');
          }
        }
      }
    }
  }
 ?>
