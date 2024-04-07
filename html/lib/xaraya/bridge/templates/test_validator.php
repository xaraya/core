<?php
/**
 * Test Script for Twig Validator tests
 */

namespace Xaraya\Bridge\TemplateEngine;

use xarCache;
use xarDatabase;
use xarTwigTpl;
use sys;

if (php_sapi_name() !== 'cli') {
    echo 'Test Script for Twig Validator tests';
    return;
}

$baseDir = dirname(__DIR__, 5);
require_once $baseDir . '/vendor/autoload.php';
chdir($baseDir . '/html');

// initialize bootstrap
sys::init();
// initialize database to call xarMod::apiFunc() for namespaces
xarCache::init();
xarDatabase::init();

// validate all *.html.twig templates for includes directory
/**
$options = [
    'namespace' => 'workflow/includes',
    //'extension' => '.html.twig',
];
$validator = new TwigValidator($options);
$targetPath = sys::root() . '/templates/twig/workflow/includes';

$validator->validateDir($targetPath);
 */

class TestValidator
{
    public string $baseDir;

    public function __construct(string $baseDir)
    {
        $this->baseDir = $baseDir;
    }

    public function validateModule(string $module)
    {
        // validate all *.html.twig templates for $module
        $options = [
            'namespace' => $module,
        ];
        $validator = new TwigValidator($options);
        $targetPath = $this->baseDir . '/templates/twig/code/modules/' . $module;

        return $validator->validateDir($targetPath);
    }

    public function validateTheme(string $theme)
    {
        // use @theme (singular) namespace for themes
        $options = [
            'namespace' => '@theme/' . $theme,
        ];
        // use .xml.twig extension for rss theme
        if ($theme == 'rss') {
            $options['extension'] = '.xml.twig';
        }
        $validator = new TwigValidator($options);
        $targetPath = $this->baseDir . '/templates/twig/themes/' . $theme;

        return $validator->validateDir($targetPath);
    }

    public function validateProperties()
    {
        // validate all *.html.twig templates for properties
        $options = [
            'namespace' => 'properties',
        ];
        $validator = new TwigValidator($options);
        $targetPath = $this->baseDir . '/templates/twig/code/properties';

        return $validator->validateDir($targetPath);
    }
}

$tester = new TestValidator($baseDir);

$namespaces = xarTwigTpl::getNamespaces();
foreach ($namespaces as $module => $path) {
    if (!str_contains($path, 'code/modules/')) {
        continue;
    }
    //$dependencies = $tester->validateModule($module);
    //var_export($dependencies);
}

$themes = ['common', 'default', 'rss', 'print', 'installer'];
$subDir = '';  // 'pages';
foreach ($themes as $theme) {
    //$dependencies = $tester->validateTheme($theme);
    //var_export($dependencies);
}

$dependencies = $tester->validateProperties();
