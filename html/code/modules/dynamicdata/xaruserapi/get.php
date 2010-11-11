<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 */
/**
 * Dynamic Data Version Information
 *
 * @author mikespub <mikespub@xaraya.com>
*/
function dynamicdata_userapi_get(Array $args=array())
{
    return xarMod::apiFunc('dynamicdata','user','getfield',$args);
}


?>