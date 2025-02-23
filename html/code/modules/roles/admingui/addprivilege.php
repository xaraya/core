<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\AdminGui;
use xarController;
use xarModHooks;
use xarPrivileges;
use xarRoles;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles admin addprivilege function
 * @extends MethodClass<AdminGui>
 */
class AddprivilegeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * addprivilege - assign a privilege to role
     * This is an action page
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @see AdminGui::addprivilege()
     */
    public function __invoke(array $args = [])
    {
        // get parameters
        $this->var()->find('privid', $privid, 'int:1:', 0);
        $this->var()->find('roleid', $roleid, 'int:1:', 0);
        if (empty($privid)) {
            return xarController::notFound(null, $this->getContext());
        }
        if (empty($roleid)) {
            return xarController::notFound(null, $this->getContext());
        }

        // Check for authorization code
        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }

        // Call the Roles class and get the role
        $role = xarRoles::get($roleid);

        // Call the Privileges class and get the privilege
        sys::import('modules.privileges.class.privileges');
        $priv = xarPrivileges::getPrivilege($privid);

        // Security
        if (!xarSecurity::check('ManagePrivileges', 0, 'Privileges', $priv->getName())) {
            return;
        }

        // If this privilege is already assigned do nothing
        // Try to assign the privilege and bail if an error was thrown
        if (!$priv->isassigned($role)) {
            if (!$role->assignPrivilege($priv)) {
                return;
            }
        }

        // We need to tell some hooks that we are coming from the add privilege screen
        // and not the update the actual roles screen.  Right now, the keywords vanish
        // into thin air.  Bug 1960 and 3161
        xarVar::setCached('Hooks.all', 'noupdate', 1);

        // CHECKME: do we really want to do that here (other than for flushing the cache) ?
        // call update hooks and let them know that the role has changed
        $pargs['module']   = 'roles';
        $pargs['itemtype'] = $role->getType();
        $pargs['itemid']   = $roleid;
        xarModHooks::call('item', 'update', $roleid, $pargs);

        $this->var()->find('return_url', $return_url, 'isset', '');

        if (empty($return_url)) {
            $return_url = xarController::URL(
                'roles',
                'admin',
                'showprivileges',
                ['id' => $roleid]
            );
        }

        // redirect to the next page
        xarController::redirect($return_url, null, $this->getContext());
        return true;
    }
}
