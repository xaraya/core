<?php
/**
 * Test a user or group's privileges against a mask
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * testprivileges - test a user or group's privileges against a mask
 *
 * Performs a test of all the privileges of a user or group against a security mask.
 * A security mask defines the hurdle a group/user needs to overcome
 * to gain entrance to a given module component.
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @return array data for the template display
 */
function roles_admin_testprivileges()
{
    // Get Parameters
    if (!xarVarFetch('id', 'int:1:', $id, 0, XARVAR_NOT_REQUIRED)) return;
    if (empty($id)) return xarResponse::notFound();
    if (!xarVarFetch('pmodule', 'int', $modRegId, xarSecurity::PRIVILEGES_ALL, XARVAR_NOT_REQUIRED,XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('name', 'str:1', $name, '', XARVAR_NOT_REQUIRED,XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('test', 'str:1:35:', $test, '', XARVAR_NOT_REQUIRED,XARVAR_PREP_FOR_DISPLAY)) return;

    // Security Check
    if (!xarSecurityCheck('EditRoles')) return;

    // Call the Roles class and get the role
    $role = xarRoles::get($id);

    $types = xarMod::apiFunc('roles','user','getitemtypes');
    $thistype = $role->getType();
    $data['itemtypename'] = $types[$thistype]['label'];
    // get the array of parents of this role
    // need to display this in the template
    $parents = array();
    foreach ($role->getParents() as $parent) {
        $parents[] = array('parentid' => $parent->getID(),
            'parentname' => $parent->getName());
    }
    $data['parents'] = $parents;

    // we want to do test
    if (!empty($test)) {
        // get the mask to test against
        $mask = xarSecurity::getMask($name);
        $component = $mask->getComponent();
        // test the mask against the role
        $testresult = xarSecurity::check($name, 0, $component, 'All', $mask->getModule(), $role->getName());
        // test failed
        if (!$testresult) {
            $resultdisplay = xarML('Privilege: none found');
        }
        // test returned an object
        else {
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
        $testmasks = array($mask);
        $testmaskarray = array();
        foreach ($testmasks as $testmask) {
            $thismask = array('sname' => $testmask->getName(),
                'srealm' => $testmask->getRealm(),
                'smodule' => $testmask->getModule(),
                'scomponent' => $testmask->getComponent(),
                'sinstance' => $testmask->getInstance(),
                'slevel' => xarSecurity::$levels[$testmask->getLevel()]
                );
            $testmaskarray[] = $thismask;
        }
        $data['testmasks'] = $testmaskarray;
        $modName = $mask->getModule();
        $modRegId = xarMod::getRegId($modName);
    }
    // no test yet
    // Load Template
    $data['object'] = $role;
    $data['test'] = $test;
    $data['pname'] = $role->getName();
    $data['itemtype'] = $role->getType();
    $types = xarMod::apiFunc('roles','user','getitemtypes');
    $data['itemtypename'] = $types[$thistype]['label'];
    $data['pmodule'] = $modRegId;
    $data['id'] = $id;
    $data['testlabel'] = xarML('Test');
    if (!empty($modRegId) && $modRegId != xarSecurity::PRIVILEGES_ALL) {
        // Note: xarMasks::getmasks() expects the internal system modid, not the registered modid
        $modInfo = xarMod::getInfo($modRegId);
        $data['masks'] = xarMasks::getmasks($modInfo['systemid']);
    } else {
        $data['masks'] = xarMasks::getmasks(xarSecurity::PRIVILEGES_ALL);
    }
    $data['authid'] = xarSecGenAuthKey();
    return $data;
}

?>
