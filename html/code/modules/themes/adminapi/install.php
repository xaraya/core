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
use InstallerTool;

sys::import('xaraya.modules.method');
sys::import('modules.modules.class.installer');

/**
 * themes adminapi install function
 * @extends MethodClass<AdminApi>
 */
class InstallMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Install a theme.
     * @author Marty Vance
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['maindId'] ID of the module to look dependents for
     * @return bool|void true on dependencies activated, false for not
     * @see AdminApi::install()
     */
    public function __invoke(array $args = [])
    {
        //    static $installed_ids = array();
        $regid = $args['regid'];

        // Security Check
        // need to specify the module because this function is called by the installer module
        if (!$this->sec()->check('AdminThemes', 1, 'All', 'All', 'themes')) {
            return;
        }

        // Argument check
        if (!isset($regid)) {
            throw new EmptyParameterException('regid');
        }
        // See if we have lost any modules since last generation
        sys::import('modules.modules.class.installer');
        $installer = InstallerTool::getInstance('themes');
        if (!$installer->checkformissing()) {
            return;
        }

        // Make xarTheme::getInfo not cache anything...
        xarTheme::setNoCache(true);

        $installer->installmodule($regid);
        return true;
    }
}
