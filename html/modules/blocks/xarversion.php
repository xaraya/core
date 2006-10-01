<?php
/**
 * Blocks initialization
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 * @author Jim McDonald, Paul Rosania
*/

/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */
$modversion['name'] = 'Blocks Administration';
$modversion['id'] = '13';
$modversion['displayname'] = xarML('Blocks');
$modversion['version'] = '1.0.1';
$modversion['description'] = 'Administration of block instances and groups';
$modversion['displaydescription'] = xarML('Administration of block instances and groups');
$modversion['credits'] = '';
$modversion['help'] = '';
$modversion['changelog'] = '';
$modversion['license'] = '';
$modversion['official'] = 1;
$modversion['author'] = 'Jim McDonald, Paul Rosania';
$modversion['contact'] = 'http://www.mcdee.net/, paul@xaraya.com';
$modversion['admin'] = 1;
$modversion['user'] = 0;
$modversion['securityschema'] = array('Blocks::Group'    => 'Group name::Group ID',
                                      'Blocks::Instance' => 'Block type:Block title:Block ID');
$modversion['class'] = 'Core Admin';
$modversion['category'] = 'Global';
?>
