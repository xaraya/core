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
/*
 * Get a single block type.
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['tid'] block type ID (optional)<br/>
 *        string   $args['module'] module name (optional, but requires 'type')<br/>
 *        string   $args['type'] block type name (optional, but requires 'module')
 * @return array of block types, keyed on block type ID
 * @author Jason Judge
*/

function blocks_userapi_getblocktype(Array $args=array())
{
    // Minimum parameters allowed, to fetch a single block type: id or type.
    if (empty($args['tid']) && (empty($args['module']) || empty($args['type']))) {
        throw new BadParameterException(array('id','module','type'),'The parameters #(1) or #(2)/#(3) have not been set');
    }

    $types = xarMod::apiFunc('blocks', 'user', 'getallblocktypes', $args);

    // We should have exactly one block type: throw back if not.
    // @todo: Is this an error? If so, throw exception, if not return array()
    if (count($types) <> 1) 
    {
        return;
        throw new Exception("Blocktypes should be unique, I'm getting multiple results, strangeness!");
    }

    return(array_pop($types));
}

?>
