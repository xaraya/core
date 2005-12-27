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

  // lets retrieve all $HTTP_GET_VARS keys and values..
  $get_params = xtc_get_all_get_params(array('reviews_id'));
  $get_params = substr($get_params, 0, -1); //remove trailing &

  $reviews_query = new xenQuery("select rd.reviews_text, r.reviews_rating, r.reviews_id, r.products_id, r.customers_name, r.date_added, r.last_modified, r.reviews_read, p.products_id, pd.products_name, p.products_image from " . TABLE_REVIEWS . " r, " . TABLE_REVIEWS_DESCRIPTION . " rd left join " . TABLE_PRODUCTS . " p on (r.products_id = p.products_id) left join " . TABLE_PRODUCTS_DESCRIPTION . " pd on (p.products_id = pd.products_id and pd.language_id = '". $_SESSION['languages_id'] . "') where r.reviews_id = '" . (int)$_GET['reviews_id'] . "' and r.reviews_id = rd.reviews_id and p.products_status = '1'");
  if (!$reviews_query->getrows()) xarRedirectResponse(xarModURL('commerce','user','reviews'));
      $q = new xenQuery();
      if(!$q->run()) return;
  $reviews = $q->output();


  $breadcrumb->add(NAVBAR_TITLE_PRODUCT_REVIEWS, xarModURL('commerce','user','product_reviews', array('x' => $get_params));

  new xenQuery("update " . TABLE_REVIEWS . " set reviews_read = reviews_read+1 where reviews_id = '" . $reviews['reviews_id'] . "'");

  $reviews_text = xarModAPIFunc('commerce','user','break_string,array('string' => htmlspecialchars($reviews['reviews_text']),'length' => 60, 'break' => '-<br>');


 require(DIR_WS_INCLUDES . 'header.php');

 $data['PRODUCTS_NAME'] = $reviews['products_name'];
 $data['AUTHOR'] = $reviews['customers_name'];
 $data['DATE'] = xarModAPIFunc('commerce','user','date_long',array('raw_date' =>$reviews['date_added']));
 $data['REVIEWS_TEXT'] = nl2br($reviews_text);
 $data['RATING'] = xtc_image(xarTplGetImage(DIR_WS_IMAGES . 'stars_' . $reviews['reviews_rating'] . '.gif'), sprintf(TEXT_OF_5_STARS, $reviews['reviews_rating']));
 $data['PRODUCTS_LINK'] = xarModURL('commerce','user','product_info', 'products_id=' . $reviews['products_id']);
 $data['BUTTON_BACK'] = '<a href="' . xarModURL('commerce','user','product_reviews', $get_params) . '">' .
 xarModAPIFunc('commerce','user','image',array('src' => xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_back.gif'),
        'alt' => IMAGE_BUTTON_BACK);
. '</a>';
 $data['BUTTON_BUY_NOW'] = '<a href="' . xarModURL('commerce','user','default', 'action=buy_now&products_id=' . $reviews['products_id']) . '">' .
 xarModAPIFunc('commerce','user','image',array('src' => xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_in_cart.gif'),
         'alt' => IMAGE_BUTTON_IN_CART);

 $data['IMAGE'] = '<a href="javascript:popupImageWindow(\''. xarModURL('commerce','user',(FILENAME_POPUP_IMAGE, 'pID=' . $reviews['products_id']).'\')">'. xtc_image(xarTplGetImage('product_images/thumbnail_images/' . $reviews['products_image']), $reviews['products_name'], PRODUCT_IMAGE_THUMBNAIL_WIDTH, PRODUCT_IMAGE_THUMBNAIL_HEIGHT, 'align="center" hspace="5" vspace="5"').'<br></a>';

  $data['language'] = $_SESSION['language'];


  // set cache ID
  if (USE_CACHE=='false') {
  $smarty->caching = 0;
  return data;
  } else {
  $smarty->caching = 1;
  $smarty->cache_lifetime=CACHE_LIFETIME;
  $smarty->cache_modified_check=CACHE_CHECK;
  $cache_id = $_SESSION['language'].$reviews['reviews_id'];
  return data;
  }
  ?>