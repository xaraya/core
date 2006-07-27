<?php
// TODO: turn this into an xml file
    function authsystem_dataapi_adminmenu() 
    {
        return array(
            array('includes' => array('main','overview'), 'target' => 'overview', 'label' => xarML('Authsystem Overview')),
            array('mask' => 'AdminAuthsystem', 'includes' => 'modifyconfig', 'target' => 'modifyconfig', 'title' => xarML('Modify the Authsystem authentication configuration'), 'label' => xarML('Modify Configuration')),
        );
    }
?>