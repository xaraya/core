<?php
/**
 * File: $Id$
 *
 * Includes a file into a block
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
function base_fincludeblock_init()
{
    return true;
}

/**
 * Block info array
 */
function base_fincludeblock_info()
{
    return array('text_type' => 'finclude',
         'text_type_long' => 'Simple File Include',
         'module' => 'base',
         'func_update' => 'base_fincludeblock_update',
         'allow_multiple' => true,
         'form_content' => false,
         'form_refresh' => false,
         'show_preview' => true);
}

/**
 * Display func.
 * @param $blockinfo array containing title,content
 */
function base_fincludeblock_display($blockinfo)
{
    // Security Check
    if(!xarSecurityCheck('ViewBaseBlocks',0,'Block',"finclude:$blockinfo[title]:All")) return;

    if (empty($blockinfo['title'])){
        $blockinfo['title'] = xarML('File Include');
    }

    if (empty($blockinfo['url'])){
        $blockinfo['content'] = xarML('Block has no file defined to include');
    } else {

        if (!file_exists($blockinfo['url'])) {
            $blockinfo['content'] = xarML('Block has no file defined to include');
        }

        $blockinfo['content'] = implode(file($blockinfo['url']), '');
    }

    return $blockinfo;
}

/**
 * Modify Function to the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function base_fincludeblock_modify($blockinfo)
{
    if (!empty($blockinfo['url'])) {
        $url = $blockinfo['url'];
    } else {
        $url = '';
    }

    $content = xarTplBlock('base','fincludeAdmin', array('url' => $url));

    return $content;
}

/**
 * Updates the Block config from the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function base_fincludeblock_update($blockinfo)
{
    if (!xarVarFetch('url', 'str:1', $vars['url'], '', XARVAR_NOT_REQUIRED)) return;

    if (empty($blockinfo['title'])){
        $blockinfo['title'] = xarML('File Include');
    }

    // Defaults
    if (empty($vars['url'])) {
        $vars['url'] = 'Error - No Url Specified';
    }

    $blockinfo['content'] = serialize($vars);

    return $blockinfo;
}
?>
