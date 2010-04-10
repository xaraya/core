<?php
/**
 * Read a block's type info.
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/* Read and execute a block's getInfo method.
 *
 * This method attempts to create an empty block class instance,
 * from which it attempts to call the getInfo method to retrieve
 * default block information
 *
 * @param args['module'] the module name
 * @param args['type'] the block type name
 * @return the block init details (an array)
 * @throws EmptyParameterException, ClassNotFoundException, FunctionNotFoundException,
 *         FileNotFoundException (via adminapi load function)
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

    // Load block file
    if (!xarMod::apiFunc('blocks', 'admin', 'load',
        array('module' => $module, 'type' => $type, 'func' => 'getInfo'))) return;

    // cascading block files - order is admin specific, block specific
    $to_check = array();
    $to_check[] = ucfirst($type) . 'BlockAdmin';    // from eg menu_admin.php
    $to_check[] = ucfirst($type) . 'Block';         // from eg menu.php
    foreach ($to_check as $className) {
        // @FIXME: class name should be unique
        if (class_exists($className)) {
            // instantiate the block instance using the first class we find
            $block = new $className(array());
            break;
        }
    }

    // make sure we instantiated a block,
    if (!isset($block)) {
        throw new ClassNotFoundException($className);
    }

    // get the defaults
    $type_info = $block->getInfo();
    // clean up
    unset($block);

    return $type_info;
}

?>
