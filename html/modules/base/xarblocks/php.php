<?php
/**
 * File: $Id$
 *
 * Displays a PHP Block
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage Base Module
 * @author Patrick Kellum
*/

/**
 * init func
 */
function base_phpblock_init()
{
    return true;
}

/**
 * Block info array
 */
function base_phpblock_info()
{
    return array('text_type' => 'PHP',
         'text_type_long' => 'PHP Script',
         'module' => 'base',
         'allow_multiple' => true,
         'form_content' => true,
         'form_refresh' => false,
         'show_preview' => true);
}

/**
 * Display func.
 * @param $blockinfo array containing title,content
 */
function base_phpblock_display($blockinfo)
{
    // Security Check
    if(!xarSecurityCheck('ViewBaseBlocks',0,'Block',"php:$blockinfo[title]:All")) return;

    if (empty($blockinfo['title'])){
        $blockinfo['title'] = xarML('PHP Block');
    }

    ob_start();
    print eval($blockinfo['content']);
    $blockinfo['content'] = ob_get_contents();
    ob_end_clean();

    if (empty($blockinfo['content'])){
        $blockinfo['content'] = xarML('Content is empty');
    }

    return $blockinfo;
}

/**
 * Modify Function to the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function base_phpblock_modify($blockinfo)
{
    // Get current content
    $vars = @unserialize($blockinfo['content']);

    $vars['blockid'] = $blockinfo['bid'];
    $content = xarTplBlock('base', 'phpAdmin', $vars);

    return $content;
}
?>