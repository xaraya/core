<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @subpackage categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/147.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Autoload function for this module's properties
 */
function categories_properties_autoload($class)
{
    $class = strtolower($class);

    $class_array = array(
        'categoriesproperty'            => 'modules.categories.xarproperties.categories',
        'categorymenuproperty'          => 'modules.categories.xarproperties.categorymenu',
        'categorynavigationproperty'    => 'modules.categories.xarproperties.categorynavigation',
        'categorypickerproperty'        => 'modules.categories.xarproperties.categorypicker',
        'categorytreeproperty'          => 'modules.categories.xarproperties.categorytree',
        'celkopositionproperty'         => 'modules.categories.xarproperties.celkoposition',
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
    xarAutoload::registerFunction('categories_properties_autoload');
} else {
    // guess you'll have to register it yourself :-)
}
?>