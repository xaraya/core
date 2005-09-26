<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */


/**
 * Base JavaScript management functions
 * Include a section of inline JavaScript code in a page.
 * Used when a module needs to generate custom JS on-the-fly,
 * such as "var lang_msg = xarML('error - aborted');"
 *
 * @author Jason Judge
 * @param $args['position'] position on the page; generally 'head' or 'body'
 * @param $args['code'] the JavaScript code fragment
 * @param $args['index'] optional index in the JS array; unique identifier
 * @returns true=success; null=fail
 * @return boolean
 */
function base_javascriptapi_moduleinline($args)
{
    extract($args);

    if (empty($code)) {
        return;
    }

    // Use a hash index to prevent the same JS code fragment
    // from being included more than once.
    if (empty($index)) {
        $index = md5($code);
    }

    // Default the position to the head.
    if (empty($position)) {
        $position = 'head';
    }

    return xarTplAddJavaScript($position, 'code', $code, $index);
}

?>