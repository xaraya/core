<?php
/**
 * File: $Id$
 *
 * Displays a HTML editible Block
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage Base Module
 * @author Patrick Kellum
*/

/**
 * Block init - holds security.
 */
function base_htmlblock_init()
{
    return true;
}

/**
 * block information array
 */
function base_htmlblock_info()
{
    return array('text_type' => 'HTML',
         'text_type_long' => 'HTML',
         'module' => 'base',
         'func_update' => 'base_htmlblock_update',
         'allow_multiple' => true,
         'form_content' => false,
         'form_refresh' => false,
         'show_preview' => true);

}

/**
 * Display func.
 * @param $blockinfo array containing title,content
 */
function base_htmlblock_display($blockinfo)
{
    // Security Check
    if(!xarSecurityCheck('ViewBaseBlocks',0,'Block',"html:$blockinfo[title]:All")) return;

    // Get variables from content block
    $vars = unserialize($blockinfo['content']);

    $now = time();

    if ($now > $vars['expire']){
        if ($vars['expire'] != 0){
            return;
        } else {
            $blockinfo['content'] = xarVarPrepHTMLDisplay($vars['html_content']);
            return $blockinfo;
        }
    } else {
        $blockinfo['content'] = xarVarPrepHTMLDisplay($vars['html_content']);
        return $blockinfo;
    }

}

/**
 * Modify Function to the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function base_htmlblock_modify($blockinfo)
{
    // Get current content
    $vars = @unserialize($blockinfo['content']);

    // Defaults
    if (empty($vars['expire'])) {
        $vars['expire'] = 0;
    }
    // Defaults
    if (empty($vars['html_content'])) {
        $vars['html_content'] = '';
    }

    $now = time();
    if ($vars['expire'] == 0){
        $vars['expirein'] = 0;
    } else {
        $soon = $vars['expire'] - $now ;
        $sooner = $soon / 3600;
        $vars['expirein'] =  round($sooner);
    }

    $vars['blockid'] = $blockinfo['bid'];
    $content = xarTplBlock('base', 'htmlAdmin', $vars);

    return $content;
}

/**
 * Updates the Block config from the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function base_htmlblock_update($blockinfo)
{
   if (!xarVarFetch('expire','str:1', $vars['expire'], 0, XARVAR_NOT_REQUIRED)) {return;}
   if (!xarVarFetch('html_content','str:1', $vars['html_content'], '', XARVAR_NOT_REQUIRED)) {return;}

    // Defaults
    if ($vars['expire'] != 0){
        $now = time();
        $vars['expire'] = $vars['expire'] + $now;
    }

    $blockinfo['content'] = serialize($vars);

    return $blockinfo;
}

?>
