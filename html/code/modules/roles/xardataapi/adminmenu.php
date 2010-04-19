<?php
// TODO: turn this into an xml file
    function roles_dataapi_adminmenu()
    {
        return array(
                array('includes' => array('main','overview'), 'target' => 'main', 'title' => xarML('Roles Overview'), 'label' => xarML('Overview')),
                array('mask' => 'EditRoles', 'includes' => array('showusers','display','modify','delete','showprivileges','testprivileges'), 'target' => 'showusers', 'title' => xarML('View and edit all groups/users on the system'), 'label' => xarML('Groups &amp; Users')),
                array('mask' => 'ManageRoles', 'includes' => array('createmail','modifyemail','modifynotice'), 'target' => 'createmail', 'title' => xarML('Manage system emails'), 'label' => xarML('Messaging')),
                array('mask' => 'ManageRoles', 'includes' => 'purge', 'target' => 'purge', 'title' => xarML('Undelete or permanently remove users/groups'), 'label' => xarML('Recall/Purge')),
                array('mask' => 'ManageRoles', 'includes' => 'sitelock', 'target' => 'sitelock', 'title' => xarML('Lock the site to all but selected users'), 'label' => xarML('SiteLock')),
                array('mask' => 'AdminRoles', 'includes' => 'modifyconfig', 'target' => 'modifyconfig', 'title' => xarML('Modify the roles module configuration'), 'label' => xarML('Modify Configuration')),
        );
    }
?>