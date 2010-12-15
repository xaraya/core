<?php
/**
 * Build a theme's configuration
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Build a theme's configuration
 * 
 */
function themes_admin_build()
{ 
    sys::import('modules.themes.class.initialization');
    ThemeInitialization::importConfigurations();
    return array();
} 
?>