<?php
/**
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */

/**
 * Get block information
 *
 * @author Jim McDonald
 * @author Paul Rosania
 * @access public
 * @param array    $args array of optional parameters<br/>
 *                 see blocks/userapi/getinfo
 * @return see blocks/userapi/getinfo
 * @deprec Jan 2004
 * @throws DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 */
function blocks_adminapi_getinfo(Array $args=array())
{
    return xarMod::apiFunc('blocks', 'user', 'getinfo', $args);
}

?>
