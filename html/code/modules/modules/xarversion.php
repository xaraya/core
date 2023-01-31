<?php
/**
 * Configuration information for the Modules module
 *
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 */

/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */
 
$modversion = array(
    'name'               => 'Modules Administration',
    'id'                 => '1',
    'version'            => '2.4.1',
    'displayname'        => xarMLS::translate('Modules'),
    'description'        => 'Configure modules, view install/docs/credits.',
    'displaydescription' => xarMLS::translate('Configure modules, view install/docs/credits.'),
    'credits'            => 'xardocs/credits.txt',
    'help'               => '',
    'changelog'          => 'xardocs/changelog.txt',
    'license'            => '',
    'official'           => true,
    'author'             => 'Jim McDonald',
    'contact'            => 'http://www.xaraya.com/',
    'admin'              => true,
    'user'               => false,
    'class'              => 'Core Admin',
    'category'           => 'System',
);
