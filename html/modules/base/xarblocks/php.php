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
    // Security
    xarSecAddSchema('base:PHPblock', 'Block title::');
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
    if(!xarSecurityCheck('ViewBase',0,'PHPblock','$blockinfo[title]::')) return;

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
?>