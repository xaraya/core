<?php
/**
 * Roles module initialization
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @author Jan Schrage, John Cox, Gregor Rothfuss
 */

/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */
$modversion['name']           = 'Roles';
$modversion['id']             = '27';
$modversion['version']        = '1.1.0';
$modversion['displayname']    = xarML('Roles');
$modversion['description']    = 'User and Group registration and handling';
$modversion['displaydescription'] = xarML('User and Group registration and handling');
$modversion['credits']        = 'xardocs/credits.txt';
$modversion['help']           = 'xardocs/help.txt';
$modversion['changelog']      = 'xardocs/changelog.txt';
$modversion['license']        = 'xardocs/license.txt';
$modversion['official']       = 1;
$modversion['author']         = 'Jim McDonald, Marco Canini, Jan Schrage, Camille Perinel';
$modversion['contact']        = 'http://www.mcdee.net/, marco@xaraya.com, jan@xaraya.com, kams@xaraya.com';
$modversion['admin']          = 1;
$modversion['user']           = 1;
$modversion['securityschema'] = array('roles::' => 'Role uname:Role uid');
$modversion['class']          = 'Core Complete';
$modversion['category']       = 'Users & Groups';
?>
