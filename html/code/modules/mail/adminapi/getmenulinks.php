<?php

/**
 * @package modules\mail
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Mail\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Mail\AdminApi;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail adminapi getmenulinks function
 * @extends MethodClass<AdminApi>
 */
class GetmenulinksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Utility function pass individual menu items to the admin menu.
     * @author John Cox <niceguyeddie@xaraya.com>
     * @return array the menulinks for the admin menu items of this module.
     * @see AdminApi::getmenulinks()
     */
    public function __invoke(array $args = [])
    {
        if ($this->mod()->isAvailable('scheduler')) {
            $menulinks[] = ['url' => $this->ctl()->getModuleURL('mail', 'admin', 'viewq'),
                'title' => $this->ml('View all mails scheduled to be sent later'),
                'label' => $this->ml('View Mail Queue')];
        }
        return $menulinks;
    }
}
