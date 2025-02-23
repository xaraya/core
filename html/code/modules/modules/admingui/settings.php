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
use xarController;
use xarMod;
use xarModUserVars;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules admin settings function
 * @extends MethodClass<AdminGui>
 */
class SettingsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * List modules and current settings
     * @param array several params from the associated form in template
     * @author Xaraya Development Team
     * @see AdminGui::settings()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('AdminModules')) {
            return;
        }

        $this->var()->find('hidecore', $hidecore, 'str:1:', '0');
        $this->var()->find('selstyle', $selstyle, 'str:1:', 'plain');
        $this->var()->find('selfilter', $selfilter, 'str:1:', 'xarMod::STATE_ANY');
        $this->var()->find('selsort', $selsort, 'str:1:', 'namedesc');
        $this->var()->find('regen', $regen, 'str:1:');

        xarModUserVars::set('modules', 'hidecore', $hidecore);
        xarModUserVars::set('modules', 'selstyle', $selstyle);
        xarModUserVars::set('modules', 'selfilter', $selfilter);
        xarModUserVars::set('modules', 'selsort', $selsort);

        xarController::redirect(xarController::URL('modules', 'admin', 'list', ['regen' => $regen]), null, $this->getContext());
        return true;
    }
}
