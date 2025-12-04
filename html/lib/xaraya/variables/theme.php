<?php

/**
 * @package core\variables
 * @subpackage variables
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

use Xaraya\Services\xar;

/**
 * Interface declaration for theme vars
 *
 */
interface IxarThemeVars
{
    public static function get($scope, $name);
}


/**
 * @package core\variables
 * @subpackage variables
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @deprecated 2.4.1 not used except in kingston theme pages
 */
class xarThemeVars extends xarObject implements IxarThemeVars
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
     * @deprecated 2.9.1 use xar::theme()->getVar() instead
     */
    public static function get($scope, $name, $itemid = null)
    {
        try {
            $themeBaseInfo = xar::mod()->getBaseInfo($scope, 'theme');
            $varvalue = $themeBaseInfo['configuration'][$name];
            return $varvalue;
        } catch (Exception $e) {
            return null;
        }
    }
}
