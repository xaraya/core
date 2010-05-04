<?php
function installer_admin_upgrade()
{
    if(!xarVarFetch('phase','int', $data['phase'], 1, XARVAR_DONT_SET)) {return;}

    // Version information
    $fileversion = XARCORE_VERSION_NUM;
    $dbversion = xarConfigVars::get(null, 'System.Core.VersionNum');
    sys::import('xaraya.version');
    
    // Versions prior to 2.1.0 had the revision number as version number
    if (strlen($dbversion) == 41) {
        $data['versioncompare'] = 1;
        $data['upgradable'] = 0;
    } else {
        $data['versioncompare'] = xarVersion::compare($fileversion, $dbversion);
        $data['upgradable'] = xarVersion::compare($dbversion, '2.0.0') > 0;
    }
    
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
                                '2.1.0',
    );
    
    if ($data['phase'] == 1) {
        $data['active_step'] = 1;

    } elseif ($data['phase'] == 2) {
        $data['active_step'] = 2;
        if (!Upgrader::loadFile('upgrades/210/main.php')) {
            $data['upgrade']['errormessage'] = Upgrader::$errormessage;
            return $data;
        }
        $data = array_merge($data,main_210());
        
    } elseif ($data['phase'] == 3) {
        $data['active_step'] = 3;
    } elseif ($data['phase'] == 4) {
        $data['active_step'] = 4;
    }

    return $data;
}

?>