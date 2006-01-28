<?php
/**
 * Initialization function
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */

/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */
$modversion['name'] = 'Privileges Adminstration';
$modversion['id'] = '1098';
$modversion['version'] = '1.0.1';
$modversion['displayname'] = xarML('Privileges');
$modversion['description'] = 'Modify privileges security';
$modversion['displaydescription'] = xarML('Modify privileges security');
$modversion['official'] = 1;
$modversion['author'] = 'Marc Lutolf';
$modversion['contact'] = 'http://www.xaraya.com/';
$modversion['admin'] = 1;
$modversion['user'] = 0;
$modversion['securityschema'] = array('Privileges::' => 'name:pid');
$modversion['class'] = 'Core Complete';
$modversion['category'] = 'Users & Groups';
?>