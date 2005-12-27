<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 XT-Commerce
//   Third Party contributions:
//   Enable_Disable_Categories 1.3            Autor: Mikel Williams | mikel@ladykatcostumes.com
//  (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
//  (c) 2002-2003 osCommerce (oscommerce.sql,v 1.83); www.oscommerce.com
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------

               // create smarty elements
//  $smarty = new Smarty;
  // include boxes
  require(DIR_WS_INCLUDES.'boxes.php');
  // include needed function
  require_once(DIR_FS_INC . 'xtc_get_short_description.inc.php');


  $breadcrumb->add(NAVBAR_TITLE_PRODUCTS_NEW, xarModURL('commerce','user','products_new');

 require(DIR_WS_INCLUDES . 'header.php');



  $products_new_array = array();

  $products_new_query_raw = "select DISTINCT p.products_id, pd.products_name, p.products_image, p.products_price, p.products_tax_class_id, IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, p.products_date_added, m.manufacturers_name from " . TABLE_PRODUCTS . " p, " . TABLE_CATEGORIES . " c, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c left join " . TABLE_MANUFACTURERS . " m on p.manufacturers_id = m.manufacturers_id left join " . TABLE_PRODUCTS_DESCRIPTION . " pd on p.products_id = pd.products_id and pd.language_id = '" . $_SESSION['languages_id'] . "' left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id where c.categories_status=1 and p.products_id = p2c.products_id and c.categories_id = p2c.categories_id and products_status = '1' order by p.products_date_added DESC, pd.products_name";

  $products_new_split = new splitPageResults($products_new_query_raw, $_GET['page'], MAX_DISPLAY_PRODUCTS_NEW);

  if (($products_new_split->number_of_rows > 0)) {
   $data['NAVIGATION_BAR'] = '
   <table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="smallText">'.$products_new_split->display_count(TEXT_DISPLAY_NUMBER_OF_PRODUCTS_NEW).'</td>
            <td align="right" class="smallText">'.TEXT_RESULT_PAGE . ' ' . $products_new_split->display_links(MAX_DISPLAY_PAGE_LINKS, xtc_get_all_get_params(array('page', 'info', 'x', 'y'))).'</td>
          </tr>
        </table>';

  }

$module_content='';
  if ($products_new_split->number_of_rows > 0) {
    $products_new_query = new xenQuery($products_new_split->sql_query);
      $q = new xenQuery();
      if(!$q->run()) return;
    while ($products_new = $q->output()) {
      if (xarModAPIFunc('commerce','user','not_null',array('arg' => $products_new['specials_new_products_price']))) {
        $products_price = xarModAPIFunc('commerce','user','get_products_price',array('products_id' =>$products_new['products_id'],'price_special' =>$price_special=1,'quantity' =>$quantity=1));
      } else {
        $products_price = xarModAPIFunc('commerce','user','get_products_price',array('products_id' =>$products_new['products_id'],'price_special' =>$price_special=1,'quantity' =>$quantity=1));
      }

    $products_new['products_name'] = xarModAPIFunc('commerce','user','get_products_name',array('id' =>$products_new['products_id']));
    $products_new['products_short_description'] = xtc_get_short_description($products_new['products_id']);
    $module_content[]=array(
                            'PRODUCTS_NAME' => $products_new['products_name'],
                            'PRODUCTS_DESCRIPTION' => $products_new['products_short_description'],
                            'PRODUCTS_PRICE' => xarModAPIFunc('commerce','user','get_products_price',array('products_id' =>$products_new['products_id'],'price_special' =>$price_special=1,'quantity' =>$quantity=1)),
                            'PRODUCTS_LINK' => xarModURL('commerce','user','product_info', 'products_id=' . $products_new['products_id']),
                            'PRODUCTS_IMAGE' => xarTplGetImage('product_images/thumbnail_images/' . $products_new['products_image']),
                            'BUTTON_BUY_NOW'=>'<a href="' . xarModURL('commerce','user',(basename($PHP_SELF), xtc_get_all_get_params(array('action')) . 'action=buy_now&BUYproducts_id=' . $products_new['products_id'], 'NONSSL') . '">' .
xarModAPIFunc('commerce','user','image',array('src' => xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_buy_now.gif'),
        'alt' => TEXT_BUY . $products_new['products_name'] . TEXT_NOW));



    }
  } else {

$data['ERROR'] = TEXT_NO_NEW_PRODUCTS;

  }

    $data['language'] = $_SESSION['language'];
  $smarty->caching = 0;
  $data['module_content'] = $module_content;
  return data;
?>