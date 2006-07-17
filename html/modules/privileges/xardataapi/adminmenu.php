<?php
// TODO: turn this into an xml file
    function privileges_dataapi_adminmenu() 
    {
        return array(
                array('includes' => array('main','overview'), 'target' => 'overview', 'label' => xarML(' Privileges Overview')),
                array('mask' => 'ReadPrivilege', 'includes' => 'viewprivileges', 'target' => 'viewprivileges', 'title' => xarML('View all privileges on the system'), 'label' => xarML('View Privileges')),
                array('mask' => 'AddPrivilege', 'includes' => 'newprivilege', 'target' => 'newprivilege', 'title' => xarML('Add a new privilege to the system'), 'label' => xarML('Add Privilege')),
                array('mask' => 'AdminPrivilege', 'includes' => array('viewrealms','newrealm','modifyrealm','deleterealm'), 'target' => 'viewrealms', 'title' => xarML('Add, change or delete realms'), 'label' => xarML('Manage Realms')),
                array('mask' => 'AdminPrivilege', 'includes' => 'modifyconfig', 'target' => 'modifyconfig', 'title' => xarML('Modify the privileges module configuration'), 'label' => xarML('Modify Config')),
        );
    }
?>