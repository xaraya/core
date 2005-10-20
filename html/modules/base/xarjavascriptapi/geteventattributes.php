<?php
/**
 * File: $Id$
 *
 * Handle render javascript form field tags
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @author Jason Judge
 * @todo none
 */

/**
 * Get JavaScript event attributes for a tag.
 * Returns all submitted JavaScript fragments for a position (tag) and type (list of event names)
 * as a single string. Each attribute will be returned in name="value" format. Only non-empty
 * attributes will be returned.
 * 
 * Examples:
 * Add an 'onload' trigger to the page (both examples do the same thing):
 *   <xar:base-include-javascript position="body" type="onload" code="alert('hello, world')" />
 *   xarTplAddJavaScript('body', 'onload', "alert('hello, world')");
 *
 * Get all the event attributes for all body tag events (this can be fetched in a page template):
 *   xarModAPIfunc('base', 'javascript', 'geteventjs', array('position'=>'body', 'type'=>'onload,onunload'));
 *
 * TODO: investigate whether it is worthwhile putting all these JS functions into a
 * dedicated xarJS.php script. Going through the APIs is cumbersome, and on the whole
 * the total code involved in the JavaScript is quite small.
 *
 * @author Jason Judge
 * @param $args[position] the location of the event trigger; defaults to 'body'
 * @param $args[type] the type of event trigger; several as a comma-separated list
 * @returns string
 * @return empty string
 */ 
function base_javascriptapi_geteventattributes($args)
{
    extract($args);

    // Initialise the event attributes string.
    $result = '';

    // Position and type are mandatory.
    // 'position' is the name or ID of the tag ('body', 'mytag', etc.).
    if (empty($position)) {
        // The body tag is the most likely place the events will be used.
        $position = 'body';
    }

    // 'type' is the event types ('onload', 'onmouseup', etc.), supplied
    // as a comma-separated list.
    if (empty($type)) {
        return $result;
    } else {
        $types = explode(',', strtolower($type));
    }

    foreach($types as $type) {
        $js = xarModAPIfunc(
            'base', 'javascript', 'geteventjs',
            array('position' => $position, 'type' => $type)
        );
        if (!empty($js)) {
            // Format the attribute.
            $result .= ' ' . $type . '="' . htmlspecialchars($js) . '"';
        }
    }

    // Return the result, in the form of a string containing attributes and values.
    return $result;
}

?>