<?php
/**
 * Blocks Module
 *
 * @package modules\blocks
 * @subpackage blocks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/13.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Autoload function for this module's classes
 */
function blocks_classes_autoload($class)
{
    $class = strtolower($class);

    $class_array = array(
        // Events
        
        // Controllers
        'blocksmodactivateobserver'            => 'modules.blocks.class.modactivate',
        'blocksmoddeactivateobserver'          => 'modules.blocks.class.moddeactivate',
        'blocksmodremoveobserver'              => 'modules.blocks.class.modremove',
    );
    
    if (isset($class_array[$class])) {
        sys::import($class_array[$class]);
        return true;
    }
    
    return false;
}

/**
 * Register this function for autoload on import
 */
if (class_exists('xarAutoload')) {
    xarAutoload::registerFunction('blocks_classes_autoload');
} else {
    // guess you'll have to register it yourself :-)
}
?>