<?php
/**
 * Xaraya Themes Module
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
**/
/**
 * Get image
 * 
 * Wrapper for the <xar:img .../> template tag and xarTpl::getImage function
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @param  $args array of parameters<br/>
 *         $args[file] name of file to look for, required<br/>
 *         $args[scope] scope to look in [(theme)|module|property], required<br/>
 *         $args[module] name of module, optional when in module scope, defaults to current module<br/>
 *         $args[property] name of property, required when in property scope
 * @throws none
 * @return string url to image
**/  
function themes_userapi_getimage($args)
{   
    extract($args);
    if (empty($file)) return;
    if (empty($scope)) $scope = 'module';

    if ($scope == 'theme') {
        // @todo: support theme param to specify a theme to look in other than current/common ?
        $package = !empty($theme) ? $theme : null;
    } elseif ($scope == 'module') {
        $package = empty($module) ? xarMod::getName() : $module;
    } elseif ($scope == 'property') {
        if (empty($property)) return;
        $package = $property;
    }
    return xarTpl::getImage($file, $scope, $package);        
}
?>