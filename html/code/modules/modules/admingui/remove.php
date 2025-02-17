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
use PropertyRegistration;
use xarController;
use xarLog;
use xarMod;
use xarSec;
use xarSecurity;
use xarVar;
use sys;
use InstallerTool;

sys::import('xaraya.modules.method');
sys::import('modules.modules.class.installer');

/**
 * modules admin remove function
 * @extends MethodClass<AdminGui>
 */
class RemoveMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Remove a module
     * Loads module admin API and calls the remove function
     * to actually perform the removal, then redirects to
     * the list function with a status message and retursn true.
     * @author Xaraya Development Team
     * @access public
     * @param int id the module id
     * @return mixed true on success
     * @see AdminGui::remove()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('AdminModules')) {
            return;
        }

        // Security and sanity checks
        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }

        if (!xarVar::fetch('id', 'int:1:', $id, 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (empty($id)) {
            return xarController::notFound(null, $this->getContext());
        }
        if (!xarVar::fetch(
            'return_url',
            'pre:trim:str:1:',
            $return_url,
            '',
            xarVar::NOT_REQUIRED
        )) {
            return;
        }

        //Checking if the user has already passed thru the GUI:
        xarVar::fetch('command', 'checkbox', $command, false, xarVar::NOT_REQUIRED);

        $minfo = xarMod::getInfo($id);

        // set the target location (anchor) to go to within the page
        $target = $minfo['name'];
        if (empty($return_url)) {
            $return_url = xarController::URL('modules', 'admin', 'list', ['state' => 0], null, $target);
        }

        sys::import('modules.modules.class.installer');
        $installer = InstallerTool::getInstance();
        if (!$command) {
            // not been thru gui yet, first check the modules dependencies
            $dependents = $installer->getalldependents($id);
            if (!(count($dependents['active']) > 0 || count($dependents['initialised']) > 1)) {
                //No dependents, just remove the module
                if (!$adminapi->remove(['regid' => $id])) {
                    return;
                }
                // Clear the property cache
                PropertyRegistration::importPropertyTypes(true);
                xarController::redirect($return_url, null, $this->getContext());
            } else {
                // There are dependents, let's build a GUI
                $data                 = [];
                $data['id']           = $id;
                $data['authid']       = xarSec::genAuthKey();
                $data['dependencies'] = $dependents;
                $data['return_url']   = $return_url;
                return $data;
            }
        }

        // User has seen the GUI
        // Removes with dependents, first remove the necessary dependents then the module itself
        if (!$installer->removewithdependents($id)) {
            //Call exception
            xarLog::message('Missing module since last generation!', xarLog::LEVEL_WARNING);
            return;
        } // Else

        // Clear the property cache
        PropertyRegistration::importPropertyTypes(true);

        // Hmmm, I wonder if the target adding is considered a hack
        // it certainly depends on the implementation of xarController::URL
        //    xarController::redirect(xarController::URL('modules', 'admin', "list#$target"), null, $this->getContext());
        xarController::redirect($return_url, null, $this->getContext());
        // Never reached
        return true;
    }
}
