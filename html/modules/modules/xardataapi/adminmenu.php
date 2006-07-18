<?php
// TODO: turn this into an xml file
    function modules_dataapi_adminmenu() 
    {
        return array(
                array('includes' => array('main','overview'), 'target' => 'overview', 'label' => xarML('Modules Overview')),
                array('mask' => 'AdminModules', 'includes' => 'list', 'target' => 'list', 'title' => xarML('View list of all installed modules on the system'), 'label' => xarML('View Available Modules')),
                array('mask' => 'AdminModules', 'includes' => 'hooks', 'target' => 'hooks', 'title' => xarML('Extend the functionality of your modules via hooks'), 'label' => xarML('Configure Hooks')),
                array('mask' => 'AdminModules', 'includes' => 'modifyconfig', 'target' => 'modifyconfig', 'title' => xarML('Modify configuration parameters'), 'label' => xarML('Modify Config')),
        );
    }
?>
