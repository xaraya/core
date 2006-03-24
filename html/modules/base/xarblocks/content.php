<?php
/**
 * Displays a Text/HTML/PHP Block
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Displays a Text/HTML/PHP Block
 *
 * @author Jason Judge
 */
function base_contentblock_init()
{
    return array(
        'content_text' => '',
        'content_type' => 'text',
        'expire' => 0,
        'hide_empty' => true,
        'custom_format' => '',
        'hide_errors' => true,
        'start_date' => '',
        'end_date' => '',
        'nocache' => 1, // don't cache by default
        'pageshared' => 1, // but if you do, share across pages
        'usershared' => 1, // and for group members
        'cacheexpire' => null
    );
}

/**
 * Block info array
 */
function base_contentblock_info()
{
    return array(
        'text_type' => 'Content',
        'text_type_long' => 'Generic Content Block',
        'module' => 'base',
        'func_update' => 'base_contentblock_update',
        'allow_multiple' => true,
        'form_content' => false,
        'form_refresh' => false,
        'show_preview' => true,
        'notes' => "content_type can be 'text', 'html', 'php' or 'data'"
    );
}

/**
 * Display func.
 * @param $blockinfo array
 * @returns $blockinfo array
 */
function base_contentblock_display($blockinfo)
{
    // Security Check
    if (!xarSecurityCheck('ViewBaseBlocks', 0, 'Block', "content:$blockinfo[title]:$blockinfo[bid]")) {return;}

    // Get variables from content block
    if (!is_array($blockinfo['content'])) {
        $blockinfo['content'] = unserialize($blockinfo['content']);
    }

    // Pointer to simplify referencing.
    $vars =& $blockinfo['content'];

    // Check if the block is within its start/end period
    $now = time();
    if (
        (!empty($vars['start_date']) && $vars['start_date'] > $now)
        || (!empty($vars['end_date']) && $vars['end_date'] < $now)
    ) {
        // Not yet started.
        return;
    }

    // Special preparation for each content type.
    if ($vars['content_type'] == 'text') {
    } elseif ($vars['content_type'] == 'php' || $vars['content_type'] == 'data') {
        // Execute the PHP code.
        ob_start();
        if (!empty($vars['hide_errors'])) {
            $result = @eval($vars['content_text']);
        } else {
            $result = eval($vars['content_text']);
        }
        $vars['content_text'] = ob_get_contents();
        ob_end_clean();

        if ($result === false && !empty($vars['hide_errors'])) {
            // If the PHP code returns a boolean 'false', then the block
            // will not be displayed. This allows the code in a PHP block
            // to suppress its own output completely.
            // Note: only works if PHP errors are hidden, since a PHP
            // error will also return a false.
            return;
        }

        // If the format is 'data' then an array can be returned, that
        // gets merged with the content for the output template.
        if ($vars['content_type'] == 'data') {
            if (is_array($result)) {
                $data = array_merge($result, $vars);
                $vars = $data;
            } else {
                // Structured data not returned.
                return;
            }
        }

        if (isset($title) && is_string($title)) {
            // The PHP code can set the title of the block (my treat).
            // Just include $title='whatever';  in the block code.
            $blockinfo['title'] = $title;
        }
    }

    if ($vars['content_type'] != 'data' && !empty($vars['hide_empty']) && trim($vars['content_text']) == '') {
        // Block is empty - hide it (but not 'data' type, as no output
        // is required for that type).
        return;
    }

    // Split the text into lines, to help rendering.
    $vars['content_lines'] = explode("\n", $vars['content_text']);

    return $blockinfo;
}

?>