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
function blocks_typesapi_getitem(Array $args=array())
{
    if (empty($args)) {
        $msg = 'Missing #(1) for #(2) module #(3) function #(4)()';
        $vars = array('arguments', 'blocks', 'typesapi', 'getitem');
        throw new EmptyParameterException($vars, $msg);
    }
    
    $types = xarMod::apiFunc('blocks', 'types', 'getitems', $args);
    
    if (empty($types)) {
        return false;
    } elseif (count($types) > 1) {
        $msg = 'Invalid #(1) for #(2) module #(3) function #(4)()';
        $vars = array('arguments', 'blocks', 'typesapi', 'getitem');
        throw new BadParameterException($vars, $msg);
    } else {
        return reset($types);
    }
}
?>