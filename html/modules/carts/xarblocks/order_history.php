<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003-4 Xaraya
//  (c) 2003 XT-Commerce
//  (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
//  (c) 2002-2003 osCommerce (oscommerce.sql,v 1.83); www.oscommerce.com
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------

/**
 * Initialise the block
 */
function commerce_order_historyblock_init()
{
    return array(
        'content_text' => '',
        'content_type' => 'text',
        'expire' => 0,
        'hide_empty' => true,
        'custom_format' => '',
        'hide_errors' => true,
        'start_date' => '',
        'end_date' => ''
    );
}

/**
 * Get information on the block ($blockinfo array)
 */
function commerce_order_historyblock_info()
{
    return array(
        'text_type' => 'Content',
        'text_type_long' => 'Generic Content Block',
        'module' => 'commerce',
        'func_update' => 'commerce_order_history_update',
        'allow_multiple' => true,
        'form_content' => false,
        'form_refresh' => false,
        'show_preview' => true,
        'notes' => "content_type can be 'text', 'html', 'php' or 'data'"
    );
}

/**
 * Display function
 * @param $blockinfo array
 * @returns $blockinfo array
 */
function commerce_order_historyblock_display($blockinfo)
{
    // Security Check
    if (!xarSecurityCheck('ViewCommerceBlocks', 0, 'Block', "content:$blockinfo[title]:All")) {return;}



//$box_content='';
  // include needed functions
  require_once(DIR_FS_INC . 'xtc_get_all_get_params.inc.php');

  if (isset($_SESSION['customer_id'])) {
    // retreive the last x products purchased
    $orders_query = new xenQuery("select distinct op.products_id from " . TABLE_ORDERS . " o, " . TABLE_ORDERS_PRODUCTS . " op, " . TABLE_PRODUCTS . " p where o.customers_id = '" . $_SESSION['customer_id'] . "' and o.orders_id = op.orders_id and op.products_id = p.products_id and p.products_status = '1' group by products_id order by o.date_purchased desc limit " . MAX_DISPLAY_PRODUCTS_IN_ORDER_HISTORY_BOX);
    if ($orders_query->getrows()) {



      $product_ids = '';
      $q = new xenQuery();
      if(!$q->run()) return;
      while ($orders = $q->output()) {
        $product_ids .= $orders['products_id'] . ',';
      }
      $product_ids = substr($product_ids, 0, -1);

      $customer_orders_string = '<table border="0" width="100%" cellspacing="0" cellpadding="1">';
      $products_query = new xenQuery("select products_id, products_name from " . TABLE_PRODUCTS_DESCRIPTION . " where products_id in (" . $product_ids . ") and language_id = '" . $_SESSION['languages_id'] . "' order by products_name");
      $q = new xenQuery();
      if(!$q->run()) return;
      while ($products = $q->output()) {
        $customer_orders_string .= 
            '  <tr>' .
            '    <td class="infoBoxContents"><a href="' . 
            xarModURL('commerce','user','product_info',array('products_id' => $products['products_id'])) . 
            '">' . $products['products_name'] . '</a></td>' .
            '    <td class="infoBoxContents" align="right" valign="top"><a href="' . 
            xarModURL('commerce','user',basename($PHP_SELF), xtc_get_all_get_params(array('action')) . 'action=cust_order&pid=' . $products['products_id']) . 
            '">' . xtc_image(xarTplGetImage('icons/cart.gif'), ICON_CART) . 
            '</a></td>' .
            '  </tr>';
      }
      $customer_orders_string .= '</table>';


    }
  }


    $box_smarty->assign('BOX_CONTENT', $customer_orders_string);

    $box_smarty->caching = 0;
    $box_smarty->assign('language', $_SESSION['language']);
    $blockinfo['content'] = $data;
    return $blockinfo;
}
?>