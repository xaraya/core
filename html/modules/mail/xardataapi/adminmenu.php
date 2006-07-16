<?php
// TODO: turn this into an xml file
	function mail_dataapi_adminmenu() {
		return array(
				array('includes' => array('main','overview'), 'target' => 'overview', 'label' => xarML('Mail Overview')),
				array('mask' => 'AdminMail', 'includes' => 'compose', 'target' => 'compose', 'title' => xarML('Test your email configuration'), 'label' => xarML('Test Configuration')),
				array('mask' => 'AdminMail', 'includes' => 'view', 'target' => 'view', 'title' => xarML('Manage queues for mail item handling'), 'label' => xarML('Queue Management')),
				array('mask' => 'AdminMail', 'includes' => 'template', 'target' => 'template', 'title' => xarML('Change the mail template for notifications'), 'label' => xarML('Notification Template')),
				array('mask' => 'AdminMail', 'includes' => 'modifyconfig', 'target' => 'modifyconfig', 'title' => xarML('Modify the configuration for the utility mail module'), 'label' => xarML('Modify Config')),
		);
	}
?>