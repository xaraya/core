<?php
/** 
 * Read a block's type info.
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/*
 * @param args['module'] the module name
 * @param args['type'] the block type name
 * @return the block 'info' details (an array) or NULL if no details present
 *
 * @author Jim McDonald, Paul Rosania
 */

function blocks_userapi_read_type_info($args)
{
    extract($args);
    
    if (empty($module) && empty($type)) {
        // No identifier provided.
        throw new EmptyParameterException('module or type');
    }

    // Function to execute, to get the block info.
    $infofunc = $module . '_' . $type . 'block_info';

    if (function_exists($infofunc)) {
        return $infofunc();
    }
    
    // Load and execute the info function of the block.
    if (!xarModAPIFunc(
        'blocks', 'admin', 'load',
        array(
            'modName' => $module,
            'blockName' => $type,
            'blockFunc' => 'info'
        )
    )) {return;}

    if (function_exists($infofunc)) {
        return $infofunc();
    } else {
        // No block info function found.
        return;
    }
}

?>
