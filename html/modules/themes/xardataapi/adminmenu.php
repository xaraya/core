<?php
// TODO: turn this into an xml file
    function themes_dataapi_adminmenu()
    {
        return array(
                array('includes' => array('main','overview'), 'target' => 'overview', 'label' => xarML('Themes Overview')),
                array('mask' => 'AdminTheme', 'includes' => 'list', 'target' => 'list', 'title' => xarML('View installed themes on the system'), 'label' => xarML('View Themes')),
                array('mask' => 'AdminTheme', 'includes' => 'modifyconfig', 'target' => 'modifyconfig', 'title' => xarML('Modify the configuration of the themes module'), 'label' => xarML('Modify Config')),
        );
    }
?>
