<?php
/**
 * Test a user or group's privileges against a mask
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
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
 * @param none $
 * @return none
 * @throws none
 * @todo none
 */
function roles_admin_testprivileges()
{
    // Get Parameters
    if (!xarVarFetch('uid', 'int:1:', $uid)) return;
    if (!xarVarFetch('pmodule', 'str:1:', $module, '', XARVAR_NOT_REQUIRED,XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('name', 'str:1', $name, '', XARVAR_NOT_REQUIRED,XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('test', 'str:1:35:', $test, '', XARVAR_NOT_REQUIRED,XARVAR_PREP_FOR_DISPLAY)) return;

    // Security Check
    if (!xarSecurityCheck('EditRole')) return;

    // Call the Roles class and get the role
    $roles = new xarRoles();
    $role = $roles->getRole($uid);

    $types = xarModAPIFunc('roles','user','getitemtypes');
    $data['itemtypename'] = $types[$role->getType()]['label'];
    // get the array of parents of this role
    // need to display this in the template
    $parents = array();
    foreach ($role->getParents() as $parent) {
        $parents[] = array('parentid' => $parent->getID(),
            'parentname' => $parent->getName());
    }
    $data['parents'] = $parents;

    // Call the Privileges class and
    // get a list of all modules for dropdown display
    sys::import('modules.privileges.class.privileges');
    $privileges = new xarPrivileges();
    $allmodules = $privileges->getmodules();
    // Call the Masks class
    $masks = new xarMasks();
    // we want to do test
    if (!empty($test)) {
        // get the mask to test against
        $mask = $masks->getMask($name);
        $component = $mask->getComponent();
        // test the mask against the role
        $testresult = $masks->xarSecurityCheck($name, 0, $component, 'All', $mask->getModule(), $role->getName());
        // test failed
        if (!$testresult) {
            $resultdisplay = xarML('Privilege: none found');
        }
        // test returned an object
        else {
            $resultdisplay = "";
            $data['rname'] = $testresult->getName();
            $data['rrealm'] = $testresult->getRealm();
            $data['rmodule'] = $testresult->getModule();
            $data['rcomponent'] = $testresult->getComponent();
            $data['rinstance'] = $testresult->getInstance();
            $data['rlevel'] = xarMasks::$levels[$testresult->getLevel()];
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
                'slevel' => xarMasks::$levels[$testmask->getLevel()]
                );
            $testmaskarray[] = $thismask;
        }
        $data['testmasks'] = $testmaskarray;
        $module = $mask->getModule();
    }
    // no test yet
    // Load Template
    $data['test'] = $test;
    $data['pname'] = $role->getName();
    $data['itemtype'] = $role->getType();
    $data['basetype'] = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('moduleid' => 27, 'itemtype' => $data['itemtype']));
    $types = xarModAPIFunc('roles','user','getitemtypes');
    $data['itemtypename'] = $types[$data['itemtype']]['label'];
    $data['pmodule'] = $module;
    $data['uid'] = $uid;
    $data['allmodules'] = $allmodules;
    $data['testlabel'] = xarML('Test');
    if (empty($module)) $data['masks'] = array();
    else $data['masks'] = $masks->getmasks(strtolower($module));
    $data['authid'] = xarSecGenAuthKey();
    return $data;
    // redirect to the next page
    xarResponseRedirect(xarModURL('roles', 'admin', 'newrole'));
}

?>
