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
use ixarTheme;
use xarTheme;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes adminapi upgrade function
 * @extends MethodClass<AdminApi>
 */
class UpgradeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Upgrade a theme
     * @author Marty Vance
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['regid'] registered theme id
     * @return bool|void true on success, false on failure
     * @throws \EmptyParameterException
     * @see AdminApi::upgrade()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Get arguments from argument array
        extract($args);

        // Argument check
        if (!isset($regid)) {
            throw new EmptyParameterException('regid');
        }

        // Get theme information
        $themeInfo = xarTheme::getInfo($regid);
        if (empty($themeInfo)) {
            $this->session()->setVar('errormsg', $this->ml('No such theme'));
            return false;
        }

        // Update state of theme
        $res = $adminapi->setstate(['regid' => $regid, 'state' => ixarTheme::STATE_INACTIVE]);

        if (!isset($res)) {
            return;
        }

        // Get the new version information...
        $themeFileInfo = xarTheme::getFileInfo($themeInfo['osdirectory']);
        if (empty($themeFileInfo)) {
            return;
        }

        // Note the changes in the database...
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        $sql = "UPDATE $xartable[themes] SET version = ? WHERE regid = ?";
        $bindvars = [$themeFileInfo['version'],
            $regid];

        $dbconn->Execute($sql, $bindvars);

        // Message
        $this->session()->setVar('statusmsg', $this->ml('Theme has been upgraded, now inactive'));

        // Success
        return true;
    }
}
