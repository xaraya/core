<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003-4 Xaraya
//  (c) 2003 XT-Commerce
//   Third Party contributions:
//   Customers Status v3.x (c) 2002-2003 Copyright Elari elari@free.fr | www.unlockgsm.com/dload-osc/ | CVS : http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/elari/?sortby=date#dirlist
//  (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
//  (c) 2002-2003 osCommerce (oscommerce.sql,v 1.83); www.oscommerce.com
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------

/**
 * Initialise the block
 */
function commerce_customers_statusblock_init()
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
function commerce_customers_statusblock_info()
{
    return array(
        'text_type' => 'Content',
        'text_type_long' => 'Generic Content Block',
        'module' => 'commerce',
        'func_update' => 'commerce_customers_status_update',
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
function commerce_customers_statusblock_display($blockinfo)
{
    // Security Check
    if (!xarSecurityCheck('ViewCommerceBlocks', 0, 'Block', "content:$blockinfo[title]:All")) {return;}



//$box_content='';


  if ($customer_status_value['customers_status_public'] == 1 ) {
    $info_box_contents = array();
    $info_box_contents[] = array('align' => 'left',
                                 'text'  => BOX_HEADING_CUSTOMERS_STATUS_BOX);

    new infoBoxHeading($info_box_contents, false, false);

    $info_box_contents = array();
    $info_box_contents[] = array('align' => 'center',
                                 'text'  => TEXT_INFO_CSNAME . '<br>' . $customer_status_value['customers_status_name']);

    if ($customer_status_value['customers_status_discount'] > 0 ) {
    $info_box_contents[] = array('align' => 'center',
                                 'text'  => TABLE_HEADING_DISCOUNT . ':' . $customer_status_value['customers_status_discount'] . '%.<br>' . SUB_TITLE_OT_DISCOUNT . $customer_status_value['customers_status_ot_discount'] . '%');
    }

    if ($customer_status_value['customers_status_show_price'] == 1) {
      if ($customer_status_value['customers_status_show_price_tax'] == 1) {
        $info_box_contents[] = array('align' => 'center',
                                     'text'  => TEXT_INFO_SHOW_PRICE_WITH_TAX_YES);
      } else {
        $info_box_contents[] = array('align' => 'center',
                                    'text'  => TEXT_INFO_SHOW_PRICE_WITH_TAX_NO);
      }
    } else {
      $info_box_contents[] = array('align' => 'center',
                                   'text'  => TEXT_INFO_SHOW_PRICE_NO);
    }
    new infoBox($info_box_contents);
  }
    $smarty->assign('BOX_TITLE', BOX_HEADING_CUSTOMERS_STATUS_BOX);
    $smarty->assign('BOX_CONTENT', $box_content);


    $box_add_a_quickie= $smarty->fetch(CURRENT_TEMPLATE.'/boxes/box.html');
    $blockinfo['content'] = $data;
    return $blockinfo;
}
?>