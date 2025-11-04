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
use Xaraya\Modules\Modules\AdminApi;
use ixarMod;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules admin viewerror function
 * @extends MethodClass<AdminGui>
 */
class ViewerrorMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * View an error with a module
     * @author Xaraya Development Team
     * @param int id the module's registered id
     * @return mixed true on success, error message on failure
     * @see AdminGui::viewerror()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!$this->sec()->checkAccess('AdminModules')) {
            return;
        }

        // Get parameters
        $this->var()->find('id', $regId, 'int', 0);
        if (empty($regId)) {
            return $this->ctl()->notFound();
        }

        //if (!$this->sec()->confirmAuthKey()) return;

        // Get module information from the database
        $dbModule = $adminapi->getdbmodules(['regId' => $regId]);
        if (!isset($dbModule)) {
            return;
        }

        // Get module information from the filesystem
        $fileModule = $adminapi->getfilemodules(['regId' => $regId]);
        if (!isset($fileModule)) {
            return;
        }

        // Get the module state and display appropriate template
        // for the error that was encountered with the module
        switch ($dbModule['state']) {
            case ixarMod::STATE_ERROR_UNINITIALISED:
            case ixarMod::STATE_ERROR_INACTIVE:
            case ixarMod::STATE_ERROR_ACTIVE:
            case ixarMod::STATE_ERROR_UPGRADED:
                // Set template to 'update'
                $template = 'errorupdate';

                // Set regId
                $data['regId'] = $regId;

                // Set module name
                $data['modname'] = $dbModule['name'];

                // Set db version
                $data['dbversion'] = $dbModule['version'];

                // Set file version number of module
                $data['fileversion'] = $fileModule['version'];

                break;

            default:
                break;
        }

        // Return the template variables to BL
        $data['context'] ??= $this->getContext();
        return $this->tpl()->module('modules', 'admin', $template, $data);
    }
}
