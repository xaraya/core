<?php
/**
 * Test Script for Blocklayout Converter tests
 */

namespace Xaraya\Bridge\TemplateEngine;

use xarTwigTpl;
use sys;

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
$sourcePath = sys::root() . '/html/code/modules/workflow/xartemplates/includes';
$targetPath = sys::root() . '/templates/twig/workflow/includes';
$converter->convertDir($sourcePath, $targetPath, '.xt', 'test_');
 */

class TestConverter
{
    public string $baseDir;

    public function __construct(string $baseDir)
    {
        $this->baseDir = $baseDir;
    }

    public function convertModule(string $module, string $subDir = '', string $prefix = '')
    {
        // convert all *.xt templates from $module
        $options = [
            'namespace' => $module,
        ];
        $converter = new BlocklayoutToTwigConverter($options);
        $sourcePath = $this->baseDir . '/html/code/modules/' . $module . '/xartemplates';
        $targetPath = $this->baseDir . '/templates/twig/code/modules/' . $module;
        if (!empty($subDir)) {
            $options['namespace'] .= '/' . $subDir;
            $sourcePath .= '/' . $subDir;
            $targetPath .= '/' . $subDir;
        }
        if (!is_dir($targetPath)) {
            mkdir($targetPath);
        }
        $converter->convertDir($sourcePath, $targetPath, '.xt', $prefix);
    }

    public function convertTheme(string $theme, string $subDir = '', string $prefix = '')
    {
        // no namespace for themes pages etc.
        $options = [];
        // use .xml.twig extension for rss theme
        if ($theme == 'rss') {
            $options['extension'] = '.xml.twig';
        }
        $converter = new BlocklayoutToTwigConverter($options);
        $sourcePath = $this->baseDir . '/html/themes/' . $theme;
        $targetPath = $this->baseDir . '/templates/twig/themes/' . $theme;
        if (!empty($subDir)) {
            $sourcePath .= '/' . $subDir;
            $targetPath .= '/' . $subDir;
        }
        $converter->convertDir($sourcePath, $targetPath, '.xt');
    }
}

$tester = new TestConverter($baseDir);

$namespaces = xarTwigTpl::getNamespaces();
foreach ($namespaces as $module => $path) {
    if (!str_contains($path, 'code/modules/')) {
        continue;
    }
    $tester->convertModule($module);
}

$themes = ['common', 'default', 'rss', 'print', 'installer'];
$subDir = '';  // 'pages';
foreach ($themes as $theme) {
    $tester->convertTheme($theme, $subDir);
}
