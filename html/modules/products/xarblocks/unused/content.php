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
function commerce_contentblock_init()
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
function commerce_contentblock_info()
{
    return array(
        'text_type' => 'Content',
        'text_type_long' => 'Generic Content Block',
        'module' => 'commerce',
        'func_update' => 'commerce_content_update',
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
function commerce_contentblock_display($blockinfo)
{
    // Security Check
    if (!xarSecurityCheck('ViewCommerceBlocks', 0, 'Block', "content:$blockinfo[title]:All")) {return;}



$content_string='';
$content_query=new xenQuery("SELECT
                    content_id,
                    categories_id,
                    parent_id,
                    content_title,
                    content_group
                    FROM ".TABLE_CONTENT_MANAGER."
                    WHERE languages_id='".$_SESSION['languages_id']."'
                    and file_flag=1 and content_status=1");
      $q = new xenQuery();
      if(!$q->run()) return;
 while ($content_data=$q->output()) {

 $content_string .= '<a href="' . xarModURL('commerce','user',(FILENAME_CONTENT,'coID='.$content_data['content_group']) . '">' . $content_data['content_title'] . '</a><br>';
}
if ($content_string=='') return;




    $box_smarty->assign('BOX_TITLE', BOX_HEADING_CONTENT);
    $box_smarty->assign('BOX_CONTENT', $content_string);
    $box_smarty->assign('language', $_SESSION['language']);
      // set cache ID
  if (USE_CACHE=='false') {
  $box_smarty->caching = 0;
  $box_content= $box_smarty->fetch(CURRENT_TEMPLATE.'/boxes/box_content.html');
  } else {
  $box_smarty->caching = 1;
  $box_smarty->cache_lifetime=CACHE_LIFETIME;
  $box_smarty->cache_modified_check=CACHE_CHECK;
  $cache_id = $_SESSION['language'];
  $box_content= $box_smarty->fetch(CURRENT_TEMPLATE.'/boxes/box_content.html',$cache_id);
  }

    $blockinfo['content'] = $data;
    return $blockinfo;
}
?>