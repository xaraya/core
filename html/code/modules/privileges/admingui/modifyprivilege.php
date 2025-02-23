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
use xarSec;
use xarSecurity;
use xarSession;
use xarVar;
use sys;
use SecurityLevel;

sys::import('xaraya.modules.method');

/**
 * privileges admin modifyprivilege function
 * @extends MethodClass<AdminGui>
 */
class ModifyprivilegeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * modifyprivilege - modify privilege details
     * @return array|void data for the template display
     * @see AdminGui::modifyprivilege()
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
        $this->var()->check('pname', $name);
        $this->var()->check('prealm', $realm);
        $this->var()->find('pmodule', $pmodule);
        $this->var()->check('pcomponent', $component);
        $this->var()->check('poldcomponent', $oldcomponent);
        $this->var()->check('ptype', $type);
        $this->var()->check('plevel', $level);
        $this->var()->find('pinstance', $instance, 'array', []);

        $this->var()->check('pparentid', $pparentid);

        // Clear Session Vars
        xarSession::delVar('privileges_statusmsg');

        //Call the Privileges class and get the privilege to be modified
        sys::import('modules.privileges.class.privileges');
        $priv = xarPrivileges::getPrivilege($id);
        //Get the array of parents of this privilege
        $parents = [];
        foreach ($priv->getParents() as $parent) {
            $parents[] = ['parentid' => $parent->getID(),
                'parentname' => $parent->getName()];
        }

        // remove duplicate entries from the list of privileges
        //Get the array of all privileges, minus the current one
        // need this for the dropdown display
        $privileges = [];
        $names = [];
        foreach (xarPrivileges::getprivileges() as $temp) {
            $nam = $temp['name'];
            if (!in_array($nam, $names) && $temp['id'] != $id) {
                $names[] = $nam;
                $privileges[] = $temp;
            }
        }

        // Load Template
        if (isset($id)) {
            $data['ppid'] = $id;
        } else {
            $data['ppid'] = $priv->getID();
        }

        if (empty($name)) {
            $name = $priv->getName();
        }
        $data['pname'] = $name;

        // Security Check
        $data['frozen'] = !xarSecurity::check('EditPrivileges', 0, 'Privileges', $name);

        if (isset($realm)) {
            $data['prealm'] = $realm;
        } else {
            $data['prealm'] = $priv->getRealm();
        }

        if (isset($pmodule)) {
            $data['pmodule'] = $pmodule;
        } else {
            $data['pmodule'] = $priv->getModule();
        }
        if (empty($data['pmodule'])) {
            $data['pmodule'] = "empty";
        }

        if (isset($component)) {
            $data['pcomponent'] = $component;
        } else {
            $data['pcomponent'] = $priv->getComponent();
        }

        if (isset($level)) {
            $data['plevel'] = $level;
        } else {
            $data['plevel'] = $priv->getLevel();
        }

        $instances = $adminapi->getinstances(['module' => $data['pmodule'],'component' => $data['pcomponent']]);
        $numInstances = count($instances); // count the instances to use in later loops

        if (count($instance) > 0) {
            $default = $instance;
        } else {
            $default = [];
            $inst = $priv->getInstance();
            if ($inst == "All") {
                for ($i = 0; $i < $numInstances; $i++) {
                    $default[] = "All";
                }
            } else {
                $default = explode(':', $priv->getInstance());
            }
        }
        // send to external wizard if necessary
        if (!empty($instances['external']) && $instances['external'] == "yes") {
            $data['target'] = $instances['target'] . '&amp;extpid=' . $data['ppid'] . '&amp;extname=' . $data['pname'] . '&amp;extrealm=' . $data['prealm'] . '&amp;extmodule=' . $data['pmodule'] . '&amp;extcomponent=' . $data['pcomponent'] . '&amp;extlevel=' . $data['plevel'];
            $data['target'] .= '&amp;extinstance=' . urlencode(join(':', $default));
            $data['curinstance'] = join(':', $default);
            $data['instances'] = [];
        } else {
            for ($i = 0; $i < $numInstances; $i++) {
                if ($component == '' || ($component == $oldcomponent)) {
                    $instances[$i]['default'] = $default[$i];
                } else {
                    $instances[$i]['default'] = '';
                }
            }
            $data['instances'] = $instances;
        }

        if (isset($type)) {
            $data['ptype'] = $type;
        } else {
            $data['ptype'] = $priv->isEmpty() ? "empty" : "full";
        }

        // @checkme where is $show supposed to come from?
        if (isset($show)) {
            $data['show'] = $show;
        } else {
            $data['show'] = 'assigned';
        }

        $accesslevels = SecurityLevel::$displayMap;
        unset($accesslevels[-1]);
        $data['levels'] = [];
        foreach ($accesslevels as $key => $value) {
            $data['levels'][] = ['id' => $key, 'name' => $value];
        }

        $data['oldcomponent'] = $component;
        $data['authid'] = xarSec::genAuthKey();
        $data['parents'] = $parents;
        $data['privileges'] = $privileges;
        $data['realms'] = xarPrivileges::getrealms();
        ;
        $data['components'] = $adminapi->getcomponents(['modid' => xarMod::getRegID($data['pmodule'])]);
        $data['refreshlabel'] = xarML('Refresh');
        return $data;
    }
}
