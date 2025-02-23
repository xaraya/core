<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\AdminGui;
use xarSec;
use xarSecurity;
use xarVar;
use sys;
use InstallerTool;

sys::import('xaraya.modules.method');
sys::import('modules.modules.class.installer');

/**
 * modules admin updateinstalloptions function
 * @extends MethodClass<AdminGui>
 */
class UpdateinstalloptionsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @package modules\modules
     * @subpackage modules
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/1.html
     * @see AdminGui::updateinstalloptions()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('AdminModules')) {
            return;
        }

        // TODO: check under what conditions this is needed
        //    if (!xarSec::confirmAuthKey()) return;
        $this->var()->check('regid', $regid, 'int', null);
        sys::import('modules.modules.class.installer');
        $installer = InstallerTool::getInstance();
        if (!$installer->installmodule($regid)) {
            return;
        }
    }
}
