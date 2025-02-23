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
use Exception;
use xarMod;
use xarSec;
use xarSecurity;
use xarVar;
use sys;
use InstallerTool;

sys::import('xaraya.modules.method');
sys::import('modules.modules.class.installer');

/**
 * modules admin modifyinstalloptions function
 * @extends MethodClass<AdminGui>
 */
class ModifyinstalloptionsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @return array|void data for the template display
     * @see AdminGui::modifyinstalloptions()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('AdminModules')) {
            return;
        }

        sys::import('modules.modules.class.installer');
        $installer = InstallerTool::getInstance();
        if (!$installer->getModuleStack()->size) {
            $this->var()->check('regid', $regid, 'int', null);
            if (!isset($regid)) {
                throw new Exception('Missing id of module for installation options...aborting');
            }
            $modInfo = $this->mod()->getInfo($regid);
            $data['authid'] = $this->sec()->genAuthKey('modules');
            $data['regid'] = $modInfo['regid'];
            $data['modname'] = $modInfo['name'];
            $data['displayname'] = $modInfo['displayname'];
            return $data;
        } else {
            throw new Exception('You are not installing this module...aborting');
        }
    }
}
