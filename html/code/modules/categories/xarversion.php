<?php

/**
 * Categories System
 *
 * @package modules\categories
 * @subpackage categories
 * @category Xaraya Web Applications Framework
 * @version 2.8.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/147.html
 *
 * @author Jim McDonald, Flavio Botelho <nuncanada@xaraya.com>, mikespub <postnuke@mikespub.net>
*/
$modversion = [
    'name'           => 'categories',
    'id'             => '147',
    'version'        => '2.8.1',
    'displayname'    => xarMLS::translate('Categories'),
    'description'    => 'Categorised data utility',
    'credits'        => 'xardocs/credits.txt',
    'help'           => 'xardocs/help.txt',
    'changelog'      => 'xardocs/changelog.txt',
    'license'        => 'xardocs/license.txt',
    'official'       => true,
    'author'         => 'Jim McDonald',
    'contact'        => 'http://www.mcdee.net/',
    'admin'          => true,
    'user'           => false,
    'class'          => 'Core Complete',
    'category'       => 'Content',
    'securityschema' => ['categories::category' => 'Category name::Category ID',
        'categories::item' => 'Category ID:Module ID:Item ID'],
    'twigtemplates'  => true,
];
