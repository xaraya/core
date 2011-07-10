<?php
/**
 * @package core
 * @subpackage variables
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 */

/**
 * Interface declaration for theme vars
 *
 */
interface IxarThemeVars
{
    static function get   ($scope, $name);
}


class xarThemeVars extends Object implements IxarThemeVars
{
    /**
     * get a theme variable
     *
     * 
     * @param  string $scope The name of the theme
     * @param  string $name  The name of the variable
     * @return mixed The value of the variable or void if variable doesn't exist
     * @throws EmptyParameterException
     * @todo the silent spec of itemid is a bit hacky
     */
    public static function get($scope, $name, $itemid = null)
    {
        try {
            $themeBaseInfo = xarMod::getBaseInfo($scope, 'theme');
            $varvalue = $themeBaseInfo['configuration'][$name];
            return $varvalue;
        } catch (Exception $e) {
            return null;
        }
    }
}
?>
