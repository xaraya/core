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
use sys;

sys::import('xaraya.modules.method');
sys::import('modules.modules.class.installer');
use Xaraya\Modules\InstallerTool;

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

        $minfo = $this->mod()->getInfo($id);

        // set the target location (anchor) to go to within the page
        $target = $minfo['name'];
        if (empty($return_url)) {
            $return_url = $this->ctl()->getModuleURL('modules', 'admin', 'list', ['state' => 0], null) . '#' . $target;
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
                $this->prop()->importPropertyTypes(true);
                $this->ctl()->redirect($return_url);
                return true;
            } else {
                // There are dependents, let's build a GUI
                $data                 = [];
                $data['id']           = $id;
                $data['authid']       = $this->sec()->genAuthKey();
                $data['dependencies'] = $dependents;
                $data['return_url']   = $return_url;
                return $data;
            }
        }

        // User has seen the GUI
        // Removes with dependents, first remove the necessary dependents then the module itself
        if (!$installer->removewithdependents($id)) {
            //Call exception
            $this->log()->warning('Missing module since last generation!');
            return;
        } // Else

        // Clear the property cache
        $this->prop()->importPropertyTypes(true);

        // Hmmm, I wonder if the target adding is considered a hack
        // it certainly depends on the implementation of xarController::URL
        //    $this->ctl()->redirect($this->ctl()->getModuleURL('modules', 'admin', "list#$target"));
        $this->ctl()->redirect($return_url);
        // Never reached
        return true;
    }
}
