<?php
$modversion['name'] = 'carts';
$modversion['id'] = '30202';
$modversion['version'] = '0.5.0';
$modversion['displayname'] = xarML('Carts');
$modversion['description'] = 'Shopping Cart Module';
$modversion['credits'] = 'xardocs/credits.txt';
$modversion['help'] = 'xardocs/help.txt';
$modversion['changelog'] = 'xardocs/changelog.txt';
$modversion['license'] = 'xardocs/license.txt';
$modversion['official'] = 1;
$modversion['author'] = 'Marc Lutolf';
$modversion['contact'] = 'http://www.xaraya.com/';
$modversion['admin'] = 1;
$modversion['user'] = 0;
$modversion['securityschema'] = array('Carts::' => '::');
$modversion['class'] = 'Complete';
$modversion['category'] = 'Content';
// this module depends on the categories module
// this module depends on the xen module
// this module depends on the commerce module
// this module depends on the products module
$modversion['dependency'] = array(147,3005,3006,30201);
$modversion['dependencyinfo'] = array(147 => 'categories',
									  3005 => 'xen',
									  3006 => 'commerce',
									  30201 => 'products');
?>