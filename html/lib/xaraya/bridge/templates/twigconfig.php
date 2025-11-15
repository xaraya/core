<?php

/**
 * Use Twig template engine for output in Xaraya
 */

namespace Xaraya\Bridge\TemplateEngine;

use Twig\Environment;
use Xaraya\Context\Context;
use Xaraya\Services\ServicesInterface;
use Xaraya\Services\xar;
use sys;
use Exception;

/**
 * Use Twig template engine to generate output in Xaraya
 *
 * @link https://twig.symfony.com/
 */
class TwigConfig
{
    public const DEFAULT_EXTENSION = '.html.twig';
    public const CONFIG_CACHE_KEY = 'xaraya/twig_config';
    public const CONFIG_CACHE_TTL = 300;
    public static string $twigDir = '';
    /** @var array<string, string> */
    public static array $namespaces = [];
    /** @var array<string, mixed> */
    public static array $extensions = [
        'themes' => [
            'common' => '.html.twig',
            //'rss' => '.xml.twig',
        ],
        'modules' => [],
        'property' => [],
        'block' => [],
    ];

    public static function init(array $args = [])
    {
        // @todo initialize twig with supported module namespaces
        return static::hasTwigEnvironment();
    }

    public static function hasTwigEnvironment()
    {
        if (class_exists('\Twig\Environment')) {
            return true;
        }
        return false;
    }

    /**
     * Get a Twig environment with template paths, options and context
     * @param ?Context<string, mixed> $context
     * @param ?ServicesInterface $xar
     * @param array<string, string> $paths
     * @param array<string, mixed> $options
     * @return Environment
     */
    public static function getTwigEnvironment(?Context $context = null, ?ServicesInterface $xar = null, array $paths = [], array $options = [])
    {
        // support templates/twig or vendor/xaraya/twig/html directory for standard templates
        $twigDir = static::getTwigTemplatesDir();
        // support templates/custom directory for custom templates only
        $customDir = static::getXarayaRootDir() . '/templates/custom';

        $namespaces = static::getNamespaces($xar);

        $basePaths = [];
        foreach ($namespaces as $namespace => $path) {
            // if the custom directory exists, add it to the paths
            if (is_dir($customDir . '/' . $path)) {
                $basePaths[$customDir . '/' . $path] = $namespace;
            }
            $basePaths[$twigDir . '/' . $path] = $namespace;
            if (!is_dir($twigDir . '/' . $path)) {
                throw new Exception("Invalid path for Twig namespace '$namespace':\nPath $twigDir/$path");
            }
        }

        // add paths for Twig filesystem loader (with namespace)
        // {{ include('@workflow/includes/trackeritem.html.twig') }}
        $paths = array_replace($basePaths, $paths);

        // override default options for Twig environment
        $options = array_replace([
            //'cache' => sys::varpath() . '/cache/templates',
            'debug' => true,
        ], $options);

        $xar ??= xar::getServicesClass();
        // get $context from GUI/API function call or DataObject
        if (!isset($context)) {
            // $context = new Context(['source' => __METHOD__]);
            // Use context from static services class here
            $context = $xar->getContext();
        }

        $twigbridge = new TwigBridge($paths, $options, $context, $xar);
        $twig = $twigbridge->getEnvironment();

        // @todo this only affects links generated *after* the main module has executed, so it's too late for that
        // see e.g. blocks admin view_instances - info_link, type_link etc. are already url-encoded
        // @checkme set generate XML urls to false to avoid autoescape issues
        $xar->ctl()->setConfig(['generateXMLURLs' => false]);
        $xar->log()->notice(__METHOD__ . ": New twig environment for context from " . ($context['source'] ?? 'unknown'));

        return $twig;
    }

    /**
     * Support templates/twig or vendor/xaraya/twig/html directory for standard templates
     * @return string
     */
    public static function getTwigTemplatesDir()
    {
        if (!empty(static::$twigDir)) {
            return static::$twigDir;
        }
        $rootDir = static::getXarayaRootDir();
        $twigDir = $rootDir . '/templates/twig';
        if (!is_dir($twigDir)) {
            $twigDir = $rootDir . '/vendor/xaraya/twig/html';
        }
        static::$twigDir = $twigDir;
        return static::$twigDir;
    }

    public static function getXarayaRootDir()
    {
        $rootDir = sys::root();
        // fix common issues with rootDir
        if (empty($rootDir) || $rootDir == sys::web()) {
            $rootDir = dirname(__DIR__, 5);
        }
        if (str_ends_with($rootDir, '/')) {
            $rootDir = rtrim($rootDir, '/');
        }
        return $rootDir;
    }

