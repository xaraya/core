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
use xarController;
use xarModHooks;
use xarPrivileges;
use xarSec;
use xarSecurity;
use xarSession;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * privileges admin deleteprivilege function
 * @extends MethodClass<AdminGui>
 */
class DeleteprivilegeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * deletePrivilege - delete a privilege
     * prompts for confirmation
     * @see AdminGui::deleteprivilege()
     */
    public function __invoke(array $args = [])
    {
        $this->var()->check('id', $id);
        $this->var()->check('confirmation', $confirmation);

        // Clear Session Vars
        $this->session()->delVar('privileges_statusmsg');

        //Call the Privileges class and get the privilege to be deleted
        sys::import('modules.privileges.class.privileges');
        $priv = xarPrivileges::getprivilege($id);
        if (empty($priv)) {
            return $this->ctl()->notFound();
        }
        $name = $priv->getName();

        // Security
        if (!$this->sec()->check('ManagePrivileges', 0, 'Privileges', $name)) {
            return;
        }

        if (empty($confirmation)) {

            //Get the array of parents of this privilege
            $parents = [];
            foreach ($priv->getParents() as $parent) {
                $parents[] = ['parentid' => $parent->getID(),
                    'parentname' => $parent->getName()];
            }
            //Load Template
            $data['authid'] = $this->sec()->genAuthKey();
            $data['id'] = $id;
            $data['pname'] = $name;
            $data['parents'] = $parents;
            return $data;

        }

        // Check for authorization code
        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
        }

        //Try to remove the privilege and bail if an error was thrown
        if (!$priv->remove()) {
            return;
        }

        $this->mod()->callHooks('item', 'delete', $id, '');

        $this->session()->setVar('privileges_statusmsg', $this->ml(
            'Privilege Removed',
            'privileges'
        ));

        // redirect to the next page
        $this->ctl()->redirect($this->ctl()->getModuleURL('privileges', 'admin', 'viewprivileges'));
        return true;
    }
}
