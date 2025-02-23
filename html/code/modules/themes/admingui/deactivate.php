<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Themes\AdminGui;
use Xaraya\Modules\Themes\AdminApi;
use xarController;
use xarMod;
use xarSec;
use xarSecurity;
use xarTheme;
use xarVar;
use sys;
use InstallerTool;

sys::import('xaraya.modules.method');
sys::import('modules.modules.class.installer');

/**
 * themes admin deactivate function
 * @extends MethodClass<AdminGui>
 */
class DeactivateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Deactivate a theme
     * Loads theme admin API and calls the setstate
     * function    to actually    perfrom    the    deactivation,
     * then    redirects to the list function with    a status
     * message and returns true.
     * @author Marty Vance
     * @access public
     * @param int id $ the theme id    to deactivate
     * @return bool|string|void true on success, false on failure
     * @see AdminGui::deactivate()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('AdminThemes')) {
            return;
        }

        // Security and sanity checks
        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }

        $this->var()->find('id', $id, 'int:1:', 0);
        if (empty($id)) {
            return xarController::notFound(null, $this->getContext());
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
        $minfo = xarTheme::getInfo($id);
        $target = $minfo['name'];
        if (empty($return_url)) {
            $return_url = xarController::URL('themes', 'admin', 'view', ['state' => xarTheme::STATE_ANY], null, $target);
        }

        // See if we have lost any modules since last generation
        sys::import('modules.modules.class.installer');
        $installer = InstallerTool::getInstance('themes');
        if (!$installer->checkformissing()) {
            return;
        }

        // deactivate
        $deactivated = $adminapi->setstate(['regid' => $id,'state' => xarTheme::STATE_INACTIVE]);

        // Hmmm, I wonder if the target adding is considered a hack
        // it certainly depends on the implementation of xarController::URL
        xarController::redirect($return_url, null, $this->getContext());
        return true;
    }
}
