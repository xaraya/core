<?php
// TODO: turn this into an xml file
	function roles_dataapi_adminmenu() {
		return array(
				array('includes' => array('main','overview'), 'target' => 'overview', 'title' => xarML('Roles Overview'), 'label' => xarML('Overview')),
				array('mask' => 'EditRole', 'includes' => array('showusers','displayrole','modifyrole','deleterole','showprivileges','testprivileges'), 'target' => 'showusers', 'title' => xarML('View and edit all groups/users on the system'), 'label' => xarML('Groups &amp; Users')),
				array('mask' => 'AdminRole', 'includes' => array('createmail','modifyemail','modifynotice'), 'target' => 'createmail', 'title' => xarML('Manage system emails'), 'label' => xarML('Messaging')),
				array('mask' => 'AdminRole', 'includes' => 'purge', 'target' => 'purge', 'title' => xarML('Undelete or permanently remove users/groups'), 'label' => xarML('Recall/Purge')),
				array('mask' => 'AdminRole', 'includes' => 'sitelock', 'target' => 'sitelock', 'title' => xarML('Lock the site to all but selected users'), 'label' => xarML('SiteLock')),
				array('mask' => 'AdminRole', 'includes' => 'modifyconfig', 'target' => 'modifyconfig', 'title' => xarML('Modify the roles module configuration'), 'label' => xarML('Modify Config')),
		);
	}
?>