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

// Display a banner from the specified group or banner id ($identifier)
  function commerce_userapi_display_banner($action, $identifier) {
    if ($action == 'dynamic') {
      $banners_query = new xenQuery("select count(*) as count from " . TABLE_BANNERS . " where status = '1' and banners_group = '" . $identifier . "'");
      $q = new xenQuery();
      if(!$q->run()) return;
      $banners = $q->output();
      if ($banners['count'] > 0) {
        $banner = xarModAPIFunc('commerce','user','random_select',array('query' =>"select banners_id, banners_title, banners_image, banners_html_text from " . TABLE_BANNERS . " where status = '1' and banners_group = '" . $identifier . "'"));
      } else {
        return '<b>TEP ERROR! (xtc_display_banner(' . $action . ', ' . $identifier . ') -> No banners with group \'' . $identifier . '\' found!</b>';
      }
    } elseif ($action == 'static') {
      if (is_array($identifier)) {
        $banner = $identifier;
      } else {
        $banner_query = new xenQuery("select banners_id, banners_title, banners_image, banners_html_text from " . TABLE_BANNERS . " where status = '1' and banners_id = '" . $identifier . "'");
        if ($banner_query->getrows()) {
      $q = new xenQuery();
      if(!$q->run()) return;
          $banner = $q->output();
        } else {
          return '<b>TEP ERROR! (xtc_display_banner(' . $action . ', ' . $identifier . ') -> Banner with ID \'' . $identifier . '\' not found, or status inactive</b>';
        }
      }
    } else {
      return '<b>TEP ERROR! (xtc_display_banner(' . $action . ', ' . $identifier . ') -> Unknown $action parameter value - it must be either \'dynamic\' or \'static\'</b>';
    }

    if (xarModAPIFunc('commerce','user','not_null',array('arg' => $banner['banners_html_text']))) {
      $banner_string = $banner['banners_html_text'];
    } else {
      $banner_string = '<a href="' . xarModURL('commerce','user',(FILENAME_REDIRECT, 'action=banner&goto=' . $banner['banners_id']) . '" target="_blank">' . xtc_image(xarTplGetImage(DIR_WS_IMAGES . $banner['banners_image']), $banner['banners_title']) . '</a>';
    }

    xtc_update_banner_display_count($banner['banners_id']);

    return $banner_string;
  }
 ?>