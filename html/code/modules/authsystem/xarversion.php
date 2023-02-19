<?php
/**
 * Configuration information for the Authsystem module
 *
 * @package modules\authsystem
 * @subpackage authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/42.html
 *
 * @author Marco Canini
 */

/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */
 
$modversion = array(
    'name'               => 'authsystem',
    'displayname'        => xarML('Authsystem'),
    'id'                 => '42',
    'version'            => '2.4.1',
    'description'        => 'Xaraya default authentication module',
    'displaydescription' => xarML('Xaraya default authentication module'),
    'credits'            => 'xardocs/credits.txt',
    'help'               => 'xardocs/help.txt',
    'changelog'          => 'xardocs/changelog.txt',
    'license'            => 'docs/license.txt',
    'official'           => true,
    'author'             => 'Marco Canini, Jo Dalle Nogare',
    'contact'            => 'http://www.xaraya.com/',
    'admin'              => true,
    'user'               => true,
    'class'              => 'Authentication',
    'category'           => 'Users & Groups',
);