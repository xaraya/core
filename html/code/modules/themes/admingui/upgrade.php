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
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes admin upgrade function
 * @extends MethodClass<AdminGui>
 */
class UpgradeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Upgrade a theme
     * Loads theme admin API and calls the upgrade function
     * to actually perform the upgrade, then redrects to
     * the list function and with a status message and returns
     * true.
     * @author Marty Vance
     * @param int id the theme id to upgrade
     * @return bool|string|void true on success, false on failure
     * @see AdminGui::upgrade()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!$this->sec()->checkAccess('AdminThemes')) {
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

        // Upgrade theme
        $upgraded = $adminapi->upgrade(['regid' => $id]);

        //throw back
        if (!isset($upgraded)) {
            return;
        }

        if (empty($return_url)) {
            $return_url = $this->ctl()->getModuleURL('themes', 'admin', 'view');
        }
        $this->ctl()->redirect($return_url);
        return true;
    }
}
