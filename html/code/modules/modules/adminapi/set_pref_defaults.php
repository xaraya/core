<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\AdminApi;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules adminapi set_pref_defaults function
 * @extends MethodClass<AdminApi>
 */
class SetPrefDefaultsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * reset admin preferences to default module preferences
     * @author Xaraya Development Team
     * @access public
     * @return bool|void true on success, false on failure
     * @see AdminApi::setPrefDefaults()
     */
    public function __invoke(array $args = [])
    {
        // no beating around the bush here
        if ($this->mod()->getUserVar('hidecore')) {
            $this->mod()->delUserVar('hidecore');
        }
        if ($this->mod()->getUserVar('regen')) {
            $this->mod()->delUserVar('regen');
        }
        if ($this->mod()->getUserVar('selstyle')) {
            $this->mod()->delUserVar('selstyle');
        }
        if ($this->mod()->getUserVar('selfilter')) {
            $this->mod()->delUserVar('selfilter');
        }
        if ($this->mod()->getUserVar('selsort')) {
            $this->mod()->delUserVar('selsort');
        }
        if ($this->mod()->getUserVar('hidestats')) {
            $this->mod()->delUserVar('hidestats');
        }
        if ($this->mod()->getUserVar('selmax')) {
            $this->mod()->delUserVar('selmax');
        }
        if ($this->mod()->getUserVar('startpage')) {
            $this->mod()->delUserVar('startpage');
        }

        // all done
        return true;
    }
}
