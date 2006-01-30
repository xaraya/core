<?php
/**
 * Initialise the Authsystem module
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 * @author Marco Canini
 */

/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */
$modversion['name'] = 'authsystem';
$modversion['displayname'] = xarML('System Authentication');
$modversion['id'] = '42';
$modversion['version'] = '0.91.0';
$modversion['description'] = 'Xaraya default authentication module';
$modversion['displaydescription'] = xarML('Xaraya default authentication module');
$modversion['credits'] = 'xardocs/credits.txt';
$modversion['help'] = 'xardocs/help.txt';
$modversion['changelog'] = 'xardocs/changelog.txt';
$modversion['license'] = 'docs/license.txt';
$modversion['official'] = 1;
$modversion['author'] = 'Marco Canini';
$modversion['contact'] = 'marco.canini@xaraya.com';
$modversion['admin'] = 1;
$modversion['user'] = 0;
$modversion['securityschema'] = array();
$modversion['class'] = 'Authentication';
$modversion['category'] = 'Global';
?>