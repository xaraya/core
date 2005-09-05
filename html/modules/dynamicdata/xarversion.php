<?php
/**
 * File: $Id$
 *
 * Dynamic Data Version Information
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * 
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/

$modversion['name'] = 'Dynamic Data';
$modversion['id'] = '182';
$modversion['version'] = '1.1.0';
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
$modversion['class'] = 'Core Complete';
$modversion['category'] = 'Content';
?>