    public static function getNamespaces($xar = null)
    {
        if (!empty(static::$namespaces)) {
            return static::$namespaces;
        }
        // @todo use cache trait if/when variable caching is enabled by default
        if (function_exists('apcu_fetch')) {
            if (apcu_exists(static::CONFIG_CACHE_KEY)) {
                $config = apcu_fetch(static::CONFIG_CACHE_KEY);
                if (!empty($config) && !empty($config['namespaces']) && !empty($config['extensions'])) {
                    static::$namespaces = $config['namespaces'];
                    static::$extensions = $config['extensions'];
                    return static::$namespaces;
                }
            }
        }
        $xar ??= xar::getServicesClass();
        $twigDir = static::getTwigTemplatesDir();
        static::addCoreTemplates();
        static::addModuleTemplates($xar);
        static::addThemeTemplates($xar);
        static::addPropertyTemplates($twigDir);
        static::addBlockTemplates($twigDir);
        // @todo use cache trait if/when variable caching is enabled by default
        if (function_exists('apcu_store')) {
            $config = [
                'namespaces' => static::$namespaces,
                'extensions' => static::$extensions,
            ];
            apcu_store(static::CONFIG_CACHE_KEY, $config, static::CONFIG_CACHE_TTL);
        }
        return static::$namespaces;
    }

    public static function addCoreTemplates()
    {
        static::$namespaces = [
            'authsystem' => 'code/modules/authsystem',
            'base' => 'code/modules/base',
            'blocks' => 'code/modules/blocks',
            'categories' => 'code/modules/categories',
            'dynamicdata' => 'code/modules/dynamicdata',
            'installer' => 'code/modules/installer',
            'mail' => 'code/modules/mail',
            'modules' => 'code/modules/modules',
            'privileges' => 'code/modules/privileges',
            'roles' => 'code/modules/roles',
            'themes' => 'code/modules/themes',
            // use @theme (singular) namespace for themes
            'theme' => 'themes',
            // @todo support stand-alone properties (partial)
            'property' => 'code/properties',
            // @todo support stand-alone blocks
            'block' => 'code/blocks',
        ];
    }

    public static function addModuleTemplates($xar = null)
    {
        $xar ??= xar::getServicesClass();
        // make other modules configurable based on fileinfo from version.php
        $fileModules = $xar->mod()->apiFunc('modules', 'admin', 'getfilemodules');
        // support templates/twig or vendor/xaraya/twig/html directory for standard templates
        $twigDir = static::getTwigTemplatesDir();
        foreach ($fileModules as $name => $fileInfo) {
            $name = strtolower($name);
            if (in_array($name, static::$namespaces)) {
                continue;
            }
            if (empty($fileInfo['twigtemplates'])) {
                continue;
            }
            // @todo support individual module templates directories too!?
            $path = 'code/modules/' . $fileInfo['directory'];
            if (!is_dir($twigDir . '/' . $path)) {
                xar::log()->warning(__METHOD__ . ": Invalid path for Twig namespace '$name' $twigDir/$path");
                continue;
            }
            static::$namespaces[$name] = $path;
            // @todo if a module uses a specific file extension for twig templates, e.g. to create xml feeds
            if (!empty($fileInfo['twigextension']) && $fileInfo['twigextension'] != static::DEFAULT_EXTENSION) {
                static::$extensions['modules'][$name] = $fileInfo['twigextension'];
            } else {
                static::$extensions['modules'][$name] = static::DEFAULT_EXTENSION;
            }
        }
    }

    public static function addThemeTemplates($xar = null)
    {
        $xar ??= xar::getServicesClass();
        // @todo this assumes we're running in sys::web() because it looks for 'themes'
        // make other themes configurable based on fileinfo from xartheme.php
        $fileThemes = $xar->mod()->apiFunc('themes', 'admin', 'getfilethemes');
        foreach ($fileThemes as $name => $fileInfo) {
            $name = strtolower($name);
            // no namespace for themes
            if (empty($fileInfo['twigtemplates'])) {
                continue;
            }
            // if a theme uses a specific file extension for twig templates, e.g. to create xml feeds
            if (!empty($fileInfo['twigextension']) && $fileInfo['twigextension'] != static::DEFAULT_EXTENSION) {
                static::$extensions['themes'][$name] = $fileInfo['twigextension'];
            } else {
                static::$extensions['themes'][$name] = static::DEFAULT_EXTENSION;
            }
        }
    }

    public static function addPropertyTemplates(string $twigDir)
    {
        // make stand-alone properties configurable: if they have templates in their twig directory!?
        static::addAvailableTemplates($twigDir, 'property');
    }

