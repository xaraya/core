<?php
/**
 * Text block
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/**
 * init func
 * @author Patrick Kellum
 */
function base_textblock_init()
{
    return array(
        'text_content' => '',
        'expire' => 0,
        'nocache' => 1, // don't cache by default
        'pageshared' => 1, // but if you do, share across pages
        'usershared' => 1, // and for group members
        'cacheexpire' => null
    );
}

/**
 * Block info array
 */
function base_textblock_info()
{
    return array(
        'text_type' => 'Text',
        'text_type_long' => 'Plain Text',
        'module' => 'base',
        'func_update' => 'base_textblock_update',
        'allow_multiple' => true,
        'form_content' => false,
        'form_refresh' => false,
        'show_preview' => true
    );
}

/**
 * Display func.
 * @param $blockinfo array containing title,content
 */
function base_textblock_display($blockinfo)
{
    // Security Check
    if (!xarSecurityCheck('ViewBaseBlocks', 0, 'Block', "text:$blockinfo[title]:$blockinfo[bid]")) {return;}

    // Get variables from content block
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    $now = time();
    //$args['content'] = $vars['text_content'];
    //$args['module'] = 'base';
    //$vars['text_content'] = xarModCallHooks('item', 'transform', $blockinfo['bid'], $args);

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
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    // Defaults
    if (empty($vars['expire'])) {
        $vars['expire'] = 0;
    }

    // Defaults
    if (empty($vars['text_content'])) {
        $vars['text_content'] = '';
    }

    if ($vars['expire'] == 0){
        $vars['expirein'] = 0;
    } else {
        $now = time();
        $soon = $vars['expire'] - $now ;
        $sooner = $soon / 3600;
        $vars['expirein'] = round($sooner);
    }

    $vars['blockid'] = $blockinfo['bid'];

    return $vars;
}

/**
 * Updates the Block config from the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function base_textblock_update($blockinfo)
{
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    if (!xarVarFetch('expire', 'int', $expire, 0, XARVAR_NOT_REQUIRED)) {return;}

    // TODO: check the flags that allow a posted value to override the existing value.
    if (!xarVarFetch('text_content', 'str:1', $text_content, '', XARVAR_NOT_REQUIRED)) {return;}
    $vars['text_content'] = $text_content;

    // Defaults
    if ($expire > 0) {
        $now = time();
        $vars['expire'] = $expire + $now;
    }
    
    if (!isset($vars['expire'])) {
        $vars['expire'] = 0;
    }

    $blockinfo['content'] = $vars;

    return $blockinfo;
}

?>