<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */
/**
 * Dynamic Data Version Information
 *
 * @author mikespub <mikespub@xaraya.com>
 * @param array    $args array of optional parameters<br/>
*/
function dynamicdata_userapi_get(Array $args=array())
{
    return xarMod::apiFunc('dynamicdata','user','getfield',$args);
}


?>
