<?php
/**
 * Base JavaScript management functions
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Handle render javascript form field tags
 * Handle <xar:base-include-javascript ...> form field tags
 * Format : <xar:base-render-javascript definition="$definition" /> with $definition an array
 *       or <xar:base-render-javascript position="head|body|whatever|" type="code|src|whatever|"/>
 * Default position is ''; default type is ''.
 * Typical use in the head section is: <xar:base-render-javascript position="head"/>
 *
 * @author Jason Judge
 * @param $args array containing the form field definition or the type, position, ...
 * @returns string
 * @return empty string
 */ 
function base_javascriptapi_handlerenderjavascript($args)
{
    extract($args);

    // The whole lot can be passed in as an array.
    if (isset($definition) && is_array($definition)) {
        extract($definition);
    }

    if (empty($position)) {$position = '';}
    if (empty($type)) {$type = '';}

    // Send the JS through the template to display.
    return "echo trim(xarTplModule('base', 'javascript', 'render', array('javascript'=>xarTplGetJavaScript('$position'), 'position'=>'$position', 'type'=>'$type')));";
}

?>
