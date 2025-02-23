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
use ForbiddenOperationException;
use xarController;
use xarPrivileges;
use xarRoles;
use xarSec;
use xarSecurity;
use xarSession;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * privileges admin removerole function
 * @extends MethodClass<AdminGui>
 */
class RemoveroleMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * removeRole - remove a role from a privilege assignment
     * prompts for confirmation
     * @see AdminGui::removerole()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('EditPrivileges')) {
            return;
        }

        $this->var()->check('id', $id);
        $this->var()->check('roleid', $roleid);
        $this->var()->check('confirmation', $confirmation);
        if (empty($id)) {
            return $this->ctl()->notFound();
        }
        if (empty($roleid)) {
            return $this->ctl()->notFound();
        }

        //Call the Roles class and get the role to be removed
        $role = xarRoles::get($roleid);

        //Call the Privileges class and get the privilege to be de-assigned
        sys::import('modules.privileges.class.privileges');
        $priv = xarPrivileges::getPrivilege($id);


        // some assignments can't be changed, for your own good
        if ((($roleid == 1) && ($id == 1)) ||
            (($roleid == 2) && ($id == 6)) ||
            (($roleid == 4) && ($id == 2))) {
            throw new ForbiddenOperationException(null, 'This privilege cannot be removed');
        }

        // Clear Session Vars
        $this->session()->delVar('privileges_statusmsg');

        // get the names of the role and privilege for display purposes
        $rolename = $role->getName();
        $privname = $priv->getName();

        if (empty($confirmation)) {

            //Load Template
            $data['authid'] = $this->sec()->genAuthKey();
            $data['roleid'] = $roleid;
            $data['id'] = $id;
            $data['ptype'] = $role->getType();
            $data['privname'] = $privname;
            $data['rolename'] = $rolename;
            return $data;

        } else {

            // Check for authorization code
            if (!$this->sec()->confirmAuthKey()) {
                return $this->ctl()->badRequest('bad_author');
            }

            //Try to remove the privilege and bail if an error was thrown
            if (!$role->removePrivilege($priv)) {
                return;
            }

            $this->session()->setVar('privileges_statusmsg', $this->ml(
                'Role Removed',
                'privileges'
            ));

            // redirect to the next page
            $this->ctl()->redirect($this->ctl()->getModuleURL(
                'privileges',
                'admin',
                'viewroles',
                ['id' => $id]
            ));
            return true;
        }

    }
}
