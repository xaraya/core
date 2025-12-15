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
use Xaraya\Modules\Roles\UserApi;
use xarMasks;
use xarPrivileges;
use xarRoles;
use xarSecurity;

/**
 * roles admin testprivileges function
 * @extends MethodClass<AdminGui>
 */
class TestprivilegesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * testprivileges - test a user or group's privileges against a mask
     * Performs a test of all the privileges of a user or group against a security mask.
     * A security mask defines the hurdle a group/user needs to overcome
     * to gain entrance to a given module component.
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @return array|string|void data for the template display
     * @see AdminGui::testprivileges()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Security
        if (!$this->sec()->checkAccess('EditRoles')) {
            return;
        }

        // Get Parameters
        $this->var()->find('id', $id, 'int:1:', 0);
        if (empty($id)) {
            return $this->ctl()->notFound();
        }
        $this->var()->find('pmodule', $modRegId, 'int', xarSecurity::PRIVILEGES_ALL);
        $this->var()->find('name', $name, 'str:1', '');
        $this->var()->find('test', $test, 'str:1:35:', '');

        // Call the Roles class and get the role
        $role = $this->user()->getRole('id', (int) $id);

        $types = $userapi->getitemtypes();
        $thistype = $role->getType();
        $data['itemtypename'] = $types[$thistype]['label'];
        // get the array of parents of this role
        // need to display this in the template
        $parents = [];
        foreach ($role->getParents() as $parent) {
            $parents[] = ['parentid' => $parent->getID(),
                'parentname' => $parent->getName()];
        }
        $data['parents'] = $parents;

        // we want to do test
        if (!empty($test)) {
            // get the mask to test against
            $mask = xarSecurity::getMask($name);
            $component = $mask->getComponent();
            // test the mask against the role
            $testresult = $this->sec()->check($name, 0, $component, 'All', $mask->getModule(), $role->getName());
            // test failed
            if (!$testresult) {
                $resultdisplay = $this->ml('Privilege: none found');
            }
            // test returned an object
            else {
                // @fixme testresult does not contain a privilege id
                $thisprivilege = xarPrivileges::getPrivilege($testresult['id']);
                $resultdisplay = "";
                $data['rname'] = $thisprivilege->getName();
                $data['rrealm'] = $thisprivilege->getRealm();
                $data['rmodule'] = $thisprivilege->getModule();
                $data['rcomponent'] = $thisprivilege->getComponent();
                $data['rinstance'] = $thisprivilege->getInstance();
                $data['rlevel'] = xarSecurity::$levels[$thisprivilege->getLevel()];
            }
            // rest of the data for template display
            $data['testresult'] = $testresult;
            $data['resultdisplay'] = $resultdisplay;
            $testmasks = [$mask];
            $testmaskarray = [];
            foreach ($testmasks as $testmask) {
                $thismask = ['sname' => $testmask->getName(),
                    'srealm' => $testmask->getRealm(),
                    'smodule' => $testmask->getModule(),
                    'scomponent' => $testmask->getComponent(),
                    'sinstance' => $testmask->getInstance(),
                    'slevel' => xarSecurity::$levels[$testmask->getLevel()],
                ];
                $testmaskarray[] = $thismask;
            }
            $data['testmasks'] = $testmaskarray;
            $modName = $mask->getModule();
            $modRegId = $this->mod()->getRegID($modName);
        }
        // no test yet
        // Load Template
        $data['object'] = $role;
        $data['test'] = $test;
        $data['pname'] = $role->getName();
        $data['itemtype'] = $role->getType();
        $types = $userapi->getitemtypes();
        $data['itemtypename'] = $types[$thistype]['label'];
        $data['pmodule'] = $modRegId;
        $data['id'] = $id;
        $data['testlabel'] = $this->ml('Test');
        if (!empty($modRegId) && $modRegId != xarSecurity::PRIVILEGES_ALL) {
            // Note: xarMasks::getmasks() expects the internal system modid, not the registered modid
            $modInfo = $this->mod()->getInfo($modRegId);
            $data['masks'] = xarMasks::getmasks($modInfo['systemid']);
        } else {
            $data['masks'] = xarMasks::getmasks(xarSecurity::PRIVILEGES_ALL);
        }
        $data['authid'] = $this->sec()->genAuthKey();
        return $data;
    }
}
