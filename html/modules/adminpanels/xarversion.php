<?php
/**
 * File: $Id: s.xarversion.php 1.13 03/01/22 23:13:25+00:00 andyv@andyv.plus.com $
 *
 * Administration System
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage adminpanels module
 * @author Andy Varganov <andyv@xaraya.com>
*/

/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */
$modversion['name'] = 'Adminpanels';
$modversion['description'] = 'Taking care of the admin navigation';
$modversion['displayname'] = xarML('Admin Panels');
$modversion['displaydescription'] = xarML('Taking care of the admin navigation');
$modversion['id'] = '9';
$modversion['version'] = '1.2.2';
$modversion['official'] = 1;
$modversion['author'] = 'Andy Varganov';
$modversion['contact'] = 'andyv@xaraya.com';
$modversion['admin'] = 1;
$modversion['user'] = 0;
$modversion['securityschema'] = array('adminpanels::' => '::');
$modversion['class'] = 'Core Admin';
$modversion['category'] = 'Global';
?>
