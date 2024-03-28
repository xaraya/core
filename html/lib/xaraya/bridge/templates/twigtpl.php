<?php
/**
 * Use Twig template engine for output in Xaraya
 * @todo implement methods :-)
 */
sys::import('xaraya.templates');
sys::import('xaraya.bridge.templates.twig');
sys::import('xaraya.context.context');
use Twig\Environment;
use Twig\Loader\LoaderInterface;
use Twig\TemplateWrapper;
use Xaraya\Bridge\TemplateEngine\TwigBridge;
use Xaraya\Context\Context;

/**
 * Twig Template Engine
 * @uses \sys::autoload()
 * @link https://twig.symfony.com/
 */
class xarTwigTpl extends xarTpl
{
    public static function init(array $args = [])
    {
        //return parent::init($args);
        // @todo initialize twig with supported module namespaces
        return false;
    }

    /**
     * Get a Twig environment with template paths, options and context
     * @param array<string, string> $paths
     * @param array<string, mixed> $options
     * @param ?Context<string, mixed> $context
     * @return Environment
     */
    public static function getTwig(array $paths = [], array $options = [], ?Context $context = null)
    {
        sys::autoload();

        // add paths for Twig filesystem loader (with namespace)
        // {{ include('@workflow/includes/trackeritem.html.twig') }}
        $paths = array_replace([
            'code/modules/dynamicdata/templates' => 'dynamicdata',
            'code/modules/workflow/templates' => 'workflow',
            'code/modules/base/templates' => 'base',
            'code/modules/themes/templates' => 'themes',
            'themes' => '',  // no namespace for themes pages etc.
        ], $paths);

        // override default options for Twig environment
        $options = array_replace([
            //'cache' => sys::varpath() . '/cache/templates',
            'debug' => true,
        ], $options);

        // get $context from GUI/API function call or DataObject
        if (!isset($context)) {
            //$context = ContextFactory::fromGlobals(__METHOD__);
            $context = new Context(['source' => __METHOD__]);
        }

        $twigbridge = new TwigBridge($paths, $options, $context);
        $twig = $twigbridge->getEnvironment();

        return $twig;
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
     * @param string $trace
     * @return string
     */
    public static function renderTemplate($template, $tplData, $templateName, $trace = '')
    {
        // don't use trace in page templates to avoid adding comments to page
        if (empty($trace) || !xarTpl::outputTemplateFilenames()) {
            return $template->render($tplData);
        }
        return '<!-- start: ' . $templateName . ' -->' .
            //'<!-- args: ' . $trace . ' -->' .
            $template->render($tplData) .
            '<!-- end: ' . $templateName . ' -->';
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
            $context['twig'] = static::getTwig([], [], $context);
        }
        if (empty($pageTemplate)) $pageTemplate = $context['page'] ?? self::getPageTemplateName();
        $themeName = $context['theme'] ?? xarTpl::getThemeName();
        $trace = "xarTwigTpl::renderPage('...', 'theme', $themeName, $pageTemplate, null, 'pages')";
        // get page template source (current > common)
        //$sourceFileName = self::getScopeFileName('theme', self::getThemeName(), $pageTemplate, null, 'pages');
        /** @var Environment $twig */
        $twig = $context['twig'];
        $templateName = static::findPageTemplate($twig, $themeName, 'pages', $pageTemplate, '');
        if (empty($templateName)) {
            //return parent::renderPage($mainModuleOutput, $pageTemplate, $context);
            return $trace;
        }
        // see xarTpl::renderPage
        $tpl = (object) null; // Create an object to hold the 'specials'
        $tpl->pageTitle = parent::getPageTitle();
        $tplData = [
            'tpl'                      => $tpl,
            '_bl_mainModuleOutput'     => $mainModuleOutput,
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
        if (xarCoreCache::isCached('Templates.Twig', $cachename))
            return xarCoreCache::getCached('Templates.Twig', $cachename);

        // @todo define this in theme config
        $extension = '.html.twig';
        if ($themeName === 'rss') {
            $extension = '.xml.twig';
        }
        $templates = [];
        // @todo align better with current theme template lookup?
        if (!empty($pageName)) {
            $templates[] = $themeName . '/' . $tplType . '/' . $tplName . '-' . $pageName . $extension;
            $templates[] = $themeName . '/' . $tplType . '/' . $tplName . $extension;
            if ($themeName != 'default') {
                $templates[] = 'default/' . $tplType . '/' . $tplName . '-' . $pageName . $extension;
                $templates[] = 'default/' . $tplType . '/' . $tplName . $extension;
            }
            if ($themeName != 'common') {
                $templates[] = 'common/' . $tplType . '/' . $tplName . '-' . $pageName . $extension;
                $templates[] = 'common/' . $tplType . '/' . $tplName . $extension;
            }
        } else {
            $templates[] = $themeName . '/' . $tplType . '/' . $tplName . $extension;
            if ($themeName != 'default') {
                $templates[] = 'default/' . $tplType . '/' . $tplName . $extension;
            }
            if ($themeName != 'common') {
                $templates[] = 'common/' . $tplType . '/' . $tplName . $extension;
            }
        }

        $templateName = static::findTwigTemplate($twig, $templates);
        xarCoreCache::setCached('Templates.Twig', $cachename, $templateName);

        return $templateName;
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
            $tplData['context']['twig'] = static::getTwig([], [], $tplData['context']);
        }
        $themeName = $tplData['context']['theme'] ?? xarTpl::getThemeName();
        $trace = "xarTwigTpl::module($modName, $modType, $funcName, [...], $tplName)";
        /** @var Environment $twig */
        $twig = $tplData['context']['twig'];
        $templateName = static::findModuleTemplate($twig, $themeName, $modName, $modType, $funcName, $tplName ?? '');
        if (empty($templateName)) {
            //return parent::module($modName, $modType, $funcName, $tplData, $templateName);
            return $trace;
        }
        $template = $twig->load($templateName);
        return static::renderTemplate($template, $tplData, $templateName, $trace);
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
        if (xarCoreCache::isCached('Templates.Twig', $cachename))
            return xarCoreCache::getCached('Templates.Twig', $cachename);

        // @todo define this in theme config
        $extension = '.html.twig';
        if ($themeName === 'rss') {
            $extension = '.xml.twig';
        }
        $templates = [];
        // user templates are now in the top level directory and all others in subdirectories
        if ($modType == 'user') {
            if (!empty($tplName)) {
                $templates[] = $themeName . '/modules/' . $modName . '/' . $funcName . '-' . $tplName . $extension;
                $templates[] = $themeName . '/modules/' . $modName . '/' . $funcName . $extension;
                $templates[] = '@' . $modName . '/' . $funcName . '-' . $tplName . $extension;
                $templates[] = '@' . $modName . '/' . $funcName . $extension;
                if ($modName !== 'dynamicdata') {
                    $templates[] = $themeName . '/modules/dynamicdata/' . $funcName . '-' . $tplName . $extension;
                    $templates[] = $themeName . '/modules/dynamicdata/' . $funcName . $extension;
                    $templates[] = '@dynamicdata/' . $funcName . '-' . $tplName . $extension;
                    $templates[] = '@dynamicdata/' . $funcName . $extension;
                }
            } else {
                $templates[] = $themeName . '/modules/' . $modName . '/' . $funcName . $extension;
                $templates[] = '@' . $modName . '/' . $funcName . $extension;
                if ($modName !== 'dynamicdata') {
                    $templates[] = $themeName . '/modules/dynamicdata/' . $funcName . $extension;
                    $templates[] = '@dynamicdata/' . $funcName . $extension;
                }
            }
        } else {
            if (!empty($tplName)) {
                $templates[] = $themeName . '/modules/' . $modName . '/' . $modType . '/' . $funcName . '-' . $tplName . $extension;
                $templates[] = $themeName . '/modules/' . $modName . '/' . $modType . '/' . $funcName . $extension;
                $templates[] = '@' . $modName . '/' . $modType . '/' . $funcName . '-' . $tplName . $extension;
                $templates[] = '@' . $modName . '/' . $modType . '/' . $funcName . $extension;
                if ($modName !== 'dynamicdata') {
                    $templates[] = $themeName . '/modules/dynamicdata/' . $modType . '/' . $funcName . '-' . $tplName . $extension;
                    $templates[] = $themeName . '/modules/dynamicdata/' . $modType . '/' . $funcName . $extension;
                    $templates[] = '@dynamicdata/' . $modType . '/' . $funcName . '-' . $tplName . $extension;
                    $templates[] = '@dynamicdata/' . $modType . '/' . $funcName . $extension;
                }
            } else {
                $templates[] = $themeName . '/modules/' . $modName . '/' . $modType . '/' . $funcName . $extension;
                $templates[] = '@' . $modName . '/' . $modType . '/' . $funcName . $extension;
                if ($modName !== 'dynamicdata') {
                    $templates[] = $themeName . '/modules/dynamicdata/' . $modType . '/' . $funcName . $extension;
                    $templates[] = '@dynamicdata/' . $modType . '/' . $funcName . $extension;
                }
            }
        }

        $templateName = static::findTwigTemplate($twig, $templates);
        xarCoreCache::setCached('Templates.Twig', $cachename, $templateName);

        return $templateName;
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
        $themeName = $tplData['context']['theme'] ?? xarTpl::getThemeName();
        //return parent::block($modName, $blockType, $tplData, $tplName, $tplBase, $tplModule);
        return "xarTwigTpl::block($modName, $blockType, [...], $tplName, $tplBase, $tplModule)";
    }

