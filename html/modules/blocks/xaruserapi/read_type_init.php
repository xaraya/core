<?php
/**
 * Read and execute a block's init function
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */
/* Read and execute a block's init function.
 * @param args['module'] the module name
 * @param args['type'] the block type name
 * @return the block 'info' details (an array) or NULL if no details present
 *
 * @author Jim McDonald, Paul Rosania
 */

function blocks_userapi_read_type_init($args)
{
    extract($args);
    
    if (empty($module) && empty($type)) {
        // No identifier provided.
        $msg = xarML('Invalid parameter: missing module or type');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    // Function to execute, to get the block info.
    $initfunc = $module . '_' . $type . 'block_init';

    if (function_exists($initfunc)) {
        $result = $initfunc();
    } else {
        // Load and execute the info function of the block.
        if (!xarModAPIFunc(
            'blocks', 'admin', 'load',
            array(
                'modName' => $module,
                'blockName' => $type,
                'blockFunc' => 'init'
            )
        )) {return;}

        if (function_exists($initfunc)) {
            $result = $initfunc();
        } else {
            // No block info function found.
            $result = NULL;
        }
    }

    // Discard any boolean value returned by the init function,
    // as those are legacy values with no meaning now.
    if (is_bool($result)) {
        $result = NULL;
    }

    return $result;
}

?>