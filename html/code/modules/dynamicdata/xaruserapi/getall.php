<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 */
/**
 * Get all items
 * @author mikespub <mikespub@xaraya.com>
 * @param array    $args array of optional parameters<br/>
*/

function dynamicdata_userapi_getall(Array $args=array())
{
    return xarMod::apiFunc('dynamicdata','user','getitem',$args);
}

?>
