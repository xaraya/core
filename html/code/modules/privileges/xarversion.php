<?php
/**
 * Configuration information for the Privileges module
 *
 * @package modules
 * @subpackage privileges module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/1098.html
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */

/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */
 
$modversion['name']               = 'Privileges Adminstration';
$modversion['id']                 = '1098';
$modversion['version']            = '2.4.0';
$modversion['displayname']        = xarML('Privileges');
$modversion['description']        = 'Modify privileges security';
$modversion['displaydescription'] = xarML('Modify privileges security');
$modversion['official']           = true;
$modversion['author']             = 'Marc Lutolf';
$modversion['contact']            = 'http://www.xaraya.com/';
$modversion['admin']              = true;
$modversion['user']               = false;
$modversion['class']              = 'Core Complete';
$modversion['category']           = 'Users & Groups';
$modversion['securityschema']     = array('Privileges::' => 'name:id');
?>