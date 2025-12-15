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
use xarPrivileges;
use xarRoles;

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
            return $this->ctl()->notFound();
        }
        if (empty($roleid)) {
            return $this->ctl()->notFound();
        }

        // Check for authorization code
        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
        }

        // Call the Roles class and get the role
        $role = $this->user()->getRole('id', (int) $roleid);

        // Call the Privileges class and get the privilege
        $priv = xarPrivileges::getPrivilege($privid);

        // Security
        if (!$this->sec()->check('ManagePrivileges', 0, 'Privileges', $priv->getName())) {
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
        $this->mem()->set('Hooks.all', 'noupdate', 1);

        // CHECKME: do we really want to do that here (other than for flushing the cache) ?
        // call update hooks and let them know that the role has changed
        $pargs['module']   = 'roles';
        $pargs['itemtype'] = $role->getType();
        $pargs['itemid']   = $roleid;
        $this->mod()->callHooks('item', 'update', $roleid, $pargs);

        $this->var()->find('return_url', $return_url, 'isset', '');

        if (empty($return_url)) {
            $return_url = $this->ctl()->getModuleURL(
                'roles',
                'admin',
                'showprivileges',
                ['id' => $roleid]
            );
        }

        // redirect to the next page
        $this->ctl()->redirect($return_url);
        return true;
    }
}
