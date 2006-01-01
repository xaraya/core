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
function vendors_manufacturer_infoblock_init()
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
function vendors_manufacturer_infoblock_info()
{
    return array(
        'text_type' => 'Content',
        'text_type_long' => 'Generic Content Block',
        'module' => 'vendors',
        'func_update' => 'vendors_manufacturer_info_update',
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
function vendors_manufacturer_infoblock_display($blockinfo)
{
    // Security Check
    if (!xarSecurityCheck('ViewVendorsBlocks', 0, 'Block', "content:$blockinfo[title]:All")) {return;}



//$box_content='';
  if (isset($_GET['products_id'])) {
    $manufacturer_query = new xenQuery("select m.manufacturers_id, m.manufacturers_name, m.manufacturers_image, mi.manufacturers_url from " . TABLE_MANUFACTURERS . " m left join " . TABLE_MANUFACTURERS_INFO . " mi on (m.manufacturers_id = mi.manufacturers_id and mi.languages_id = '" . $_SESSION['languages_id'] . "'), " . TABLE_PRODUCTS . " p  where p.products_id = '" . (int)$_GET['products_id'] . "' and p.manufacturers_id = m.manufacturers_id");
    if ($manufacturer_query->getrows()) {
      $q = new xenQuery();
      if(!$q->run()) return;
      $manufacturer = $q->output();




      $manufacturer_info_string = '<table border="0" width="100%" cellspacing="0" cellpadding="0">';
      if (xarModAPIFunc('vendors','user','not_null',array('arg' => $manufacturer['manufacturers_image']))) $manufacturer_info_string .= '<tr><td align="center" class="infoBoxContents" colspan="2">' . xtc_image(xarTplGetImage(DIR_WS_IMAGES . $manufacturer['manufacturers_image']), $manufacturer['manufacturers_name']) . '</td></tr>';
      if (xarModAPIFunc('vendors','user','not_null',array('arg' => $manufacturer['manufacturers_url']))) $manufacturer_info_string .= '<tr><td valign="top" class="infoBoxContents">-&nbsp;</td><td valign="top" class="infoBoxContents"><a href="' . xarModURL('vendors','user','redirect',array('action' => 'manufacturer','manufacturers_id' => $manufacturer['manufacturers_id'])) . '" target="_blank">' . sprintf(BOX_MANUFACTURER_INFO_HOMEPAGE, $manufacturer['manufacturers_name']) . '</a></td></tr>';
      $manufacturer_info_string .= '<tr><td valign="top" class="infoBoxContents">-&nbsp;</td><td valign="top" class="infoBoxContents"><a href="' . xarModURL('vendors','user','default', 'manufacturers_id=' . $manufacturer['manufacturers_id']) . '">' . BOX_MANUFACTURER_INFO_OTHER_PRODUCTS . '</a></td></tr>' .
                                   '</table>';


    }
  }

    $box_smarty->assign('BOX_TITLE', BOX_HEADING_MANUFACTURER_INFO);
    $box_smarty->assign('BOX_CONTENT', $manufacturer_info_string);

    $box_smarty->assign('language', $_SESSION['language']);
/*          // set cache ID
  if (USE_CACHE=='false') {
  $box_smarty->caching = 0;
  $box_manufacturers_info= $box_smarty->fetch(CURRENT_TEMPLATE.'/boxes/box_manufacturers_info.html');
  } else {
  $box_smarty->caching = 1;
  $box_smarty->cache_lifetime=CACHE_LIFETIME;
  $box_smarty->cache_modified_check=CACHE_CHECK;
  $cache_id = $_SESSION['language'].$_GET['products_id'];
  $box_manufacturers_info= $box_smarty->fetch(CURRENT_TEMPLATE.'/boxes/box_manufacturers_info.html',$cache_id);
  }
*/
    $blockinfo['content'] = $data;
    return $blockinfo;
}
?>