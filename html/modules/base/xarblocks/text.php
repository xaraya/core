<?php
/**
 * File: $Id$
 *
 * Displays a Text editible Block
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
function base_textblock_init()
{
    // Security
    xarSecAddSchema('base:Textblock', 'Block title::');
}

/**
 * Block info array
 */
function base_textblock_info()
{
    return array('text_type' => 'Text',
         'text_type_long' => 'Plain Text',
         'module' => 'base',
         'func_update' => 'base_textblock_update',
         'allow_multiple' => true,
         'form_content' => false,
         'form_refresh' => false,
         'show_preview' => true);
}

/**
 * Display func.
 * @param $blockinfo array containing title,content
 */
function base_textblock_display($blockinfo)
{
    // Security Check
	if(!xarSecurityCheck('ViewBase',0,'Textblock','$blockinfo[title]:All:All')) return;

    // Get variables from content block
    $vars = unserialize($blockinfo['content']);

    $now = time();

    if ($now > $vars['expire']){
        if ($vars['expire'] != 0){
            return;
        } else {
            $blockinfo['content'] = nl2br($vars['text_content']);
            return $blockinfo;
        }
    } else {
        $blockinfo['content'] = nl2br($vars['text_content']);
        return $blockinfo;
    }

}

/**
 * Modify Function to the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function base_textblock_modify($blockinfo)
{
    // Get current content
    $vars = @unserialize($blockinfo['content']);

    // Defaults
    if (empty($vars['expire'])) {
        $vars['expire'] = 0;
    }
    // Defaults
    if (empty($vars['text_content'])) {
        $vars['text_content'] = '';
    }

    $now = time();
    if ($vars['expire'] == 0){
        $vars['expirein'] = 0;
    } else {
        $soon = $vars['expire'] - $now ;
        $sooner = $soon / 3600;
        $vars['expirein'] =  round($sooner);
    }

    $content = xarTplBlock('base', 'textAdmin', $vars);

    return $content;
}

/**
 * Updates the Block config from the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function base_textblock_update($blockinfo)
{
    list($vars['expire'],
         $vars['text_content']) = xarVarCleanFromInput('expire',
                                                       'text_content');
    // Defaults
    if (empty($vars['expire'])) {
        $vars['expire'] = 0;
    }

    if ($vars['expire'] != 0){
        $now = time();
        $vars['expire'] = $vars['expire'] + $now;
    }

    if (empty($vars['text_content'])) {
        $vars['text_content'] = '';
    }

    $blockinfo['content'] = serialize($vars);

    return $blockinfo;
}

?>