    /**
     * @param Environment $twig
     * @param string $themeName
     * @param string $modName
     * @param string $blockType
     * @param ?string $tplName
     * @param ?string $tplBase
     * @param ?string $tplModule - for stand-alone blocks
     * @return string|null
     */
    public static function findBlockTemplate($twig, $themeName, $modName, $blockType, $tplName, $tplBase, $tplModule)
    {
        $cachename = "block:$themeName:$modName:$blockType:$tplName:$tplBase:$tplModule";
        // cache frequently-used sourcefilenames 
        if (xarCoreCache::isCached('Templates.Twig', $cachename))
            return xarCoreCache::getCached('Templates.Twig', $cachename);

        return null;
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
            $tplData['context']['twig'] = static::getTwig([], [], $tplData['context']);
        }
        $themeName = $tplData['context']['theme'] ?? xarTpl::getThemeName();
        $trace = "xarTwigTpl::object($modName, $objectName, $tplType, [...], $tplBase)";
        /** @var Environment $twig */
        $twig = $tplData['context']['twig'];
        $templateName = static::findObjectTemplate($twig, $themeName, $modName, $objectName, $tplType, $tplBase ?? '');
        if (empty($templateName)) {
            //return parent::object($modName, $objectName, $tplType, $tplData, $tplBase);
            return $trace;
        }
        $template = $twig->load($templateName);
        return static::renderTemplate($template, $tplData, $templateName, $trace);
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
        if (xarCoreCache::isCached('Templates.Twig', $cachename))
            return xarCoreCache::getCached('Templates.Twig', $cachename);

