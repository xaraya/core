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
function commerce_product_notificationsblock_init()
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
function commerce_product_notificationsblock_info()
{
    return array(
        'text_type' => 'Content',
        'text_type_long' => 'Generic Content Block',
        'module' => 'commerce',
        'func_update' => 'commerce_product_notifications_update',
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
function commerce_product_notificationsblock_display($blockinfo)
{
    // Security Check
    if (!xarSecurityCheck('ViewCommerceBlocks', 0, 'Block', "content:$blockinfo[title]:All")) {return;}

    //$box_content='';

    if (isset($_GET['products_id'])) {

        if (isset($_SESSION['customer_id'])) {
            $check_query = new xenQuery("select count(*) as count from " . TABLE_PRODUCTS_NOTIFICATIONS . " where products_id = '" . (int)$_GET['products_id'] . "' and customers_id = '" . $_SESSION['customer_id'] . "'");
            $q = new xenQuery();
            if(!$q->run()) return;
            $check = $q->output();

            $notification_exists = (($check['count'] > 0) ? true : false);
        } else {
            $notification_exists = false;
        }

        $info_box_contents = array();
        if ($notification_exists == true) {
            $box_content =
                '<table border="0" cellspacing="0" cellpadding="2"><tr><td class="infoBoxContents"><a href="' . 
                xarModURL('commerce','user',basename($PHP_SELF), xtc_get_all_get_params(array('action')) . 'action=notify_remove', $request_type) . 
                '">' . xtc_image(xarTplGetImage(DIR_WS_IMAGES . 'box_products_notifications_remove.gif'), IMAGE_BUTTON_REMOVE_NOTIFICATIONS) . 
                '</a></td><td class="infoBoxContents"><a href="' . 
                xarModURL('commerce','user',basename($PHP_SELF), xtc_get_all_get_params(array('action')) . 'action=notify_remove', $request_type) . 
                '">' . sprintf(BOX_NOTIFICATIONS_NOTIFY_REMOVE, xarModAPIFunc('commerce','user','get_products_name',array('id' =>$_GET['products_id']))) .
                '</a></td></tr></table>';
        } else {
            $box_content = 
                '<table border="0" cellspacing="0" cellpadding="2"><tr><td class="infoBoxContents"><a href="' . 
                xarModURL('commerce','user',basename($PHP_SELF), xtc_get_all_get_params(array('action')) . 'action=notify', $request_type) . 
                '">' . xtc_image(xarTplGetImage(DIR_WS_IMAGES . 'box_products_notifications.gif'), IMAGE_BUTTON_NOTIFICATIONS) . 
                '</a></td><td class="infoBoxContents"><a href="' . 
                xarModURL('commerce','user',basename($PHP_SELF), xtc_get_all_get_params(array('action')) . 'action=notify', $request_type) . 
                '">' . sprintf(BOX_NOTIFICATIONS_NOTIFY, xarModAPIFunc('commerce','user','get_products_name',array('id' =>$_GET['products_id']))) .
                '</a></td></tr></table>';
        }
    }

    $box_smarty->assign('BOX_CONTENT', $box_content);
    $box_smarty->assign('language', $_SESSION['language']);

    /*   // set cache ID
    if (USE_CACHE=='false') {
         $box_smarty->caching = 0;
         $box_notifications= $box_smarty->fetch(CURRENT_TEMPLATE.'/boxes/box_notifications.html');
    } else {
         $box_smarty->caching = 1;
         $box_smarty->cache_lifetime=CACHE_LIFETIME;
         $box_smarty->cache_modified_check=CACHE_CHECK;
         $cache_id = $_SESSION['language'].$_GET['products_id'];
         $box_notifications= $box_smarty->fetch(CURRENT_TEMPLATE.'/boxes/box_notifications.html',$cache_id);
    }
    */

    $blockinfo['content'] = $data;
    return $blockinfo;
}

?>