<?php 
/**
 * File: $Id$
 *
 * Dynamic Data Version Information
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/

$modversion['name'] = 'Dynamic Data';
$modversion['id'] = '182';
$modversion['version'] = '1.1';
$modversion['description'] = 'Dynamic Data Module';
$modversion['credits'] = 'xardocs/credits.txt';
$modversion['help'] = 'xardocs/help.txt';
$modversion['changelog'] = 'xardocs/changelog.txt';
$modversion['license'] = 'xardocs/license.txt';
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