        // @todo define this in theme config
        $extension = '.html.twig';
        if ($themeName === 'rss') {
            $extension = '.xml.twig';
        }
        $templates = [];
        // @todo ui_* templates are typically not overridden by objectName, but they could be...
        if (str_starts_with($tplType, 'ui_')) {
            $templates[] = $themeName . '/modules/' . $modName . '/objects/' . $tplType . $extension;
            $templates[] = '@' . $modName . '/objects/' . $tplType . $extension;
            if ($modName !== 'dynamicdata') {
                $templates[] = $themeName . '/modules/dynamicdata/objects/' . $tplType . $extension;
                $templates[] = '@dynamicdata/objects/' . $tplType . $extension;
            }
        } else {
            $templates[] = $themeName . '/modules/' . $modName . '/objects/' . $tplType . '-' . $objectName . $extension;
            $templates[] = $themeName . '/modules/' . $modName . '/objects/' . $tplType . $extension;
            $templates[] = '@' . $modName . '/objects/' . $tplType . '-' . $objectName . $extension;
            $templates[] = '@' . $modName . '/objects/' . $tplType . $extension;
            if ($modName !== 'dynamicdata') {
                $templates[] = $themeName . '/modules/dynamicdata/objects/' . $tplType . '-' . $objectName . $extension;
                $templates[] = $themeName . '/modules/dynamicdata/objects/' . $tplType . $extension;
                $templates[] = '@dynamicdata/objects/' . $tplType . '-' . $objectName . $extension;
                $templates[] = '@dynamicdata/objects/' . $tplType . $extension;
            }
        }

