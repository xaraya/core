<?php
/**
 * Read and execute a block's init function
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
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
        throw new EmptyParameterException('module or type');
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

        $classpath = 'modules/' . $module . '/xarblocks/' . $type . '.php';
        if (function_exists($initfunc)) {
            $result = $initfunc();
        } elseif (file_exists($classpath)) {
            sys::import('modules.' . $module . '.xarblocks.' . $type);
            sys::import('xaraya.structures.descriptor');
            $name = ucfirst($type) . "Block";
            $descriptor = new ObjectDescriptor(array());
            $block = new $name($descriptor);
            $result = $block->getInfo();
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
