<?php
/**
 * Dynamic Data Initialization
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */

/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */
$modversion['name'] = 'Dynamic Data';
$modversion['id'] = '182';
$modversion['displayname'] = xarML('Dynamic Data');
$modversion['version'] = '1.2.1';
$modversion['description'] = 'Dynamic Data Module';
$modversion['displaydescription'] = xarML('Dynamic Data Module');
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