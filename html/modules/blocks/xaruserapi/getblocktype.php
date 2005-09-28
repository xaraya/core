<?php
/** 
 * File: $Id$
 *
 * Get a single block type.
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @param args['tid'] block type ID (optional)
 * @param args['module'] module name (optional, but requires 'type')
 * @param args['type'] block type name (optional, but requires 'module')
 * @returns array of block types, keyed on block type ID
 *
 * @subpackage Blocks administration
 * @author Jason Judge
*/

function blocks_userapi_getblocktype($args)
{
    // Minimum parameters allowed, to fetch a single block type: tid or type.
    if (empty($args['tid']) && (empty($args['module']) || empty($args['type']))) {
        $msg = xarML('blocks_userapi_getblocktype (tid and module/type are NULL)');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
        return;
    }

    $types = xarModAPIfunc('blocks', 'user', 'getallblocktypes', $args);

    // We should have exactly one block type: throw back if not.
    if (count($types) <> 1) {return;}

    return(array_pop($types));
}

?>
