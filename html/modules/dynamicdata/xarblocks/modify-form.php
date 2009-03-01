<?php
/**
 * Modify the form block
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * modify block settings
 */
function dynamicdata_formblock_modify($blockinfo)
{
    // Get current content
    $vars = @unserialize($blockinfo['content']);

    // Defaults
    if (empty($vars['objectid'])) {
        $vars['objectid'] = 0;
    }

    $vars['blockid'] = $blockinfo['bid'];

    // Return output
    return $vars;
}

/**
 * update block settings
 */
function dynamicdata_formblock_update($blockinfo)
{
    if (!xarVarFetch('objectid', 'id', $vars['objectid'], 0, XARVAR_NOT_REQUIRED)) {return;}

    $blockinfo['content'] = serialize($vars);

    return $blockinfo;
}
?>
