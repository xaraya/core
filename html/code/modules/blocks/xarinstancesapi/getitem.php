<?php
/**
 * @package modules
 * @subpackage blocks module
 * @scenario soloblock
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * @author Chris Powis <crisp@xaraya.com>
 * @todo 
**/
function blocks_instancesapi_getitem(Array $args=array())
{
    if (empty($args)) {
        $msg = 'Missing arguments for #(1) module #(2) function #(3)()';
        $vars = array('blocks', 'instancesapi', 'getitem');
        throw new MissingParameterException($vars, $msg);
    }
    
    $types = xarMod::apiFunc('blocks', 'instances', 'getitems', $args);
    
    if (empty($types)) {
        return false;
    } elseif (count($types) > 1) {
        $msg = 'Missing arguments for #(1) module #(2) function #(3)()';
        $vars = array('blocks', 'instancesapi', 'getitem');
        throw new MissingParameterException($vars, $msg);
    } else {
        return reset($types);
    }
}
?>