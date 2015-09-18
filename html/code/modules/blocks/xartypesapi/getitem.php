<?php
/**
 * @package modules\blocks
 * @scenario soloblock
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/13.html
 */

/**
 *
 * Fetches item from the API
 * 
 * @author Chris Powis <crisp@xaraya.com>
 *  
 * @param array $args Parameter data array
 * @return boolean|array Returns item on success or false on failure
 * @throws EmptyParameterException
 * @throws BadParameterException
 */
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