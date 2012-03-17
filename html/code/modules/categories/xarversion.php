<?php
/**
 * Categories System
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 * @subpackage categories module
 * @author Jim McDonald, Flávio Botelho <nuncanada@xaraya.com>, mikespub <postnuke@mikespub.net>
*/
    $modversion['name']           = 'categories';
    $modversion['id']             = '147';
    $modversion['version']        = '2.3.0';
    $modversion['displayname']    = xarML('Categories');
    $modversion['description']    = 'Categorised data utility';
    $modversion['credits']        = 'xardocs/credits.txt';
    $modversion['help']           = 'xardocs/help.txt';
    $modversion['changelog']      = 'xardocs/changelog.txt';
    $modversion['license']        = 'xardocs/license.txt';
    $modversion['official']       = true;
    $modversion['author']         = 'Jim McDonald';
    $modversion['contact']        = 'http://www.mcdee.net/';
    $modversion['admin']          = true;
    $modversion['user']           = false;
    $modversion['class']          = 'Core Complete';
    $modversion['category']       = 'Content';
    $modversion['securityschema'] = array('categories::category' => 'Category name::Category ID',
                                      'categories::item' => 'Category ID:Module ID:Item ID');
?>