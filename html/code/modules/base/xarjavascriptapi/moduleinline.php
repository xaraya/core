<?php
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 */

/**
 * Base JavaScript management functions
 * 
 * Include a section of inline JavaScript code in a page.
 * Used when a module needs to generate custom JS on-the-fly,
 * such as "var lang_msg = xarML('error - aborted');"
 *
 * @author Jason Judge
 * @param $args['position'] position on the page; generally 'head' or 'body'
 * @param $args['code'] the JavaScript code fragment
 * @param $args['index'] optional index in the JS array; unique identifier
 * 
 * @return boolean Returns true on success, false on failure
 */
function base_javascriptapi_moduleinline(Array $args=array())
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
