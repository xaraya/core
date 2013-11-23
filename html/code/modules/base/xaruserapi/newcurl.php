<?php
/**
 * Return a newCurl object
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Return a new xarCurl object.
 * 
 * @param array $args Optional set of arguments
 * @return \xarCurl xarCurl Object returned
 */
function base_userapi_newcurl(Array $args=array())
{
    sys::import('modules.base.class.xarCurl');
    return new xarCurl($args);
}

?>
