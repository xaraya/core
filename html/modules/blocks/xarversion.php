<?php 
// $Id: s.xarversion.php 1.10 03/01/17 16:29:55+00:00 johnny@falling.local.lan $
$modversion['name'] = 'Blocks administration';
$modversion['id'] = '13';
$modversion['version'] = '1.0';
$modversion['description'] = 'Administration of block instances and groups';
$modversion['credits'] = '';
$modversion['help'] = '';
$modversion['changelog'] = '';
$modversion['license'] = '';
$modversion['official'] = 1;
$modversion['author'] = 'Jim McDonald, Paul Rosania';
$modversion['contact'] = 'http://www.mcdee.net/, paul@xaraya.com';
$modversion['admin'] = 1;
$modversion['user'] = 0;
$modversion['securityschema'] = array('Blocks::Group'    => 'Group name::Group ID',
                                      'Blocks::Instance' => 'Block type:Block title:Block ID');
$modversion['class'] = 'Core Admin';
$modversion['category'] = 'Global';
?>