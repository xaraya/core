<?php
/**
 * @package modules\blocks
 * @scenario soloblock
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */

/**
 * Gets an object from the api
 * 
 * @author Chris Powis <crisp@xaraya.com>
 * 
 * @param array $args Parameter data array
 * @return type Data array
 */
function blocks_typesapi_getobject(Array $args=array())
{
    return xarMod::apiFunc('blocks', 'blocks', 'getobject', $args);    
}
?>