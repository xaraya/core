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
use sys;

sys::import('xaraya.modules.method');

/**
 * roles admin removeprivilege function
 * @extends MethodClass<AdminGui>
 */
class RemoveprivilegeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * removeprivilege - remove a privilege
     * prompts for confirmation
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @see AdminGui::removeprivilege()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('EditRoles')) {
            return;
        }

        $this->var()->get('privid', $privid, 'int:1:');
        $this->var()->get('roleid', $roleid, 'int:1:');
        $this->var()->find('confirmation', $confirmation, 'str:1:', '');
        // Call the Roles class and get the role
        $role  = xarRoles::get($roleid);

        // get the array of parents of this role
        // need to display this in the template
        $parents = [];
        foreach ($role->getParents() as $parent) {
            $parents[] = ['parentid'   => $parent->getID(),
                'parentname' => $parent->getName()];
        }
        $data['parents'] = $parents;

        // Call the Privileges class and get the privilege
        $priv = xarPrivileges::getPrivilege($privid);
        // some assignments can't be removed, for your own good
        if ((($roleid == 1) && ($privid == 1))
            || (($roleid == 2) && ($privid == 6))
            || (($roleid == 4) && ($privid == 2))) {
            return $this->tpl()->module('roles', 'user', 'errors', ['layout' => 'remove_privilege']);
        }

        // some info for the template display
        $rolename = $role->getName();
        $privname = $priv->getName();

        if (empty($confirmation)) {
            // Load Template
            $data['authid']   = $this->sec()->genAuthKey();
            $data['roleid']   = $roleid;
            $data['privid']   = $privid;
            $data['ptype']    = $role->getType();
            $data['privname'] = $privname;
            $data['rolename'] = $rolename;
            $data['removelabel'] = $this->ml('Remove');
            return $data;
        } else {
            // Check for authorization code
            if (!$this->sec()->confirmAuthKey()) {
                return $this->ctl()->badRequest('bad_author');
            }
            // Try to remove the privilege and bail if an error was thrown
            if (!$role->removePrivilege($priv)) {
                return;
            }

            // We need to tell some hooks that we are coming from the add privilege screen
            // and not the update the actual roles screen.  Right now, the keywords vanish
            // into thin air.  Bug 1960 and 3161
            $this->var()->setCached('Hooks.all', 'noupdate', 1);

            // CHECKME: do we really want to do that here (other than for flushing the cache) ?
            // call update hooks and let them know that the role has changed
            $pargs['module'] = 'roles';
            $pargs['itemid'] = $roleid;
            $this->mod()->callHooks('item', 'update', $roleid, $pargs);

            // redirect to the next page
            $this->ctl()->redirect($this->ctl()->getModuleURL(
                'roles',
                'admin',
                'showprivileges',
                ['id' => $roleid]
            ));
            return true;
        }
    }
}
