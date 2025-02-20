<?php

/**
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks\BlocksApi;

use Xaraya\Modules\Blocks\MethodClass;
use Xaraya\Modules\Blocks\BlocksApi;
use BadParameterException;
use ClassNotFoundException;
use FileNotFoundException;
use FunctionNotFoundException;
use xarClassMap;
use xarMLS;
use sys;

sys::import('modules.blocks.method');
sys::import("xaraya.classmap");

/**
 * blocks blocksapi getblock function
 * @extends MethodClass<BlocksApi>
 */
class GetblockMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Gets an object from the blocks API
     * @todo how is this different from xarBlock::getObject()?
     * @author Chris Powis <crisp@xaraya.com>
     * @staticvar array $loaded Keeps track of clases that have been loaded
     * @param array<string,mixed> $args Parameter data array
     * @return object|void Object to be returned
     * @throws \BadParameterException
     * @throws \FileNotFoundException
     * @throws \ClassNotFoundException
     * @throws \FunctionNotFoundException
     * @see BlocksApi::getblock()
     */
    public function __invoke(array $args = [])
    {
        // must have a valid type
        if (empty($args['type']) || !is_string($args['type'])) {
            $invalid[] = 'type';
        }
        // if we have a module, make sure it's valid
        if (!empty($args['module']) && !is_string($args['module'])) {
            $invalid[] = 'module';
        }

        if (isset($args['block_method']) && !is_string($args['block_method'])) {
            $invalid[] = 'block_method';
        }

        if (!empty($invalid)) {
            $msg = 'Invalid #(1) for #(2) module #(3) function #(4)()';
            $vars = [join(', ', $invalid), 'blocks', 'blocksapi', 'getblock'];
            throw new BadParameterException($vars, $msg);
        }

        // keep track of classes we've already loaded
        static $loaded = [];
        $key = !empty($args['module']) ? $args['module'] . ':' . $args['type'] : $args['type'];
        if (!empty($args['block_method'])) {
            $key .= ':' . $args['block_method'];
        }
        if (isset($loaded[$key])) {
            if (isset($args['block_method'])) {
                unset($args['block_method']);
            }
            $classname = $loaded[$key];
            return new $classname($args);
        }

        // @todo use xarClassMap::findBlock() instead

        // $typeclass does not take into account possible namespace + it does not re-use what typesapi getfiles() gave
        if (!empty($args['module'])) {
            // import a block type class belonging to a module
            $basepath = sys::code() . 'modules/' . $args['module'] . '/xarblocks/';
            $baseclass = ucfirst($args['module']) . '_' . ucfirst($args['type']) . 'Block';
        } else {
            // import a solo block type class
            $basepath = sys::code() . 'blocks/';
            $baseclass = ucfirst($args['type']) . 'Block';
        }
        $typepaths = [];
        $typeclass = [];
        if (!empty($args['block_method'])) {
            // method specific class
            // basepath/type/method.php
            $typepaths[] = $basepath . $args['type'] . '/' . $args['block_method'] . '.php';
            $typeclass[] = $baseclass . ucfirst($args['block_method']);
            // basepath/type_method.php (legacy)
            $typepaths[] = $basepath . $args['type'] . '_' . $args['block_method'] . '.php';
            $typeclass[] = $baseclass . ucfirst($args['block_method']);
            if ($args['block_method'] != 'display') {
                // admin methods class
                // basepath/type/admin.php
                $typepaths[] = $basepath . $args['type'] . '/admin.php';
                $typeclass[] = $baseclass . 'Admin';
                // basepath/type_admin.php (legacy)
                $typepaths[] = $basepath . $args['type'] . '_admin.php';
                $typeclass[] = $baseclass . 'Admin';
            }
        }
        // base class
        // basepath/type/type.php
        $typepaths[] = $basepath . $args['type'] . '/' . $args['type'] . '.php';
        $typeclass[] = $baseclass;
        // basepath/type.php (legacy)
        $typepaths[] = $basepath . $args['type'] . '.php';
        $typeclass[] = $baseclass;

        $result = xarClassMap::findBlockByPath($typepaths);
        if (!empty($result['filepath']) && !empty($result['found'])) {
            $typepath = $result['filepath'];
            include_once $typepath;
            if (count($result['found']) > 1) {
                // @todo which one do we pick here?
            }
            $classname = array_key_first($result['found']);
            $args['filepath'] = $typepath;
        } else {
            // we try to get the actual $classname and $filepath here first - as input for after UPGRADE due to table change
            $oldclasses = get_declared_classes();
            foreach ($typepaths as $i => $typepath) {
                if (!file_exists($typepath)) {
                    continue;
                }
                include_once $typepath;
                $newclasses = get_declared_classes();
                $diffclasses = array_values(array_diff($newclasses, $oldclasses, ['MenuBlock', 'BasicBlock', 'BlockType']));
                // assuming new classes in namespaces only have 1 class definition per file as they should...
                if (count($diffclasses) > 0) {
                    $classname = $diffclasses[0];
                } else {
                    $classname = $typeclass[$i];
                }
                // we need to set the actual $filepath here before constructing the object
                $args['filepath'] = $typepath;
                break;
            }
        }

        if (empty($classname)) {
            throw new FileNotFoundException($typepath);
        }

        if (!class_exists($classname) || !is_subclass_of($classname, 'BasicBlock')) {
            throw new ClassNotFoundException($classname);
        }

        if (!empty($args['block_method']) && !method_exists($classname, $args['block_method'])) {
            throw new FunctionNotFoundException($args['block_method']);
        }

        // Load the block language files
        if (!xarMLS::loadTranslations($typepath)) {
            // What to do here? return doesnt seem right
            return;
        }

        if (isset($args['block_method'])) {
            unset($args['block_method']);
        }

        $object = new $classname($args);

        $loaded[$key] = $classname;

        return $object;
    }
}
