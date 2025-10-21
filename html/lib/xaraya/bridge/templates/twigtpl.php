<?php

/**
 * Use Twig template engine for output in Xaraya
 */
sys::import('xaraya.templates');
sys::import('xaraya.bridge.templates.twigbridge');
sys::import('xaraya.context.context');
sys::import('xaraya.services.xar');
use Twig\Environment;
use Twig\Loader\LoaderInterface;
use Twig\TemplateWrapper;
use Xaraya\Bridge\TemplateEngine\TwigBridge;
use Xaraya\Context\Context;
use Xaraya\Services\xar;

//use xarMod;
//use xarServer;
//use xarTpl;

/**
 * Use Twig template engine to generate output in Xaraya
 *
 * @uses \sys::autoload()
 * @link https://twig.symfony.com/
 */
class xarTwigTpl extends xarTpl
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
        //return parent::init($args);
        // @todo initialize twig with supported module namespaces
        return false;
    }

    public static function hasTwigEnvironment()
    {
        sys::autoload();
        if (class_exists('\Twig\Environment')) {
            return true;
        }
        return false;
    }

    /**
     * Get a Twig environment with template paths, options and context
     * @param ?Context<string, mixed> $context
     * @param array<string, string> $paths
     * @param array<string, mixed> $options
     * @return Environment
     */
    public static function getTwigEnvironment(?Context $context = null, array $paths = [], array $options = [])
    {
        sys::autoload();

        // support templates/twig or vendor/xaraya/twig/html directory for standard templates
        $twigDir = static::getTwigTemplatesDir();
        // support templates/custom directory for custom templates only
        $customDir = static::getXarayaRootDir() . '/templates/custom';

        $namespaces = static::getNamespaces();

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

        // get $context from GUI/API function call or DataObject
        if (!isset($context)) {
            $context = new Context(['source' => __METHOD__]);
        }

        $twigbridge = new TwigBridge($paths, $options, $context);
        $twig = $twigbridge->getEnvironment();

        // @todo this only affects links generated *after* the main module has executed, so it's too late for that
        // see e.g. blocks admin view_instances - info_link, type_link etc. are already url-encoded
        // @checkme set generate XML urls to false to avoid autoescape issues
        xarServer::$generateXMLURLs = false;
        xarMod::$genXmlUrls = false;
        xar::log()->notice(__METHOD__ . ": New twig environment for context from " . ($context['source'] ?? 'unknown'));

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

    public static function getNamespaces()
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
        $twigDir = static::getTwigTemplatesDir();
        static::addCoreTemplates();
        static::addModuleTemplates();
        static::addThemeTemplates();
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

    public static function addModuleTemplates()
    {
        // make other modules configurable based on fileinfo from xarversion.php
        $fileModules = xar::mod()->apiFunc('modules', 'admin', 'getfilemodules');
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

    public static function addThemeTemplates()
    {
        // @todo this assumes we're running in sys::web() because it looks for 'themes'
        // make other themes configurable based on fileinfo from xartheme.php
        $fileThemes = xar::mod()->apiFunc('themes', 'admin', 'getfilethemes');
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
     * Find the first available template in the list or null
     * @param Environment $twig
     * @param list<string> $templates
     * @return string|null
     */
    public static function findTwigTemplate(Environment $twig, array $templates)
    {
        /** @var LoaderInterface $loader */
        $loader = $twig->getLoader();
        foreach ($templates as $template) {
            if ($loader->exists($template)) {
                return $template;
            }
        }
        return null;
    }

    /**
     * Render Twig template with template data + add template name in comments
     * @param TemplateWrapper $template
     * @param array<string, mixed> $tplData
     * @param string $templateName
     * @param string $caller
     * @return string
     */
    public static function renderTemplate($template, $tplData, $templateName, $caller = '')
    {
        $output = $template->render($tplData);
        /**
        try {
            $output = $template->render($tplData);
        } catch (Exception $e) {
            $output = $e->getMessage();
        }
         */
        // @todo let's be drastic about double-encoding for now...
        if (str_contains($output, '&amp;amp;')) {
            $output = str_replace('&amp;amp;', '&amp;', $output);
        }
        // don't use caller in page templates to avoid adding comments to page
        if (empty($caller) || !xarTpl::outputTemplateFilenames()) {
            return $output;
        }
        return '<!-- start: ' . $templateName . " -->\n"
            //'<!-- args: ' . $caller . ' -->' .
            . trim($output)
            . '<!-- end: ' . $templateName . " -->\n";
    }

    /**
     * Check if the theme supports twig templates
     * @param Context<string, mixed> $context
     * @return bool
     */
    public static function isThemeSupported($context)
    {
        $themeName = $context['theme'] ?? xarTpl::getThemeName();
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
     * @param string $mainModuleOutput
     * @param ?string $pageTemplate
     * @param ?Context<string, mixed> $context
     * @return string
     */
    public static function renderPage($mainModuleOutput, $pageTemplate = null, $context = null)
    {
        if (!isset($context) || !isset($context['twig'])) {
            throw new Exception('How did we end up here without twig context?');
        }
        // xarTwigTpl::renderPage('...', 'theme', default, user, null, 'pages')
        if (is_bool($context['twig'])) {
            $context['twig'] = static::getTwigEnvironment($context);
        }
        if (empty($pageTemplate)) {
            $pageTemplate = $context['page'] ?? self::getPageTemplateName();
        }
        $themeName = $context['theme'] ?? xarTpl::getThemeName();
        $caller = "xarTwigTpl::renderPage('...', 'theme', $themeName, $pageTemplate, null, 'pages')";
        // get page template source (current > common)
        //$sourceFileName = self::getScopeFileName('theme', self::getThemeName(), $pageTemplate, null, 'pages');
        /** @var Environment $twig */
        $twig = $context['twig'];
        $templateName = static::findPageTemplate($twig, $themeName, 'pages', $pageTemplate, '');
        if (empty($templateName)) {
            //return parent::renderPage($mainModuleOutput, $pageTemplate, $context);
            return 'Twig template not found: ' . $caller;
        }
        // see xarTpl::renderPage
        $tpl = (object) null; // Create an object to hold the 'specials'
        $tpl->pageTitle = parent::getPageTitle();
        $tplData = [
            'tpl'                      => $tpl,
            '_bl_mainModuleOutput'     => $mainModuleOutput,
            // not really needed for xar_blockgroup() etc. as context is already known in extension, but let's be consistent
            '_bl_context'              => $context,
        ];
        $template = $twig->load($templateName);
        // don't use trace in page templates to avoid adding comments to page
        return static::renderTemplate($template, $tplData, $templateName, '');
    }

    /**
     * @param Environment $twig
     * @param string $themeName
     * @param string $tplType
     * @param string $tplName
     * @param string $pageName - unused?
     * @return string|null
     */
    public static function findPageTemplate($twig, $themeName, $tplType, $tplName, $pageName)
    {
        $cachename = "page:$themeName:$tplType:$tplName:$pageName";
        // cache frequently-used sourcefilenames
        if (xar::var()->isCached('Templates.Twig', $cachename)) {
            return xar::var()->getCached('Templates.Twig', $cachename);
        }

        // @todo define this in theme config
        $extension = static::DEFAULT_EXTENSION;
        if (!empty(static::$extensions['themes'][$themeName])) {
            $extension = static::$extensions['themes'][$themeName];
        }
        $templates = [];
        // @todo align better with current theme template lookup?
        if (!empty($pageName)) {
            $templates[] = '@theme/' . $themeName . '/' . $tplType . '/' . $tplName . '-' . $pageName . $extension;
            $templates[] = '@theme/' . $themeName . '/' . $tplType . '/' . $tplName . $extension;
            if ($themeName != 'default') {
                $templates[] = '@theme/default/' . $tplType . '/' . $tplName . '-' . $pageName . $extension;
                $templates[] = '@theme/default/' . $tplType . '/' . $tplName . $extension;
            }
            if ($themeName != 'common') {
                $templates[] = '@theme/common/' . $tplType . '/' . $tplName . '-' . $pageName . $extension;
                $templates[] = '@theme/common/' . $tplType . '/' . $tplName . $extension;
            }
        } else {
            $templates[] = '@theme/' . $themeName . '/' . $tplType . '/' . $tplName . $extension;
            if ($themeName != 'default') {
                $templates[] = '@theme/default/' . $tplType . '/' . $tplName . $extension;
            }
            if ($themeName != 'common') {
                $templates[] = '@theme/common/' . $tplType . '/' . $tplName . $extension;
            }
        }

        $templateName = static::findTwigTemplate($twig, $templates);
        xar::var()->setCached('Templates.Twig', $cachename, $templateName);

        return $templateName;
    }

    public static function renderBlockBox($blockInfo, $tplName = null)
    {
        // xarTwigTpl::module(workflow, user, showactions, [...], updated)
        if (is_bool($blockInfo['context']['twig'])) {
            $blockInfo['context']['twig'] = static::getTwigEnvironment($blockInfo['context']);
        }
        $themeName = $blockInfo['context']['theme'] ?? xarTpl::getThemeName();
        $caller = "[$themeName] xarTwigTpl::renderBlockBox([...], $tplName)";
        /** @var Environment $twig */
        $twig = $blockInfo['context']['twig'];
        $templateName = static::findBoxTemplate($twig, $themeName, $tplName ?? '');
        if (empty($templateName)) {
            //return parent::renderPage($mainModuleOutput, $pageTemplate, $context);
            return 'Twig template not found: ' . $caller;
        }
        //var_dump($blockInfo);
        //return $templateName . ':' . $caller;
        $template = $twig->load($templateName);
        return static::renderTemplate($template, $blockInfo, $templateName, $caller);
    }

    /**
     * @param Environment $twig
     * @param string $themeName
     * @param string $tplName - optional
     * @return string|null
     */
    public static function findBoxTemplate($twig, $themeName, $tplName)
    {
        $cachename = "box:$themeName:$tplName";
        // cache frequently-used sourcefilenames
        if (xar::var()->isCached('Templates.Twig', $cachename)) {
            return xar::var()->getCached('Templates.Twig', $cachename);
        }

        // @todo define this in theme config
        $extension = static::DEFAULT_EXTENSION;
        if (!empty(static::$extensions['themes'][$themeName])) {
            $extension = static::$extensions['themes'][$themeName];
        }
        $templates = [];
        // look for specific templateName.xt (current > common)
        if (!empty($tplName)) {
            $templates[] = '@theme/' . $themeName . '/blocks/' . $tplName . $extension;
            if ($themeName !== 'common') {
                $templates[] = '@theme/common/blocks/' . $tplName . $extension;
            }
        }
        // no specific template, fallback to default.xt (current > common)
        $templates[] = $themeName . '/blocks/default' . $extension;
        if ($themeName !== 'common') {
            $templates[] = '@theme/common/blocks/default' . $extension;
        }
        // no default, fallback to blocks module block.xt (current > common > module)
        $templates[] = '@blocks/blocks/block' . $extension;

        $templateName = static::findTwigTemplate($twig, $templates);
        xar::var()->setCached('Templates.Twig', $cachename, $templateName);

        return $templateName;
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
        // make other modules configurable based on fileinfo from xarversion.php
        if (empty(static::$extensions['modules'][$modName])) {
            xar::log()->info(__METHOD__ . ": Module {$modName} does not support twig templates");
            return false;
        }
        return true;
    }

    /**
     * @param string $modName
     * @param string $modType
     * @param string $funcName
     * @param array<string, mixed> $tplData
     * @param ?string $tplName - optional
     * @return string
     */
    public static function module($modName, $modType, $funcName, $tplData = [], $tplName = null)
    {
        // xarTwigTpl::module(workflow, user, showactions, [...], updated)
        if (is_bool($tplData['context']['twig'])) {
            $tplData['context']['twig'] = static::getTwigEnvironment($tplData['context']);
        }
        $themeName = $tplData['context']['theme'] ?? xarTpl::getThemeName();
        $caller = "[$themeName] xarTwigTpl::module($modName, $modType, $funcName, [...], $tplName)";
        /** @var Environment $twig */
        $twig = $tplData['context']['twig'];
        $templateName = static::findModuleTemplate($twig, $themeName, $modName, $modType, $funcName, $tplName ?? '');
        if (empty($templateName)) {
            //return parent::module($modName, $modType, $funcName, $tplData, $templateName);
            return 'Twig template not found: ' . $caller;
        }
        $template = $twig->load($templateName);
        return static::renderTemplate($template, $tplData, $templateName, $caller);
    }

    /**
     * @param Environment $twig
     * @param string $themeName
     * @param string $modName
     * @param string $modType
     * @param string $funcName
     * @param string $tplName - optional
     * @return string|null
     */
    public static function findModuleTemplate($twig, $themeName, $modName, $modType, $funcName, $tplName)
    {
        $cachename = "module:$themeName:$modName:$modType:$funcName:$tplName";
        // cache frequently-used sourcefilenames
        if (xar::var()->isCached('Templates.Twig', $cachename)) {
            return xar::var()->getCached('Templates.Twig', $cachename);
        }

        // @todo define this in theme config
        $extension = static::DEFAULT_EXTENSION;
        if (!empty(static::$extensions['themes'][$themeName])) {
            $extension = static::$extensions['themes'][$themeName];
        }
        $templates = [];
        // user templates are now in the top level directory and all others in subdirectories
        if ($modType == 'user') {
            if (!empty($tplName)) {
                // changed order - with tplName first (theme > module), then generic next (theme > module)
                $templates[] = '@theme/' . $themeName . '/modules/' . $modName . '/' . $funcName . '-' . $tplName . $extension;
                $templates[] = '@' . $modName . '/' . $funcName . '-' . $tplName . $extension;
                $templates[] = '@theme/' . $themeName . '/modules/' . $modName . '/' . $funcName . $extension;
                $templates[] = '@' . $modName . '/' . $funcName . $extension;
                // @todo do we need/want dynamicdata module as fallback here?
                if ($modName !== 'dynamicdata') {
                    // changed order - with tplName first (theme > module), then generic next (theme > module)
                    $templates[] = '@theme/' . $themeName . '/modules/dynamicdata/' . $funcName . '-' . $tplName . $extension;
                    $templates[] = '@dynamicdata/' . $funcName . '-' . $tplName . $extension;
                    $templates[] = '@theme/' . $themeName . '/modules/dynamicdata/' . $funcName . $extension;
                    $templates[] = '@dynamicdata/' . $funcName . $extension;
                }
            } else {
                $templates[] = '@theme/' . $themeName . '/modules/' . $modName . '/' . $funcName . $extension;
                $templates[] = '@' . $modName . '/' . $funcName . $extension;
                // @todo do we need/want dynamicdata module as fallback here?
                if ($modName !== 'dynamicdata') {
                    $templates[] = '@theme/' . $themeName . '/modules/dynamicdata/' . $funcName . $extension;
                    $templates[] = '@dynamicdata/' . $funcName . $extension;
                }
            }
        } else {
            if (!empty($tplName)) {
                // changed order - with tplName first (theme > module), then generic next (theme > module)
                $templates[] = '@theme/' . $themeName . '/modules/' . $modName . '/' . $modType . '/' . $funcName . '-' . $tplName . $extension;
                $templates[] = '@' . $modName . '/' . $modType . '/' . $funcName . '-' . $tplName . $extension;
                $templates[] = '@theme/' . $themeName . '/modules/' . $modName . '/' . $modType . '/' . $funcName . $extension;
                $templates[] = '@' . $modName . '/' . $modType . '/' . $funcName . $extension;
                // @todo do we need/want dynamicdata module as fallback here?
                if ($modName !== 'dynamicdata') {
                    // changed order - with tplName first (theme > module), then generic next (theme > module)
                    $templates[] = '@theme/' . $themeName . '/modules/dynamicdata/' . $modType . '/' . $funcName . '-' . $tplName . $extension;
                    $templates[] = '@dynamicdata/' . $modType . '/' . $funcName . '-' . $tplName . $extension;
                    $templates[] = '@theme/' . $themeName . '/modules/dynamicdata/' . $modType . '/' . $funcName . $extension;
                    $templates[] = '@dynamicdata/' . $modType . '/' . $funcName . $extension;
                }
            } else {
                $templates[] = '@theme/' . $themeName . '/modules/' . $modName . '/' . $modType . '/' . $funcName . $extension;
                $templates[] = '@' . $modName . '/' . $modType . '/' . $funcName . $extension;
                // @todo do we need/want dynamicdata module as fallback here?
                if ($modName !== 'dynamicdata') {
                    $templates[] = '@theme/' . $themeName . '/modules/dynamicdata/' . $modType . '/' . $funcName . $extension;
                    $templates[] = '@dynamicdata/' . $modType . '/' . $funcName . $extension;
                }
            }
        }

        $templateName = static::findTwigTemplate($twig, $templates);
        xar::var()->setCached('Templates.Twig', $cachename, $templateName);

        return $templateName;
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
     * @param string $modName
     * @param string $blockType
     * @param array<string, mixed> $tplData
     * @param ?string $tplName
     * @param ?string $tplBase
     * @param ?string $tplModule - for stand-alone blocks
     * @return string
     */
    public static function block($modName, $blockType, $tplData = [], $tplName = null, $tplBase = null, $tplModule = null)
    {
        if (is_bool($tplData['context']['twig'])) {
            $tplData['context']['twig'] = static::getTwigEnvironment($tplData['context']);
        }
        $themeName = $tplData['context']['theme'] ?? xarTpl::getThemeName();
        //return parent::block($modName, $blockType, $tplData, $tplName, $tplBase, $tplModule);
        $caller = "[$themeName] xarTwigTpl::block($modName, $blockType, [...], $tplName, $tplBase, $tplModule)";
        /** @var Environment $twig */
        $twig = $tplData['context']['twig'];
        $templateName = static::findBlockTemplate($twig, $themeName, $modName, $blockType, $tplName ?? '', $tplBase ?? '', $tplModule ?? '');
        if (empty($templateName)) {
            //return parent::object($modName, $objectName, $tplType, $tplData, $tplBase);
            return 'Twig template not found: ' . $caller;
        }
        //var_dump($tplData);
        //return $templateName . ':' . $caller;
        $template = $twig->load($templateName);
        return static::renderTemplate($template, $tplData, $templateName, $caller);
    }

    /**
     * @param Environment $twig
     * @param string $themeName
     * @param string $modName
     * @param string $blockType
     * @param string $tplName
     * @param string $tplBase
     * @param string $tplModule - for stand-alone blocks
     * @return string|null
     */
    public static function findBlockTemplate($twig, $themeName, $modName, $blockType, $tplName, $tplBase, $tplModule)
    {
        $cachename = "block:$themeName:$modName:$blockType:$tplName:$tplBase:$tplModule";
        // cache frequently-used sourcefilenames
        if (xar::var()->isCached('Templates.Twig', $cachename)) {
            return xar::var()->getCached('Templates.Twig', $cachename);
        }

        // use name of blocktype as base unless over-ridden
        $tplBase = empty($tplBase) ? $blockType : $tplBase;

        // [default] xarTwigTpl::block(base, adminmenu, [...], , verticallistbycats, )
        // @todo define this in theme config
        $extension = static::DEFAULT_EXTENSION;
        if (!empty(static::$extensions['themes'][$themeName])) {
            $extension = static::$extensions['themes'][$themeName];
        }
        $templates = [];
        $templates[] = '@theme/' . $themeName . '/modules/' . $modName . '/blocks/' . $tplBase . $extension;
        $templates[] = '@' . $modName . '/blocks/' . $tplBase . $extension;
        // many block templates are actually in the base module
        if ($modName !== 'base') {
            $templates[] = '@theme/' . $themeName . '/modules/base/blocks/' . $tplBase . $extension;
            $templates[] = '@base/blocks/' . $tplBase . $extension;
        }

        $templateName = static::findTwigTemplate($twig, $templates);
        xar::var()->setCached('Templates.Twig', $cachename, $templateName);

        return $templateName;
        /**
         * xarTwigTpl::block(default, blocks, blockgroup, [...], , , )
        <!-- start: code/modules/blocks/xartemplates/blocks/blockgroup.xt -->
        <!-- start: themes/common/blocks/header.xt -->
        <!-- start: code/modules/themes/xartemplates/blocks/meta.xt -->
        <!-- start: code/modules/themes/xartemplates/meta-render.xt -->
        <meta http-equiv="content-type" ...><!-- end: code/modules/themes/xartemplates/blocks/meta.xt -->
        <!-- end: themes/common/blocks/header.xt -->
        <!-- end: code/modules/blocks/xartemplates/blocks/blockgroup.xt -->
         */
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
     * @param string $modName
     * @param string $objectName
     * @param string $tplType
     * @param array<string, mixed> $tplData
     * @param ?string $tplBase - unused
     * @return string
     */
    public static function object($modName, $objectName, $tplType = 'showdisplay', $tplData = [], $tplBase = null)
    {
        if (is_bool($tplData['context']['twig'])) {
            $tplData['context']['twig'] = static::getTwigEnvironment($tplData['context']);
        }
        $themeName = $tplData['context']['theme'] ?? xarTpl::getThemeName();
        $caller = "[$themeName] xarTwigTpl::object($modName, $objectName, $tplType, [...], $tplBase)";
        /** @var Environment $twig */
        $twig = $tplData['context']['twig'];
        $templateName = static::findObjectTemplate($twig, $themeName, $modName, $objectName, $tplType, $tplBase ?? '');
        if (empty($templateName)) {
            //return parent::object($modName, $objectName, $tplType, $tplData, $tplBase);
            return 'Twig template not found: ' . $caller;
        }
        $template = $twig->load($templateName);
        return static::renderTemplate($template, $tplData, $templateName, $caller);
    }

    /**
     * @param Environment $twig
     * @param string $themeName
     * @param string $modName
     * @param string $objectName
     * @param string $tplType
     * @param string $tplBase - unused
     * @return string|null
     */
    public static function findObjectTemplate($twig, $themeName, $modName, $objectName, $tplType, $tplBase)
    {
        $cachename = "object:$themeName:$modName:$objectName:$tplType:$tplBase";
        // cache frequently-used sourcefilenames
        if (xar::var()->isCached('Templates.Twig', $cachename)) {
            return xar::var()->getCached('Templates.Twig', $cachename);
        }

        // @todo define this in theme config
        $extension = static::DEFAULT_EXTENSION;
        if (!empty(static::$extensions['themes'][$themeName])) {
            $extension = static::$extensions['themes'][$themeName];
        }
        $templates = [];
        // @todo ui_* templates are typically not overridden by objectName, but they could be...
        if (str_starts_with($tplType, 'ui_')) {
            $templates[] = '@theme/' . $themeName . '/modules/' . $modName . '/objects/' . $tplType . $extension;
            $templates[] = '@' . $modName . '/objects/' . $tplType . $extension;
            // final fallback for object templates is dynamicdata module
            if ($modName !== 'dynamicdata') {
                $templates[] = '@theme/' . $themeName . '/modules/dynamicdata/objects/' . $tplType . $extension;
                $templates[] = '@dynamicdata/objects/' . $tplType . $extension;
            }
        } else {
            // changed order - with objectName first (theme > module), then generic next (theme > module)
            $templates[] = '@theme/' . $themeName . '/modules/' . $modName . '/objects/' . $tplType . '-' . $objectName . $extension;
            $templates[] = '@' . $modName . '/objects/' . $tplType . '-' . $objectName . $extension;
            $templates[] = '@theme/' . $themeName . '/modules/' . $modName . '/objects/' . $tplType . $extension;
            $templates[] = '@' . $modName . '/objects/' . $tplType . $extension;
            // final fallback for object templates is dynamicdata module
            if ($modName !== 'dynamicdata') {
                $templates[] = '@theme/' . $themeName . '/modules/dynamicdata/objects/' . $tplType . '-' . $objectName . $extension;
                $templates[] = '@dynamicdata/objects/' . $tplType . '-' . $objectName . $extension;
                $templates[] = '@theme/' . $themeName . '/modules/dynamicdata/objects/' . $tplType . $extension;
                $templates[] = '@dynamicdata/objects/' . $tplType . $extension;
            }
        }

        $templateName = static::findTwigTemplate($twig, $templates);
        xar::var()->setCached('Templates.Twig', $cachename, $templateName);

        return $templateName;
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

    /**
     * @param string $modName
     * @param string $propertyName
     * @param string $tplType
     * @param array<string, mixed> $tplData
     * @param ?string $tplBase - used by xar:data-label - why not change tplType?
     * @return string
     */
    public static function property($modName, $propertyName, $tplType = 'showoutput', $tplData = [], $tplBase = null)
    {
        // @todo check and handle stand-alone properties with module 'auto' + adapt includes path
        // xarTwigTpl::property(base, dropdown, showoutput, [...], )
        if (is_bool($tplData['context']['twig'])) {
            $tplData['context']['twig'] = static::getTwigEnvironment($tplData['context']);
        }
        $themeName = $tplData['context']['theme'] ?? xarTpl::getThemeName();
        $caller = "[$themeName] xarTwigTpl::property($modName, $propertyName, $tplType, [...], $tplBase)";
        /** @var Environment $twig */
        $twig = $tplData['context']['twig'];
        $templateName = static::findPropertyTemplate($twig, $themeName, $modName, $propertyName, $tplType, $tplBase ?? '');
        if (empty($templateName)) {
            //return parent::property($modName, $propertyName, $tplType, $tplData, $tplBase);
            return 'Twig template not found: ' . $caller;
        }
        $template = $twig->load($templateName);
        return static::renderTemplate($template, $tplData, $templateName, $caller);
    }

    /**
     * @param Environment $twig
     * @param string $themeName
     * @param string $modName
     * @param string $propertyName
     * @param string $tplType
     * @param string $tplBase - used by xar:data-label - why not change tplType?
     * @return string|null
     */
    public static function findPropertyTemplate($twig, $themeName, $modName, $propertyName, $tplType, $tplBase)
    {
        $cachename = "property:$themeName:$modName:$propertyName:$tplType:$tplBase";
        // cache frequently-used sourcefilenames
        if (xar::var()->isCached('Templates.Twig', $cachename)) {
            return xar::var()->getCached('Templates.Twig', $cachename);
        }
        if (!empty($tplBase)) {
            $tplType = xar::var()->prepPath($tplBase);
        }

        // @todo define this in theme config
        $extension = static::DEFAULT_EXTENSION;
        if (!empty(static::$extensions['themes'][$themeName])) {
            $extension = static::$extensions['themes'][$themeName];
        }
        $templates = [];
        if ($modName == 'auto') {
            $templates[] = '@theme/' . $themeName . '/properties/' . $propertyName . '/' . $tplType . '-' . $propertyName . $extension;
            $templates[] = '@theme/' . $themeName . '/properties/' . $propertyName . '/' . $tplType . $extension;
            $templates[] = '@property/' . $propertyName . '/' . $tplType . '-' . $propertyName . $extension;
            $templates[] = '@property/' . $propertyName . '/' . $tplType . $extension;
        } else {
            // changed order - with propertyName first (theme > module), then generic next (theme > module)
            $templates[] = '@theme/' . $themeName . '/modules/' . $modName . '/properties/' . $tplType . '-' . $propertyName . $extension;
            $templates[] = '@' . $modName . '/properties/' . $tplType . '-' . $propertyName . $extension;
            $templates[] = '@theme/' . $themeName . '/modules/' . $modName . '/properties/' . $tplType . $extension;
            $templates[] = '@' . $modName . '/properties/' . $tplType . $extension;
        }
        // many property templates are actually in the base module
        if ($modName !== 'base') {
            $templates[] = '@theme/' . $themeName . '/modules/base/properties/' . $tplType . '-' . $propertyName . $extension;
            $templates[] = '@base/properties/' . $tplType . '-' . $propertyName . $extension;
        }
        // final fallback for property templates is dynamicdata module
        if ($modName !== 'dynamicdata') {
            // changed order - with propertyName first (theme > module), then generic next (theme > module)
            $templates[] = '@theme/' . $themeName . '/modules/dynamicdata/properties/' . $tplType . '-' . $propertyName . $extension;
            $templates[] = '@dynamicdata/properties/' . $tplType . '-' . $propertyName . $extension;
            $templates[] = '@theme/' . $themeName . '/modules/dynamicdata/properties/' . $tplType . $extension;
            $templates[] = '@dynamicdata/properties/' . $tplType . $extension;
        }

        $templateName = static::findTwigTemplate($twig, $templates);
        xar::var()->setCached('Templates.Twig', $cachename, $templateName);

        return $templateName;
    }
}
