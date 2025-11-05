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
use Exception;
use ThemeNotFoundException;
use ixarTheme;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes adminapi initialise function
 * @extends MethodClass<AdminApi>
 */
class InitialiseMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Initialise a theme
     * @author Marty Vance
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['regid'] registered theme id
     * string   $args['name'] theme's name
     * @return bool true on success, false on failure
     * @throws \EmptyParameterException
     * @see AdminApi::initialise()
     */
    public function __invoke(array $args = [])
    {

        extract($args);
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        if (isset($name)) {
            $regid = $this->theme()->getRegID($name);
        }
        if (!isset($regid)) {
            throw new EmptyParameterException('regid');
        }

        // Get theme information
        $themeInfo = $this->theme()->getInfo($regid);
        if (!isset($themeInfo)) {
            throw new ThemeNotFoundException($regid, 'Theme (regid: #(1) does not exist.');
        }
        $themename = $themeInfo['name'];
        $themeInfo = $this->theme()->getBaseInfo($themename);

        // Update state of theme
        $set = $adminapi->setstate(['regid' => $regid,
            'state' => ixarTheme::STATE_INACTIVE]);

        if (!isset($set)) {
            throw new Exception('Could not set state of theme');
        }

        return true;
    }
}
