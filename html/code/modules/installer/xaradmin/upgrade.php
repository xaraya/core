<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

/**
 * @return array data for the template display
 */
function installer_admin_upgrade()
{    
    if(!xarVarFetch('phase','int', $data['phase'], 1, XARVAR_DONT_SET)) {return;}

    // Version information
    $fileversion = xarCore::VERSION_NUM;
    $dbversion = xarConfigVars::get(null, 'System.Core.VersionNum');
    sys::import('xaraya.version');
    
    // Versions prior to 2.1.0 had the revision number as version number, or something else
    if (strlen($dbversion) == 41 || empty($dbversion) || $dbversion == 'unknown') {
        $data['versioncompare'] = 1;
        $data['upgradable'] = 1;
        $data['oldversionnum'] = $dbversion;
    } else {
        $data['versioncompare'] = xarVersion::compare($fileversion, $dbversion);
        $data['upgradable'] = xarVersion::compare($fileversion, '2.0.0') > 0;
    }
    
    // @checkme <chris/> what are these for?
    // Core modules
    $data['coremodules'] = array(
                                42    => 'authsystem',
                                68    => 'base',
                                13    => 'blocks',
                                182   => 'dynamicdata',
                                200   => 'installer',
                                771   => 'mail',
                                1     => 'modules',
                                1098  => 'privileges',
                                27    => 'roles',
                                17    => 'themes',
    );
    $data['versions'] = array(
                                '2.1.1',
                                '2.1.2',
                                '2.1.3',
                                '2.2.0',
                                '2.3.0',
                                '2.4.0',
    );
    
        
    if ($data['phase'] != 1) {
        // Get the password of the designated site administrator
        $adminid = xarModVars::get('roles','admin');
        sys::import('modules.dynamicdata.class.objects.master');
        $role = DataObjectMaster::getObject(array('name' => 'roles_users'));
        $role->getItem(array('itemid' => $adminid));
        $adminpass = $role->properties['password']->value;
        $role->properties['password']->value = '';
        
        // Get the password from the last page, either entered by the user (and needs to be encrypted)
        // or stored on a previous page
        if(!xarVarFetch('password','str', $data['password'], '', XARVAR_NOT_REQUIRED)) {return;}
        if(!xarVarFetch('pass','str', $pass, '', XARVAR_NOT_REQUIRED)) {return;}

        // Encrypt if needed using the encryption scheme of the roles module
        if (!empty($pass)) {
            $data['password'] = $role->properties['password']->setValue($pass);
            $userpass = $role->properties['password']->value;
        } else {
            $userpass = $data['password'];
        }

        // If they don't coincide, bail
        if ($adminpass != $userpass) {        
            xarController::redirect(xarServer::getCurrentURL(array('phase' => 1, 'error' => 1)));
        }
        $data['password'] = $adminpass;
    }
    
    if ($data['phase'] == 1) {
        $data['active_step'] = 1;
        if(!xarVarFetch('error','int', $data['error'], 0, XARVAR_NOT_REQUIRED)) {return;}

    } elseif ($data['phase'] == 2) {
        $data['active_step'] = 2;
        
        
    } elseif ($data['phase'] == 3) {
        $data['active_step'] = 3;
        // Get the list of version upgrades
        Upgrader::loadFile('upgrades/upgrade_list.php');
        $upgrade_list = installer_adminapi_get_upgrade_list();

        // Run the upgrades
        $upgrades = array();
        foreach ($upgrade_list as $abbr_version => $upgrade_version) {
            // only run upgrades from dbversion onwards
            if (xarVersion::compare($upgrade_version, $dbversion) <= 0) continue;
            if (!Upgrader::loadFile('upgrades/' . $abbr_version .'/main.php')) {
                $upgrades[$upgrade_version]['message'] = xarML('There are no upgrades for version #(1)', $upgrade_version);
                $upgrades[$upgrade_version]['tasks'] = array();
                //return $data;
            } else {
                $upgrade_function = 'main_upgrade_' . $abbr_version;
                $result = $upgrade_function();
                $upgrades[$upgrade_version] = $result['upgrade'];
            }
        }
        $data['upgrades'] =& $upgrades;

    } elseif ($data['phase'] == 4) {
        $data['active_step'] = 4;
        // Flush the property cache as a matter of course
        $success = xarMod::apiFunc('dynamicdata','admin','importpropertytypes');

        // Align the db and filesystem version info
        xarConfigVars::set(null, 'System.Core.VersionId', xarCore::VERSION_ID);
        xarConfigVars::set(null, 'System.Core.VersionNum', xarCore::VERSION_NUM);
        xarConfigVars::set(null, 'System.Core.VersionRev', xarCore::VERSION_REV);
        xarConfigVars::set(null, 'System.Core.VersionSub', xarCore::VERSION_SUB);
        
        sys::import('xaraya.version');
        // Get the list of version checks
        Upgrader::loadFile('checks/check_list.php');
        $check_list = installer_adminapi_get_check_list();

        // Run the checks
        $checks = array();
        foreach ($check_list as $abbr_version => $check_version) {
            // @checkme <chris/> only run checks for current version ?
            // if (xarVersion::compare($check_version, $dbversion) != 0) continue;
            if (!Upgrader::loadFile('checks/' . $abbr_version .'/main.php')) {
                $checks[$check_version]['message'] = xarML('There are no checks for version #(1)', $check_version);
                $checks[$check_version]['tasks'] = array();
                //return $data;
            } else {
                $check_function = 'main_check_' . $abbr_version;
                $result = $check_function();
                $checks[$check_version] = $result['check'];
            }
        }
        $data['checks'] =& $checks;

    } elseif ($data['phase'] == 5) {
        $data['active_step'] = 5;
//        xarController::redirect(xarServer::getCurrentURL(array('phase' => 5)));
    }
    return $data;
}

?>
