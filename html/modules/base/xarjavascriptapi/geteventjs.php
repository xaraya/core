<?php
/**
 * Base JavaScript management functions
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */

/**
 * Handle render javascript form field tags
 * Get JavaScript for a tag event.
 * Returns all submitted JavaScript fragments for a position (tag) and type (event name)
 * as a single string, each statement separated by a semi-colon.
 * 
 * Examples:
 * Add an 'onload' trigger to the page (both examples do the same thing):
 *   <xar:base-include-javascript position="body" type="onload" code="alert('hello, world')" />
 *   xarTplAddJavaScript('body', 'onload', "alert('hello, world')");
 *
 * Get all the JavaScript for the 'onload' trigger (this can be fetched in a page template):
 *   xarModAPIfunc('base', 'javascript', 'geteventjs', array('position'=>'body', 'type'=>'onload'));
 *
 * TODO: investigate whether it is worthwhile putting all these JS functions into a
 * dedicated xarJS.php script. Going through the APIs is cumbersome, and on the whole
 * the total code involved in the JavaScript is quite small.
 *
 * @author Jason Judge
 * @param $args[position] the location of the event trigger; defaults to 'body'
 * @param $args[type] the type of event trigger; e.g. 'onload', 'onmouseover'
 * @returns string
 * @return empty string
 */ 
function base_javascriptapi_geteventjs($args)
{
    extract($args);

    // Initialise the event code string.
    $result = '';

    // Position and type are mandatory.
    // 'position' is the name or ID of the tag ('body', 'mytag', etc.).
    if (empty($position)) {
        // The body tag is the most likely place the events will be used.
        $position = 'body';
    }

    // 'type' is the type of event ('onload', 'onmouseup', etc.)
    if (empty($type)) {
        return $result;
    } else {
        $type = strtolower($type);
    }

    // Concatenate the JavaScript trigger code fragments.
    // Only pick up the event type JavaScript.
    $positionjs =& xarTplGetJavaScript($position);

    if (!empty($positionjs)) {
        foreach($positionjs as $positionjs_item) {
            // Case-insenstive test so 'OnLoad' and 'onload' are seen as the same trigger.
            if (strtolower($positionjs_item['type']) == $type) {
                // Concatenate result with this JS event code.
                $result .= rtrim($positionjs_item['data'], ' \t\n\r\x0B;') . '; ';
            }
        }
    }

    // Return the result.
    // Note: this is raw JavaScript, and is not yet prepared for use as an attribute value.
    return trim($result);
}

?>
