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
use xarMod;
use xarPrivileges;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * privileges admin displayprivilege function
 * @extends MethodClass<AdminGui>
 */
class DisplayprivilegeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * displayprivilege - display privilege details
     * @return array|void data for the template display
     * @see AdminGui::displayprivilege()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('EditPrivileges')) {
            return;
        }

        $this->var()->check('id', $id);
        $this->var()->find('pinstance', $instance, 'array', []);

        //Call the Privileges class and get the privilege to be modified
        sys::import('modules.privileges.class.privileges');
        $priv = xarPrivileges::getPrivilege($id);

        //Get the array of parents of this privilege
        $parents = [];
        foreach ($priv->getParents() as $parent) {
            $parents[] = ['parentid' => $parent->getID(),
                'parentname' => $parent->getName()];
        }

        // Load Template
        if (isset($id)) {
            $data['ppid'] = $id;
        } else {
            $data['ppid'] = $priv->getID();
        }

        $data['priv'] = $priv;
        $data['pname'] = $priv->getName();
        $data['prealm'] = $priv->getRealm();
        $data['pmodule'] = $priv->getModule();
        $data['pcomponent'] = $priv->getComponent();
        $data['plevel'] = $priv->getLevel();

        $instances = $adminapi->getinstances(['module' => $data['pmodule'],'component' => $data['pcomponent']]);
        $numInstances = count($instances); // count the instances to use in later loops

        $default = [];
        $data['instance'] = $priv->getInstance();

        $data['ptype'] = $priv->isEmpty() ? "empty" : "full";
        $data['parents'] = $parents;
        return $data;
    }
}
