<?php
// TODO: turn this into an xml file
    function base_dataapi_adminmenu()
    {
        return array(
                array('includes' => array('main','overview'), 'target' => 'overview', 'label' => xarML('Base Overview')),
                array('mask' => 'AdminBase', 'includes' => array('new','sysinfo'), 'target' => 'sysinfo', 'title' => xarML('View your PHP configuration'), 'label' => xarML('System Information')),
                array('mask' => 'AdminBase', 'includes' => 'release', 'target' => 'release', 'title' => xarML('View recent released extensions'), 'label' => xarML('Latest Xaraya Releases')),
                array('mask' => 'AdminBase', 'includes' => 'modifyconfig', 'target' => 'modifyconfig', 'title' => xarML('Modify Base configuration values'), 'label' => xarML('Modify Config')),
        );
    }
?>