        $templateName = static::findTwigTemplate($twig, $templates);
        xarCoreCache::setCached('Templates.Twig', $cachename, $templateName);

        return $templateName;
    }

    /**
     * @param string $modName
     * @param string $propertyName
     * @param string $tplType
     * @param array<string, mixed> $tplData
     * @param ?string $tplBase - unused
     * @return string
     */
    public static function property($modName, $propertyName, $tplType = 'showoutput', $tplData = [], $tplBase = null)
    {
        // xarTwigTpl::property(base, dropdown, showoutput, [...], )
        if (is_bool($tplData['context']['twig'])) {
            $tplData['context']['twig'] = static::getTwig([], [], $tplData['context']);
        }
        $themeName = $tplData['context']['theme'] ?? xarTpl::getThemeName();
        $trace = "xarTwigTpl::property($modName, $propertyName, $tplType, [...], $tplBase)";
        /** @var Environment $twig */
        $twig = $tplData['context']['twig'];
        $templateName = static::findPropertyTemplate($twig, $themeName, $modName, $propertyName, $tplType, $tplBase ?? '');
        if (empty($templateName)) {
            //return parent::property($modName, $propertyName, $tplType, $tplData, $tplBase);
            return $trace;
        }
        $template = $twig->load($templateName);
        return static::renderTemplate($template, $tplData, $templateName, $trace);
    }

    /**
     * @param Environment $twig
     * @param string $themeName
     * @param string $modName
     * @param string $propertyName
     * @param string $tplType
     * @param string $tplBase - unused
     * @return string|null
     */
    public static function findPropertyTemplate($twig, $themeName, $modName, $propertyName, $tplType, $tplBase)
    {
        $cachename = "property:$themeName:$modName:$propertyName:$tplType:$tplBase";
        // cache frequently-used sourcefilenames 
        if (xarCoreCache::isCached('Templates.Twig', $cachename))
            return xarCoreCache::getCached('Templates.Twig', $cachename);

        // @todo define this in theme config
        $extension = '.html.twig';
        if ($themeName === 'rss') {
            $extension = '.xml.twig';
        }
        $templates = [];
        $templates[] = $themeName . '/modules/' . $modName . '/properties/' . $tplType . '-' . $propertyName . $extension;
        $templates[] = $themeName . '/modules/' . $modName . '/properties/' . $tplType . $extension;
        // @todo many property templates are actually in the base module
        $templates[] = '@' . $modName . '/properties/' . $tplType . '-' . $propertyName . $extension;
        $templates[] = '@' . $modName . '/properties/' . $tplType . $extension;
        if ($modName !== 'dynamicdata') {
            $templates[] = $themeName . '/modules/dynamicdata/properties/' . $tplType . '-' . $propertyName . $extension;
            $templates[] = $themeName . '/modules/dynamicdata/properties/' . $tplType . $extension;
            $templates[] = '@dynamicdata/properties/' . $tplType . '-' . $propertyName . $extension;
            $templates[] = '@dynamicdata/properties/' . $tplType . $extension;
        }

        $templateName = static::findTwigTemplate($twig, $templates);
        xarCoreCache::setCached('Templates.Twig', $cachename, $templateName);

        return $templateName;
    }
}
