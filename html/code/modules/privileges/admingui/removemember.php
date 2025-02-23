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
use Xaraya\Modules\Privileges\AdminApi;
use xarController;
use xarMod;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * privileges admin removemember function
 * @extends MethodClass<AdminGui>
 */
class RemovememberMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * removeMember - remove a privilege from a privilege
     * Remove a privilege as a member of another privilege.
     * This is an action page..
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @return array|string|void
     * @see AdminGui::removemember()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('EditPrivileges')) {
            return;
        }

        // Check for authorization code
        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }

        // get input from any view of this page
        xarVar::fetch('childid', 'int', $childid, null, xarVar::NOT_REQUIRED);
        xarVar::fetch('parentid', 'int', $parentid, null, xarVar::NOT_REQUIRED);

        // call the API function
        if (!$adminapi->removemember(['parentid' => $parentid, 'childid' => $childid])) {
        }

        // redirect to the next page
        xarController::redirect(xarController::URL(
            'privileges',
            'admin',
            'modifyprivilege',
            ['id' => $childid]
        ), null, $this->getContext());
        return true;
    }
}
