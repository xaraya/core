<?php 
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: mikespub
// Purpose of file:  Module Information
// ----------------------------------------------------------------------

$modversion['name'] = 'Dynamic Data';
$modversion['id'] = '182';
$modversion['version'] = '1.0';
$modversion['description'] = 'Dynamic Data Module';
$modversion['credits'] = 'pndocs/credits.txt';
$modversion['help'] = 'pndocs/help.txt';
$modversion['changelog'] = 'pndocs/changelog.txt';
$modversion['license'] = 'pndocs/license.txt';
$modversion['official'] = 1;
$modversion['author'] = 'mikespub';
$modversion['contact'] = 'http://www.xaraya.com/';
$modversion['admin'] = 1;
$modversion['user'] = 1;
$modversion['securityschema'] = array('DynamicData::Item' => 'ModuleID:ItemType:ItemID',
                                      'DynamicData::Field' => 'FieldName:FieldType:FieldID');
$modversion['class'] = 'Complete';
$modversion['category'] = 'Content';
?>
