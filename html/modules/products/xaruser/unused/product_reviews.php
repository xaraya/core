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
  $get_params = xtc_get_all_get_params();
  $get_params_back = xtc_get_all_get_params(array('reviews_id')); // for back button
  $get_params = substr($get_params, 0, -1); //remove trailing &
  if (xarModAPIFunc('commerce','user','not_null',array('arg' => $get_params_back))) {
    $get_params_back = substr($get_params_back, 0, -1); //remove trailing &
  } else {
    $get_params_back = $get_params;
  }

  $product_info_query = new xenQuery("select pd.products_name from " . TABLE_PRODUCTS_DESCRIPTION . " pd left join " . TABLE_PRODUCTS . " p on pd.products_id = p.products_id where pd.language_id = '" . $_SESSION['languages_id'] . "' and p.products_status = '1' and pd.products_id = '" . (int)$_GET['products_id'] . "'");
  if (!$product_info_query->getrows()) xarRedirectResponse(xarModURL('commerce','user','reviews'));
      $q = new xenQuery();
      if(!$q->run()) return;
  $product_info = $q->output();


  $breadcrumb->add(NAVBAR_TITLE_PRODUCT_REVIEWS, xarModURL('commerce','user','product_reviews', array('x' => $get_params));

 require(DIR_WS_INCLUDES . 'header.php');

 $data['PRODUCTS_NAME'] = $product_info['products_name'];


$data_reviews=array();
  $reviews_query = new xenQuery("select reviews_rating, reviews_id, customers_name, date_added, last_modified, reviews_read from " . TABLE_REVIEWS . " where products_id = '" . (int)$_GET['products_id'] . "' order by reviews_id DESC");
  if ($reviews_query->getrows()) {
    $row = 0;
      $q = new xenQuery();
      if(!$q->run()) return;
    while ($reviews = $q->output()) {
      $row++;
      $data_reviews[]=array(
                           'ID' => $reviews['reviews_id'],
                           'AUTHOR'=> '<a href="' . xarModURL('commerce','user','product_reviews'_INFO, $get_params . '&reviews_id=' . $reviews['reviews_id']) . '">' . $reviews['customers_name'] . '</a>',
                           'DATE'=>xarModAPIFunc('commerce','user','date_short',array('raw_date' =>$reviews['date_added'])),
                           'RATING'=>xtc_image(xarTplGetImage(DIR_WS_IMAGES . 'stars_' . $reviews['reviews_rating'] . '.gif'), sprintf(TEXT_OF_5_STARS, $reviews['reviews_rating'])),
                           'TEXT'=>$reviews['reviews_text']);

    }
  }
  $data['module_content'] = $data_reviews;
  $data['BUTTON_BACK'] = '<a href="' . xarModURL('commerce','user','product_info', $get_params_back) . '">' .
xarModAPIFunc('commerce','user','image',array('src' => xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_back.gif'),
        'alt' =>IMAGE_BUTTON_BACK );
  . '</a>';
  $data['BUTTON_WRITE'] = '<a href="' . xarModURL('commerce','user','product_reviews'_WRITE, $get_params) . '">' .
xarModAPIFunc('commerce','user','image',array('src' => xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_write_review.gif'),
        'alt' => IMAGE_BUTTON_WRITE_REVIEW);
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
  $cache_id = $_SESSION['language'].$_GET['products_id'];
  return data;
  }
  ?>