    public static function addBlockTemplates(string $twigDir)
    {
        // @todo make stand-alone blocks configurable: if they have templates in their twig directory!?
        static::addAvailableTemplates($twigDir, 'block');
    }

    public static function addAvailableTemplates(string $twigDir, string $type)
    {
        $path = $twigDir . '/' . static::$namespaces[$type];
        $fileList = scandir($path);
        foreach ($fileList as $fileName) {
            if (str_starts_with($fileName, '.')) {
                continue;
            }
            $filePath = $path . '/' . $fileName;
            if (is_dir($filePath)) {
                $name = strtolower($fileName);
                static::$extensions[$type][$name] = static::DEFAULT_EXTENSION;
            }
        }
    }

    /**
     * Check if the theme supports twig templates
     * @param string $themeName
     * @return bool
     */
    public static function isThemeSupported($themeName)
    {
        // let's keep the installer with blocklayout for now
        if (in_array($themeName, ['common', 'default', 'print', 'rss'])) {
            return true;
        }
        if (in_array($themeName, ['installer', 'kingston', 'Xaraya_Classic'])) {
            return false;
        }
        $themeName = strtolower($themeName);
        // make other themes configurable based on fileinfo from xartheme.php
        if (empty(static::$extensions['themes'][$themeName])) {
            xar::log()->info(__METHOD__ . ": Theme {$themeName} does not support twig templates");
            return false;
        }
        return true;
    }

    /**
     * Get standard template extension for theme
     * @param string $themeName
     * @return string
     */
    public static function getThemeExtension($themeName)
    {
        // @todo define this in theme config
        $extension = static::DEFAULT_EXTENSION;
        if (!empty(static::$extensions['themes'][$themeName])) {
            $extension = static::$extensions['themes'][$themeName];
        }
        return $extension;
    }

    /**
     * Check if the module supports twig templates
     * @return bool
     */
    public static function isModuleSupported(string $modName)
    {
        // let's keep the installer with blocklayout for now
        if (in_array($modName, ['authsystem', 'base', 'blocks', 'categories', 'dynamicdata', 'mail', 'modules', 'privileges', 'roles', 'themes'])) {
            return true;
        }
        if (in_array($modName, ['installer'])) {
            xar::log()->info(__METHOD__ . ": Core module installer does not support twig templates");
            return false;
        }
        static::getNamespaces();
        $modName = strtolower($modName);
        // make other modules configurable based on fileinfo from version.php
        if (empty(static::$extensions['modules'][$modName])) {
            xar::log()->info(__METHOD__ . ": Module {$modName} does not support twig templates");
            return false;
        }
        return true;
    }

    /**
     * Check if the block supports twig templates
     * @return bool
     */
    public static function isBlockSupported(string $blockType, string $modName)
    {
        // @todo make stand-alone blocks configurable: if they have templates in their twig directory!?
        if (empty($modName) || $modName == 'auto') {
            $blockType = strtolower($blockType);
            if (empty(static::$extensions['block'][$blockType])) {
                xar::log()->info(__METHOD__ . ": Stand-alone block {$blockType} does not support twig templates");
                return false;
            }
            return true;
        }
        // let the module be the main blocker here
        if (!static::isModuleSupported($modName)) {
            xar::log()->info(__METHOD__ . ": Block {$blockType} of module {$modName} does not support twig templates");
            return false;
        }
        // otherwise let's always assume that it is supported ;-)
        return true;
    }

    /**
     * Check if the object supports twig templates
     * @return bool
     */
    public static function isObjectSupported(string $objectName, string $modName)
    {
        // let the module be the main blocker here
        if (!static::isModuleSupported($modName)) {
            xar::log()->info(__METHOD__ . ": Object {$objectName} of module {$modName} does not support twig templates");
            return false;
        }
        // otherwise let's always assume that it is supported ;-)
        return true;
    }

    /**
     * Check if the property supports twig templates
     * @return bool
     */
    public static function isPropertySupported(string $propertyName, string $modName)
    {
        // make stand-alone properties configurable: if they have templates in their twig directory!?
        if ($modName == 'auto') {
            $propertyName = strtolower($propertyName);
            if (empty(static::$extensions['property'][$propertyName])) {
                xar::log()->info(__METHOD__ . ": Stand-alone property {$propertyName} does not support twig templates");
                return false;
            }
            return true;
        }
        // let the module be the main blocker here
        if (!static::isModuleSupported($modName)) {
            xar::log()->info(__METHOD__ . ": Property {$propertyName} of module {$modName} does not support twig templates");
            return false;
        }
        // otherwise let's always assume that it is supported ;-)
        return true;
    }
}
