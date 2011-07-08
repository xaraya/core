<?php
/**
 * Read and execute a block's init function
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/* Read and execute a block's init function.
 *
 * This method attempts to create an empty block class instance,
 * from which it attempts to call the getInit method to retrieve
 * default block type information
 *
 * @param array    $args array of optional parameters<br/>
 *        string   $args['module'] the module name<br/>
 *        string   $args['type'] the block type name
 * @return array the block init details
 * @throws EmptyParameterException, ClassNotFoundException, FunctionNotFoundException,
 *         FileNotFoundException (via adminapi load function)
 *
 * @author Jim McDonald
 * @author Paul Rosania
 */

function blocks_userapi_read_type_init(Array $args=array())
{
    extract($args);

    if (empty($module) && empty($type)) {
        // No identifier provided.
        throw new EmptyParameterException('module or type');
    }

    if (!xarMod::apiFunc('blocks', 'admin', 'load',
        array('module' => $module, 'type' => $type, 'func' => 'getInit'))) return;

    // cascading block files - order is admin specific, block specific
    $to_check = array();
    $to_check[] = ucfirst($module) . '_' . ucfirst($type) . 'BlockAdmin';    // from eg menu_admin.php
    $to_check[] = ucfirst($module) . '_' . ucfirst($type) . 'Block';         // from eg menu.php
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

    // @TODO: cache type_init for each block type
    // instantiate an empty block class of this module type
    $block = new $className(array());
    // get the defaults
    $type_init = $block->getInit();
    // clean up
    unset($block);

    return $type_init;
}
?>