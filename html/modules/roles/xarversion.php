<?php
// $Id: s.xarversion.php 1.7 02/08/09 19:00:48-00:00 johnny $
$modversion['name'] = 'roles';
$modversion['id'] = '27';
$modversion['version'] = '1.01';
$modversion['description'] = 'User and Group registration and handling';
$modversion['credits'] = 'xardocs/credits.txt';
$modversion['help'] = 'xardocs/help.txt';
$modversion['changelog'] = 'xardocs/changelog.txt';
$modversion['license'] = 'xardocs/license.txt';
$modversion['official'] = 1;
$modversion['author'] = 'Jim McDonald, Marco Canini, Jan Schrage';
$modversion['contact'] = 'http://www.mcdee.net/, marco@xaraya.com, jan@xaraya.com';
$modversion['admin'] = 1;
$modversion['user'] = 1;
$modversion['user_menu'] = 1;
$modversion['securityschema'] = array('roles::' => 'Role uname::Role pid',
                                      'users::Field' => '::',
				      'roles::Variables' => 'Role variable name::');
$modversion['class'] = 'Core Complete';
$modversion['category'] = 'Users & Groups';
?>