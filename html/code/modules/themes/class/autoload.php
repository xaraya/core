<?php
/**
 * Themes Module
 *
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Autoload function for this module's classes
 */
function themes_classes_autoload($class)
{
    $class = strtolower($class);

    $class_array = array(
        // Events
        // Controllers
        
        'themeinit'            => 'modules.themes.class.init',
        'themeinitialization'  => 'modules.themes.class.initioalization',
        'ithemeinit'           => 'modules.themes.class.interfaces',
        'themesusersettings'   => 'modules.themes.class.user_settings',
        'xarcss'               => 'modules.themes.class.xarcss',
        'xarjs'                => 'modules.themes.class.xarjs',
        'xarmeta'              => 'modules.themes.class.xarmeta',
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
    xarAutoload::registerFunction('themes_classes_autoload');
} else {
    // guess you'll have to register it yourself :-)
}