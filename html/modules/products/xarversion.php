<?php
$modversion['name'] = 'products';
$modversion['id'] = '30201';
$modversion['version'] = '0.5.0';
$modversion['displayname'] = xarML('Products');
$modversion['description'] = 'Product and Catalog Module';
$modversion['credits'] = 'xardocs/credits.txt';
$modversion['help'] = 'xardocs/help.txt';
$modversion['changelog'] = 'xardocs/changelog.txt';
$modversion['license'] = 'xardocs/license.txt';
$modversion['official'] = 1;
$modversion['author'] = 'Marc Lutolf';
$modversion['contact'] = 'http://www.xaraya.com/';
$modversion['admin'] = 1;
$modversion['user'] = 1;
$modversion['securityschema'] = array('Products::' => '::');
$modversion['class'] = 'Complete';
$modversion['category'] = 'Content';
// this module depends on the categories module
// this module depends on the articles module
// this module depends on the xen module
// this module depends on the commerce module
$modversion['dependency'] = array(147,151,3005,3006);
$modversion['dependencyinfo'] = array(147 => 'categories',
									  151 => 'articles',
									  3005 => 'xen',
									  3006 => 'commerce');
?>