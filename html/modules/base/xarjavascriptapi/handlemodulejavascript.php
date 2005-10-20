<?php
/**
 * File: $Id$
 *
 * Handle <xar:base-include-javascript ..> form field tags
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
 * Handle <xar:base-include-javascript ...> form field tags
 * Format : <xar:base-include-javascript definition="$definition" /> with $definition an array
 *       or <xar:base-include-javascript filename="thisname.js" module="modulename" position="head|body|whatever"/>
 * Default module is the module in which the tag is called and default position is 'head'; filename is mandatory.
 *
 * Example:
 * The following tag is included in an 'articles' template. The file 'myfile.js'
 * can be located in either themes/<current>/modules/articles/includes or
 * modules/articles/xartemplates/includes:
 *
 *    <xar:base-include-javascript filename="myfile.js"/>
 *
 * @author Jason Judge
 * @param $args array containing the form field definition or the type, name, value, ...
 * @returns string
 * @return empty string
 */ 
function base_javascriptapi_handlemodulejavascript($args)
{
    extract($args);

    // The whole lot can be passed in as an array.
    if (isset($definition) && is_array($definition)) {
        extract($definition);
    }

    // Set some defaults - only attribute 'filename' is mandatory.
    if (empty($module)) {
        // No module name is supplied, default the module from the
        // current template module (not the current executing module).
        $module = '$_bl_module_name';
    } else {
        // The module name is supplied.
        $module = '\'' . addslashes($module) . '\'';
    }

    if (empty($position)) {
        $position = 'head';
    } else {
        $position = addslashes($position);
    }

    if (!empty($code) && !empty($type)) {
        // If the 'code' attribute has been passed in, then some inline code
        // has been supplied - we don't need to read anything from a file then.
        $out = "xarTplAddJavaScript('$position', '$type', \"$code\");";
    } elseif (!empty($filename)) {
        // Return the code to call up the javascript file.
        // Only the file version is supported for now.
        $out = "xarModAPIFunc("
            . "'base', 'javascript', 'modulefile', "
            . "array('module'=>" . $module
            . ", 'filename'=>'" . addslashes($filename)
            . "', 'position'=>'$position')); ";
    } else {
        $out = '';
    }

    return $out;
}

?>