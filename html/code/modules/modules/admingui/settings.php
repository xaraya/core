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
        if (!$this->sec()->checkAccess('AdminModules')) {
            return;
        }

        $this->var()->find('hidecore', $hidecore, 'str:1:', '0');
        $this->var()->find('selstyle', $selstyle, 'str:1:', 'plain');
        $this->var()->find('selfilter', $selfilter, 'str:1:', 'ixarMod::STATE_ANY');
        $this->var()->find('selsort', $selsort, 'str:1:', 'namedesc');
        $this->var()->find('regen', $regen, 'str:1:');

        $this->mod()->setUserVar('hidecore', $hidecore);
        $this->mod()->setUserVar('selstyle', $selstyle);
        $this->mod()->setUserVar('selfilter', $selfilter);
        $this->mod()->setUserVar('selsort', $selsort);

        $this->ctl()->redirect($this->ctl()->getModuleURL('modules', 'admin', 'list', ['regen' => $regen]));
        return true;
    }
}
