<?php
/**
 * DynamicData Module Test Script for Blocklayout Converter tests
 *
 * @package modules
 * @copyright (C) copyright-placeholder
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage DynamicData Module
 * @link http://xaraya.com/index.php/release/188.html
 * @author DynamicData Module Development Team
 */
use Xaraya\Bridge\TemplateEngine\BlocklayoutToTwigConverter;

if (php_sapi_name() !== 'cli') {
    echo 'DynamicData Module Test Script for Blocklayout Converter tests';
    return;
}

$baseDir = dirname(__DIR__, 5);
require_once $baseDir . '/vendor/autoload.php';

// convert all test_*.xt templates from includes directory
/**
$options = [
    'namespace' => 'workflow/includes',
];
$converter = new BlocklayoutToTwigConverter($options);
$sourcePath = dirname(__DIR__) . '/xartemplates/includes';
$targetPath = dirname(__DIR__) . '/templates/includes';
$converter->convertDir($sourcePath, $targetPath, '.xt', 'test_');
 */

$module = 'dynamicdata';
//$module = 'base';
//$module = 'themes';
//$module = 'workflow';

// convert all *.xt templates from dynamicdata module
$options = [
    'namespace' => $module,
];
$converter = new BlocklayoutToTwigConverter($options);
$sourcePath = $baseDir . '/html/code/modules/' . $module . '/xartemplates';
$targetPath = $baseDir . '/html/code/modules/' . $module . '/templates';
$converter->convertDir($sourcePath, $targetPath, '.xt');
