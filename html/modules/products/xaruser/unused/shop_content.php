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

         // create smarty elements
//  $smarty = new Smarty;
  // include boxes
  require(DIR_WS_INCLUDES.'boxes.php');

  // include needed functions


     $shop_content_query=new xenQuery("SELECT
                         content_id,
                    content_title,
                    content_heading,
                    content_text,
                    content_file
                    FROM ".TABLE_CONTENT_MANAGER."
                    WHERE content_group='".$_GET['coID']."'
                    AND languages_id='".$_SESSION['languages_id']."'");
      $q = new xenQuery();
      if(!$q->run()) return;
    $shop_content_data=$q->output();

  $breadcrumb->add($shop_content_data['content_title'], xarModURL('commerce','user','content', array('coID' => $_GET['coID']));


 require(DIR_WS_INCLUDES . 'header.php');

 $data['CONTENT_HEADING'] = $shop_content_data['content_heading'];



 if ($shop_content_data['content_file']!=''){




ob_start();

if (strpos($shop_content_data['content_file'],'.txt')) echo '<pre>';
 include(DIR_FS_CATALOG.'media/content/'.$shop_content_data['content_file']);
if (strpos($shop_content_data['content_file'],'.txt')) echo '</pre>';
 $data['file'] = ob_get_contents();
ob_end_clean();

 } else {
$content_body = $shop_content_data['content_text'];
}
 $data['CONTENT_BODY'] = $content_body;

  $data['BUTTON_CONTINUE'] = '<a href="' . xarModURL('commerce','user','default') . '">' .
  xarModAPIFunc('commerce','user','image',array('src' => xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_continue.gif'),
        'alt' => IMAGE_BUTTON_CONTINUE);
. '</a>';
  $data['language'] = $_SESSION['language'];


               // set cache ID
  if (USE_CACHE=='false') {
  $smarty->caching = 0;
  return data;
  } else {
  $smarty->caching = 1;
  $smarty->cache_lifetime=CACHE_LIFETIME;
  $smarty->cache_modified_check=CACHE_CHECK;
  $cache_id = $_SESSION['language'].$shop_content_data['content_id'];
  return data;
  }
  ?>