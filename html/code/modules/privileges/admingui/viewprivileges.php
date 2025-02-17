<?php

/**
 * @package modules\privileges
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Privileges\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Privileges\AdminGui;
use xarSec;
use xarSecurity;
use xarSession;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * privileges admin viewprivileges function
 * @extends MethodClass<AdminGui>
 */
class ViewprivilegesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * viewPrivileges - view the current privileges
     * @return array|string|void data for the template display
     * @see AdminGui::viewprivileges()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('EditPrivileges')) {
            return;
        }

        $data = [];

        if (!xarVar::fetch('show', 'isset', $data['show'], 'assigned', xarVar::NOT_REQUIRED)) {
            return;
        }

        // Clear Session Vars
        xarSession::delVar('privileges_statusmsg');

        $data['authid'] = xarSec::genAuthKey();
        $data['refreshlabel'] = xarML('Refresh');
        return $data;
    }
}
