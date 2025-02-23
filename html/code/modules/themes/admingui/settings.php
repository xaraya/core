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
        $this->var()->find('hidecore', $hidecore, 'str:1:', '0');
        $this->var()->find('selstyle', $selstyle, 'str:1:', 'plain');
        $this->var()->find('selfilter', $selfilter, 'str:1:', 'xarTheme::STATE_ANY');
        $this->var()->find('selclass', $selclass, 'str:1:', 'all');
        $this->var()->find('regen', $regen, 'str:1:', false);
        $this->var()->find('useicons', $useicons, 'checkbox', false);

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
