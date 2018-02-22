<?php
/**
 * Configuration information for the Mail module
 *
 * @package modules\mail
 * @subpackage mail
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/771.html
 *
 * @author John Cox <admin@dinerminor.com>
 */

/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */
 
$modversion = array(
    'name'               => 'Mail',
    'id'                 => '771',
    'displayname'        => xarML('Mail'),
    'version'            => '2.4.0',
    'description'        => 'Ma4l handling utility module',
    'displaydescription' => xarML('Mail handling utility module'),
    'credits'            => 'xardocs/credits.txt',
    'help'               => 'xardocs/help.txt',
    'changelog'          => 'xardocs/changelog.txt',
    'license'            => 'xardocs/license.txt',
    'official'           => true,
    'author'             => 'John Cox via phpMailer',
    'contact'            => 'http://www.xaraya.com/',
    'admin'              => true,
    'user'               => false,
    'class'              => 'Core Complete',
    'category'           => 'System',
);
?>