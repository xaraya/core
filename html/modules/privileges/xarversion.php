<?php
/**
 * $Id: s.xarversion.php 1.8 02/11/28 18:19:02-05:00 John.Cox@mcnabb. $
 *
 * Privileges Adminstration
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */

$modversion['name'] = 'Privileges Adminstration';
$modversion['id'] = '1098';
$modversion['version'] = '0.1.0';
$modversion['description'] = 'Modify privileges security';
$modversion['official'] = 1;
$modversion['author'] = 'Marc Lutolf';
$modversion['contact'] = 'http://www.xaraya.com/';
$modversion['admin'] = 1;
$modversion['user'] = 0;
$modversion['securityschema'] = array('Privileges::' => 'name:pid');
$modversion['class'] = 'Core Complete';
$modversion['category'] = 'Users & Groups';
?>