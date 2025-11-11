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
use Exception;
use ixarMod;
use Xaraya\Modules\InstallerTool;

/**
 * modules admin upgrade function
 * @extends MethodClass<AdminGui>
 */
class UpgradeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Upgrade a module
     * Loads module admin API and calls the upgrade function
     * to actually perform the upgrade, then redrects to
     * the list function and with a status message and returns
     * true.
     * @author Xaraya Development Team
     * @param int id the module id to upgrade
     * @return bool|string|void true on success, false on failure
     * @see AdminGui::upgrade()
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

        $success = true;

        // See if we have lost any modules since last generation
        $installer = InstallerTool::getInstance();
        if (!$installer->checkformissing()) {
            return;
        }

        // TODO: give the user the opportunity to upgrade the dependancies automatically.
        try {
            $installer->verifydependency($id);
            $minfo = $this->mod()->getInfo($id);
            //Bail if we've lost our module
            if ($minfo['state'] != ixarMod::STATE_MISSING_FROM_UPGRADED) {
                // Upgrade module
                $upgraded = $adminapi->upgrade(['regid' => $id]);
            }
        } catch (Exception $e) {
            // TODO: gradually build up the handling here, for now, bail early.
            throw $e;
        }

        // set the target location (anchor) to go to within the page
        $target = $minfo['name'];
        if (empty($return_url)) {
            $return_url = $this->ctl()->getModuleURL('modules', 'admin', 'list', ['state' => 0], null) . '#' . $target;
        }
        // Hmmm, I wonder if the target adding is considered a hack
        // it certainly depends on the implementation of xar::ctl()->getModuleURL()
        //    $this->ctl()->redirect($this->ctl()->getModuleURL('modules', 'admin', "list#$target"));
        $this->ctl()->redirect($return_url);

        return true;
    }
}
