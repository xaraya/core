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
use xarController;
use xarModUserVars;
use xarSecurity;
use xarTheme;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes admin settings function
 * @extends MethodClass<AdminGui>
 */
class SettingsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * List themes and current settings
     * @author Marty Vance
     * @param array several params from the associated form in template
     * @see AdminGui::settings()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('AdminThemes')) {
            return;
        }

        // form parameters
        xarVar::fetch('hidecore', 'str:1:', $hidecore, '0', xarVar::NOT_REQUIRED);
        xarVar::fetch('selstyle', 'str:1:', $selstyle, 'plain', xarVar::NOT_REQUIRED);
        xarVar::fetch('selfilter', 'str:1:', $selfilter, 'xarTheme::STATE_ANY', xarVar::NOT_REQUIRED);
        xarVar::fetch('selclass', 'str:1:', $selclass, 'all', xarVar::NOT_REQUIRED);
        xarVar::fetch('regen', 'str:1:', $regen, false, xarVar::NOT_REQUIRED);
        xarVar::fetch('useicons', 'checkbox', $useicons, false, xarVar::NOT_REQUIRED);

        if (!xarModUserVars::set('themes', 'hidecore', $hidecore)) {
            return;
        }
        if (!xarModUserVars::set('themes', 'selstyle', $selstyle)) {
            return;
        }
        if (!xarModUserVars::set('themes', 'selfilter', $selfilter)) {
            return;
        }
        if (!xarModUserVars::set('themes', 'selclass', $selclass)) {
            return;
        }
        if (!xarModUserVars::set('themes', 'useicons', $useicons)) {
            return;
        }

        xarController::redirect(xarController::URL(
            'themes',
            'admin',
            'view',
            ['regen' => $regen = 1]
        ), null, $this->getContext());
        return true;
    }
}
