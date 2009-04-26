<?php
/**
 * HTML block
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Block init - holds security.
 * @author Patrick Kellum
 */
function base_htmlblock_init()
{
    return array('html_content' => '',
                 'expire' => 0,
                 'nocache' => 1, // don't cache by default
                 'pageshared' => 1, // but if you do, share across pages
                 'usershared' => 1, // and for group members
                 'cacheexpire' => null);
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
    if (!xarSecurityCheck('ViewBaseBlocks', 0, 'Block', "html:$blockinfo[title]:$blockinfo[bid]")) {return;}

    // Get variables from content block
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    $now = time();
    // Transform Output
    //$args['content'] = $vars['html_content'];
    //$args['module'] = 'base';
    //$vars['html_content'] = xarModCallHooks('item', 'transform', $blockinfo['bid'], $args);

    if (isset($vars['expire']) && $now > $vars['expire']){
        if ($vars['expire'] != 0){
            return;
        } 
    }
    if(isset($vars['html_content'])) {
        $blockinfo['content'] = $vars['html_content'];
    } else {
        $blockinfo['content'] = '';
    }
    return $blockinfo;
}

/**
 * Modify Function to the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function base_htmlblock_modify($blockinfo)
{
    // Get current content
    if (!is_array($blockinfo['content'])) {
        $vars = @unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

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
    return $vars;

}

/**
 * Updates the Block config from the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function base_htmlblock_update($blockinfo)
{
   if (!xarVarFetch('expire', 'str:1', $vars['expire'], 0, XARVAR_NOT_REQUIRED)) {return;}
   if (!xarVarFetch('html_content', 'str:1', $vars['html_content'], '', XARVAR_NOT_REQUIRED)) {return;}

    // Defaults
    if ($vars['expire'] != 0){
        $now = time();
        $vars['expire'] = $vars['expire'] + $now;
    }

    $blockinfo['content'] = $vars;

    return $blockinfo;
}

?>
