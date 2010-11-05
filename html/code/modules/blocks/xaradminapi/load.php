<?php
/**
 * @package modules
 * @subpackage blocks module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * Load a block file from file system.
 *
 * This function checks for the existence of a block file in a specified module
 * The function also checks the block class exists, and if a func is
 * specified, will check if the method named func exists in the block class
 *
 * @author Marco Canini <marco@xaraya.com>
 * @author Paul Rosania
 * @access protected
 * @param string modName the module name (deprec)
 * @param string module the module name
 * @param string blockType the name of the block (deprec)
 * @param string type the name of the block
 * @param string blockFunc the block function to load (deprec)
 * @param string func the block function to load ('modify', 'update', 'display', 'info', 'help') (deprec)
 * @param string func the block function to load ('modify', 'update', 'display', 'getInfo', 'getInit')
 * @return boolean success or failure
 * @throws EmptyParameterException, ClassNotFoundException, FunctionNotFoundException,
 *         FileNotFoundException
 */
function blocks_adminapi_load($args)
{
    // Array of block loaded flags.
    static $loaded = array();

    extract($args);

    // Legacy, remove these when the blocks module is no longer calling them
    if (isset($modName)) {$module = $modName;}
    if (isset($blockType)) {$type = $blockType;}
    if (isset($blockFunc)) {$func = $blockFunc;}
    if (!empty($blockName)) {$type = $blockName;}

    if (empty($module)) throw new EmptyParameterException('module');

    // These really are block types, as defined in the block_types.type column.
    if (empty($type)) throw new EmptyParameterException('type');

    if ((empty($func) && isset($loaded["$module:$type"])) ||
        (!empty($func) && isset($loaded["$module:$type:$func"]))) {
        // files already loaded, we're done
        return true;
    }

    // Details for the module.
    $modBaseInfo = xarMod_getBaseInfo($module);
    if (empty($modBaseInfo)) {return;}

    // Load the block file.
    // The base block file will always be loaded, and a more specific block
    // function will be loaded if available and requested.
    // @FIXME: class name should be unique
    // include the block class file if it isn't already included

    // Directory holding the block scripts.
    $blockDir = sys::code() . 'modules/' . $modBaseInfo['osdirectory'] . '/xarblocks';

    // cascading block files - order is method specific, admin specific, block specific
    // check for a method (func) specific block file, eg menu_modify.php
    $to_check = array();
    if (!empty($func)) {
        // check for method specific file, eg menu_modify.php
        $className = ucfirst($module) . '_' . ucfirst($type) . 'Block' . ucfirst($func);
        $to_check[$className] = "{$blockDir}/{$type}_{$func}.php";
        // check for generic admin file, eg menu_admin.php
        if ($func != 'display') {
            $className = ucfirst($module) . '_' . ucfirst($type) . 'BlockAdmin';
            $to_check[$className] = "{$blockDir}/{$type}_admin.php";
        }
    }
    // default block class to load
    $className = ucfirst($module) . '_' . ucfirst($type) . 'Block';
    $to_check[$className] = "{$blockDir}/{$type}.php";
    foreach ($to_check as $className => $filePath) {
        if (file_exists($filePath)) {
            // include the first file we find
            include_once($filePath);
            if (class_exists($className)) {
                // Load the block language files
                if(!xarMLSLoadTranslations($filePath)) {
                    // What to do here? return doesnt seem right
                    return;
                }
                if (!empty($func)) {
                    if (method_exists($className, $func)) {
                        $loaded["$module:$type:$func"] = 1;
                        break;
                    } else {
                        throw new FunctionNotFoundException($func);
                    }
                } else {
                    $loaded["$module:$type"] = 1;
                    break;
                }

            } elseif (xarConfigVars::get(null, 'Site.Core.LoadLegacy') == true) {
                try {
                    sys::import('xaraya.legacy.blocks.load');
                    blocks_adminapi_load_legacy($module,$type,$func,$className,$blockDir);
                    if (!empty($func)) {
                        if (method_exists($className, $func)) {
                            $loaded["$module:$type:$func"] = 1;
                            break;
                        } else {
                            throw new FunctionNotFoundException($func);
                        }
                    } else {
                        $loaded["$module:$type"] = 1;
                        break;
                    }
                } catch (Exception $e) {
                }
            } else {
                throw new ClassNotFoundException($className);
            }
        }
    }
    // check files were loaded
    if ((empty($func) && !isset($loaded["$module:$type"])) ||
        (!empty($func) && !isset($loaded["$module:$type:$func"]))) {
        // files not loaded
        throw new FileNotFoundException($filePath);
    }

    return true;
}

?>