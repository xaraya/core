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
sys::import('modules.modules.class.installer');
use Xaraya\Modules\InstallerTool;

/**
 * modules admin deactivate function
 * @extends MethodClass<AdminGui>
 */
class DeactivateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Deactivate a module
     * @author Xaraya Development Team
     * Loads module admin API and calls the setstate
     * function to actually perfrom the deactivation,
     * then redirects to the list function with a status
     * message and returns true.
     * @access public
     * @param int id the module id to deactivate
     * @return bool|string|void true on success, false on failure
     * @see AdminGui::deactivate()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!$this->sec()->checkAccess('AdminModules')) {
            return;
        }

        // Security and sanity checks
        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
        }

        $this->var()->find('id', $id, 'int:1:', 0);
        if (empty($id)) {
            return $this->ctl()->notFound();
        }
        $this->var()->find(
            'return_url',
            $return_url,
            'pre:trim:str:1:',
            ''
        );

        //Checking if the user has already passed thru the GUI:
        $this->var()->find('command', $command, 'checkbox', false);

        // set the target location (anchor) to go to within the page
        $minfo = $this->mod()->getInfo($id);
        $target = $minfo['name'];
        if (empty($return_url)) {
            $return_url = $this->ctl()->getModuleURL('modules', 'admin', 'list', ['state' => 0], null) . '#' . $target;
        }

        sys::import('modules.modules.class.installer');
        $installer = InstallerTool::getInstance();

        // If we haven't been to the deps GUI, check that first
        if (!$command) {
            // First check for the modules depending on this one
            $dependents = $installer->getalldependents($id);
            if (count($dependents['active']) > 1) {
                //Let's make a nice GUI to show the user the options
                $data = [];
                $data['id'] = $id;
                //They come in 2 arrays: active, initialised
                //Both have $name => $modInfo under them foreach
                $data['authid']       = $this->sec()->genAuthKey();
                $data['dependencies'] = $dependents;
                return $data;
            } else {
                // No dependents, we can deactivate the module
                if (!$adminapi->deactivate(['regid' => $id])) {
                    return;
                }
                $this->ctl()->redirect($return_url);
                return true;
            }
        }

        // See if we have lost any modules since last generation
        if (!$installer->checkformissing()) {
            return;
        }

        //Bail if we've lost our module
        if ($minfo['state'] != ixarMod::STATE_MISSING_FROM_ACTIVE) {
            //Deactivate with dependents, first dependents
            //then the module itself
            if (!$installer->deactivatewithdependents($id)) {
                //Call exception
                return;
            } // Else
        }

        // Hmmm, I wonder if the target adding is considered a hack
        // it certainly depends on the implementation of xar::ctl()->getModuleURL()
        $this->ctl()->redirect($return_url);

        return true;
    }
}
