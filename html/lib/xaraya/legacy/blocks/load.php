<?php
/**
 * @package core
 * @subpackage legacy
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 */
/**
 * Load legacy blocks
 */
function blocks_adminapi_load_legacy($module,$type,$func,$className,$blockDir)
{
    sys::import('xaraya.structures.containers.blocks.basicblock');
    // Function to execute, to get the block info.
    $infofunc = $module . '_' . $type . 'block_info';
    // Function to execute, to get the block init.
    $initfunc = $module . '_' . $type . 'block_init';
    // Function to execute, to display the block.
    $displayfunc = $module . '_' . $type . 'block_display';
    // Function to execute, to modify the block.
    $modifyfunc = $module . '_' . $type . 'block_modify';
    // Function to execute, to update the block.
    $updatefunc = $module . '_' . $type . 'block_update';

    // Create dummy class to map the functions
    eval("
        class $className extends BasicBlock implements iBlock
        {
            private \$blockinfo = array();
            public function __construct(Array \$data=array())
            {
                parent::__construct(\$data);
                // save the blockinfo for later
                \$this->blockinfo = \$data;
            }
            public function getInfo()
            {
                return $infofunc();
            }
            public function getInit()
            {
                return $initfunc();
            }
            public function display(Array \$args=array())
            {
                if (empty(\$args)) {
                    \$args = \$this->blockinfo;
                }
                return $displayfunc(\$args);
            }
            public function modify(Array \$args=array())
            {
                if (empty(\$args)) {
                    \$args = \$this->blockinfo;
                }
                return $modifyfunc(\$args);
            }
            public function update(Array \$args=array())
            {
                if (empty(\$args)) {
                    \$args = \$this->blockinfo;
                }
                return $updatefunc(\$args);
            }
        }
    ");

    if (empty($func)) {
        return;
    }
    switch ($func)
    {
        case 'info':
        case 'getInfo':
            if (!function_exists($infofunc)) {
                throw new FunctionNotFoundException($func);
            }
            break;
        case 'init':
        case 'getInit':
            if (!function_exists($initfunc)) {
                throw new FunctionNotFoundException($func);
            }
            break;
        case 'display':
            if (!function_exists($displayfunc)) {
                throw new FunctionNotFoundException($func);
            }
            break;
        case 'modify':
            if (!function_exists($modifyfunc)) {
                // Try to load the modify-... file
                $filePath = "{$blockDir}/modify-{$type}.php";
                if (file_exists($filePath)) {
                    // include the first file we find
                    include_once($filePath);
                    if (!function_exists($modifyfunc)) {
                        throw new FunctionNotFoundException($func);
                    }
                } else {
                    throw new FunctionNotFoundException($func);
                }
            }
            break;
        case 'update':
            if (!function_exists($updatefunc)) {
                // Try to load the modify-... file
                $filePath = "{$blockDir}/modify-{$type}.php";
                if (file_exists($filePath)) {
                    // include the first file we find
                    include_once($filePath);
                    if (!function_exists($updatefunc)) {
                        throw new FunctionNotFoundException($func);
                    }
                }
            }
            break;
        default:
            break;
    }
}

?>