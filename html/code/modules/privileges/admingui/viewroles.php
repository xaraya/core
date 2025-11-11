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
use xarPrivileges;

/**
 * privileges admin viewroles function
 * @extends MethodClass<AdminGui>
 */
class ViewrolesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * viewroles - display the roles this privilege is assigned to
     * @return array|string|void data for the template display
     * @see AdminGui::viewroles()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('EditRoles')) {
            return;
        }

        $data = [];

        $this->var()->check('id', $id);
        $this->var()->find('show', $data['show'], 'isset', 'assigned');

        // Clear Session Vars
        $this->session()->delVar('privileges_statusmsg');

        //Call the Privileges class and get the privilege
        $priv = xarPrivileges::getPrivilege($id);

        //Get the array of current roles this privilege is assigned to
        $curroles = [];
        foreach ($priv->getRoles() as $role) {
            array_push($curroles, ['roleid' => $role->getID(),
                'name' => $role->getName(),
                'itemtype' => $role->getType(),
                'uname' => $role->getUser(),
                'auth_module_id' => $role->getAuthModule()]);
        }

        //Get the array of parents of this privilege
        $parents = [];
        foreach ($priv->getParents() as $parent) {
            $parents[] = ['parentid' => $parent->getID(),
                'parentname' => $parent->getName()];
        }

        $data['pname'] = $priv->getName();
        $data['id'] = $id;
        $data['roles'] = $curroles;
        $data['removeurl'] = $this->ctl()->getModuleURL(
            'privileges',
            'admin',
            'removerole',
            ['id' => $id]
        );

        $data['parents'] = $parents;
        $data['groups'] = $this->mod()->apiFunc('roles', 'user', 'getallgroups');
        return $data;
    }
}
