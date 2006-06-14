<?php
/**
 * Initialization function 
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail System
 * @link http://xaraya.com/index.php/release/771.html
 * @author John Cox <admin@dinerminor.com>
 */

/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */
$modversion['name']           = 'Mail';
$modversion['id']             = '771';
$modversion['displayname']    = xarML('Mail');
$modversion['version']        = '2.0.0';
$modversion['description']    = 'Mail handling utility module';
$modversion['displaydescription']    = xarML('Mail handling utility module');
$modversion['credits']        = 'xardocs/credits.txt';
$modversion['help']           = 'xardocs/help.txt';
$modversion['changelog']      = 'xardocs/changelog.txt';
$modversion['license']        = 'xardocs/license.txt';
$modversion['official']       = 1;
$modversion['author']         = 'John Cox via phpMailer';
$modversion['contact']        = 'niceguyeddie@xaraya.com';
$modversion['admin']          = 1;
$modversion['user']           = 0;
$modversion['securityschema'] = array('mail::' => '::');
$modversion['class']          = 'Core Complete';
$modversion['category']       = 'Global';

?>
