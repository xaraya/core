<?php
// TODO: turn this into an xml file
    function privileges_dataapi_adminmenu()
    {
        return array(
                array('includes' => array('main','overview'), 'target' => 'main', 'label' => xarML(' Privileges Overview')),
                array('mask' => 'EditPrivileges', 'includes' => 'viewprivileges', 'target' => 'viewprivileges', 'title' => xarML('View all privileges on the system'), 'label' => xarML('View Privileges')),
                array('mask' => 'AddPrivileges', 'includes' => 'new', 'target' => 'new', 'title' => xarML('Add a new privilege to the system'), 'label' => xarML('Add Privilege')),
                array('mask' => 'ManagePrivileges', 'includes' => 'assignprivileges', 'target' => 'assignprivileges', 'title' => xarML('Assign privileges to groups and users'), 'label' => xarML('Assign Privileges')),
                array('mask' => 'AdminPrivileges', 'includes' => array('viewrealms','newrealm','modifyrealm','deleterealm'), 'target' => 'viewrealms', 'title' => xarML('Add, change or delete realms'), 'label' => xarML('Manage Realms')),
                array('mask' => 'AdminPrivileges', 'includes' => 'modifyconfig', 'target' => 'modifyconfig', 'title' => xarML('Modify the privileges module configuration'), 'label' => xarML('Modify Configuration')),
        );
    }
?>