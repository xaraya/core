<?php
/**
 * Configuration information for the Roles module
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 *
 * @author Jan Schrage
 * @author John Cox
 * @author Gregor Rothfuss
 */

/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */
 
$modversion = array(
    'name'               => 'Roles',
    'id'                 => '27',
    'version'            => '2.4.1',
    'displayname'        => xarML('Roles'),
    'description'        => 'User and Group management',
    'displaydescription' => xarML('User and Group management'),
    'credits'            => 'xardocs/credits.txt',
    'help'               => 'xardocs/help.txt',
    'changelog'          => 'xardocs/changelog.txt',
    'license'            => 'xardocs/license.txt',
    'official'           => true,
    'author'             => 'Jim McDonald, Marco Canini, Jan Schrage, Camille Perinel',
    'contact'            => 'http://www.xaraya.com',
    'admin'              => true,
    'user'               => true,
    'class'              => 'Core Complete',
    'category'           => 'Users & Groups',
);
