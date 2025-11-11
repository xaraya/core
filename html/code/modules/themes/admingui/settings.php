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
        if (!$this->sec()->checkAccess('AdminThemes')) {
            return;
        }

        // form parameters
        $this->var()->find('hidecore', $hidecore, 'str:1:', '0');
        $this->var()->find('selstyle', $selstyle, 'str:1:', 'plain');
        $this->var()->find('selfilter', $selfilter, 'str:1:', 'ixarTheme::STATE_ANY');
        $this->var()->find('selclass', $selclass, 'str:1:', 'all');
        $this->var()->find('regen', $regen, 'str:1:', false);
        $this->var()->find('useicons', $useicons, 'checkbox', false);

        if (!$this->mod()->setUserVar('hidecore', $hidecore)) {
            return;
        }
        if (!$this->mod()->setUserVar('selstyle', $selstyle)) {
            return;
        }
        if (!$this->mod()->setUserVar('selfilter', $selfilter)) {
            return;
        }
        if (!$this->mod()->setUserVar('selclass', $selclass)) {
            return;
        }
        if (!$this->mod()->setUserVar('useicons', $useicons)) {
            return;
        }

        $this->ctl()->redirect($this->ctl()->getModuleURL(
            'themes',
            'admin',
            'view',
            ['regen' => $regen = 1]
        ));
        return true;
    }
}
