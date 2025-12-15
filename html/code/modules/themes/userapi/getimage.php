<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Themes\UserApi;

/**
 * themes userapi getimage function
 * @extends MethodClass<UserApi>
 */
class GetimageMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get image
     * Wrapper for the <xar:img .../> template tag and xar::tpl()->getImage function
     * @author Chris Powis <crisp@xaraya.com>
     * @access public
     * @param mixed $args array of parameters<br/>
     * $args[file] name of file to look for, required<br/>
     * $args[scope] scope to look in [(theme)|module|property], required<br/>
     * $args[module] name of module, optional when in module scope, defaults to current module<br/>
     * $args[property] name of property, required when in property scope
     * @return string url to image
     * @see UserApi::getimage()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        if (empty($file)) {
            return '';
        }
        if (empty($scope)) {
            $scope = 'module';
        }

        if ($scope == 'theme') {
            // @todo: support theme param to specify a theme to look in other than current/common ?
            $package = !empty($theme) ? $theme : null;
        } elseif ($scope == 'module') {
            $package = empty($module) ? $this->req()->getModule() : $module;
        } elseif ($scope == 'property') {
            if (empty($property)) {
                return '';
            }
            $package = $property;
        }
        return $this->tpl()->getImage($file, $scope, $package);
    }
}
