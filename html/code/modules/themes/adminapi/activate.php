<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Themes\AdminApi;
use EmptyParameterException;
use xarTheme;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes adminapi activate function
 * @extends MethodClass<AdminApi>
 */
class ActivateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Activate a theme if it has an active function, otherwise just set the state to active
     * @author Marty Vance
     * @access public
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['regid'] theme's registered id
     * string   $args['name'] theme's name
     * @return bool true on success, false on failure
     * @throws \EmptyParameterException
     * @see AdminApi::activate()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        // Argument check
        if (isset($name)) {
            $regid = xarTheme::getRegID($name);
        }
        if (!isset($regid)) {
            throw new EmptyParameterException('regid');
        }

        $themeInfo = xarTheme::getInfo($regid);

        // Update state of theme
        $res = $adminapi->setstate(['regid' => $regid,
            'state' => xarTheme::STATE_ACTIVE]);

        return true;
    }
}
