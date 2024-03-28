<?php
/**
 * Test Script for Blocklayout Converter tests
 */
use Xaraya\Bridge\TemplateEngine\BlocklayoutToTwigConverter;

if (php_sapi_name() !== 'cli') {
    echo 'Test Script for Blocklayout Converter tests';
    return;
}

$baseDir = dirname(__DIR__, 5);
require_once $baseDir . '/vendor/autoload.php';

// initialize bootstrap
sys::init();

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

function twig_convert_module($module, $baseDir)
{
    // convert all *.xt templates from $module
    $options = [
        'namespace' => $module,
    ];
    $converter = new BlocklayoutToTwigConverter($options);
    $sourcePath = $baseDir . '/html/code/modules/' . $module . '/xartemplates';
    $targetPath = $baseDir . '/html/code/modules/' . $module . '/templates';
    $converter->convertDir($sourcePath, $targetPath, '.xt');

    chdir($baseDir . '/html');
    $twig = xarTwigTpl::getTwig();
    $converter->validate($twig);
}

$todo = ['dynamicdata', 'base', 'themes', 'workflow'];
foreach ($todo as $module) {
    twig_convert_module($module, $baseDir);
}

function twig_convert_theme($theme, $baseDir, $subDir = '')
{
    // no namespace for themes pages etc.
    $options = [];
    // use .xml.twig extension for rss theme
    if ($theme == 'rss') {
        $options['extension'] = '.xml.twig';
    }
    $converter = new BlocklayoutToTwigConverter($options);
    $sourcePath = $baseDir . '/html/themes/' . $theme;
    $targetPath = $baseDir . '/html/themes/' . $theme;
    if (!empty($subDir)) {
        $sourcePath .= '/' . $subDir;
        $targetPath .= '/' . $subDir;
    }
    $converter->convertDir($sourcePath, $targetPath, '.xt');

    chdir($baseDir . '/html');
    $twig = xarTwigTpl::getTwig();
    $converter->validate($twig);
}

$theme = 'default';
$subDir = '';  // 'pages';
twig_convert_theme($theme, $baseDir, $subDir);
