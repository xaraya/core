<?php
/**
 * @package modules\blocks
 * @subpackage blocks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/13.html
 */
/**
 * 
 * @todo 
**/

/**
 * Fetches an item from the API
 * 
 * @author Chris Powis <crisp@xaraya.com>
 * 
 * @param array $args Parameter data array
 * @return boolean|array Returns false on failure data array on success
 * @throws EmptyParameterException
 */
function blocks_instancesapi_getitem(Array $args=array())
{
    if (empty($args)) {
        $msg = 'Missing arguments for #(1) module #(2) function #(3)()';
        $vars = array('blocks', 'instancesapi', 'getitem');
        throw new EmptyParameterException($vars, $msg);
    }
    
    $types = xarMod::apiFunc('blocks', 'instances', 'getitems', $args);
    
    if (empty($types)) {
        return false;
    } elseif (count($types) > 1) {
        $msg = 'Missing arguments for #(1) module #(2) function #(3)()';
        $vars = array('blocks', 'instancesapi', 'getitem');
        throw new EmptyParameterException($vars, $msg);
    } else {
        return reset($types);
    }
}
?>