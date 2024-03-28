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

    public static function findTwigTemplate($twig, $templates)
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

    public static function renderPage($mainModuleOutput, $pageTemplate = null, $context = null)
    {
        // xarTwigTpl::renderPage('...', 'theme', default, user, null, 'pages')
        if (is_bool($context['twig'])) {
            $context['twig'] = static::getTwig([], [], $context);
        }
        $themeName = xarTpl::getThemeName();
        $trace = "xarTwigTpl::renderPage('...', 'theme', $themeName, $pageTemplate, null, 'pages')";
        // get page template source (current > common)
        //$sourceFileName = self::getScopeFileName('theme', self::getThemeName(), $pageTemplate, null, 'pages');
        /** @var Environment $twig */
        $twig = $context['twig'];
        $templateName = static::findThemeTemplate($twig, $themeName, 'pages', $pageTemplate);
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

    public static function findThemeTemplate($twig, $themeName, $tplType, $tplName, $pageName = null)
    {
        $templates = [];
        // @todo align better with current theme template lookup?
        if (!empty($pageName)) {
            $templates[] = $themeName . '/' . $tplType . '/' . $tplName . '-' . $pageName . '.html.twig';
        }
        $templates[] = $themeName . '/' . $tplType . '/' . $tplName . '.html.twig';
        if ($themeName != 'default') {
            if (!empty($pageName)) {
                $templates[] = 'default/' . $tplType . '/' . $tplName . '-' . $pageName . '.html.twig';
            }
            $templates[] = 'default/' . $tplType . '/' . $tplName . '.html.twig';
        }
        if ($themeName != 'common') {
            if (!empty($pageName)) {
                $templates[] = 'common/' . $tplType . '/' . $tplName . '-' . $pageName . '.html.twig';
            }
            $templates[] = 'common/' . $tplType . '/' . $tplName . '.html.twig';
        }
        return static::findTwigTemplate($twig, $templates);
    }

    public static function module($modName, $modType, $funcName, $tplData = [], $tplName = null)
    {
        // xarTwigTpl::module(workflow, user, showactions, [...], updated)
        if (is_bool($tplData['context']['twig'])) {
            $tplData['context']['twig'] = static::getTwig([], [], $tplData['context']);
        }
        $trace = "xarTwigTpl::module($modName, $modType, $funcName, [...], $tplName)";
        /** @var Environment $twig */
        $twig = $tplData['context']['twig'];
        $templateName = static::findModuleTemplate($twig, $modName, $modType, $funcName, $tplName);
        if (empty($templateName)) {
            //return parent::module($modName, $modType, $funcName, $tplData, $templateName);
            return $trace;
        }
        $template = $twig->load($templateName);
        return static::renderTemplate($template, $tplData, $templateName, $trace);
    }

    public static function findModuleTemplate($twig, $modName, $modType, $funcName, $templateName)
    {
        $templates = [];
        // user templates are now in the top level directory and all others in subdirectories
        if ($modType == 'user') {
            if (!empty($templateName)) {
                $templates[] = '@' . $modName . '/' . $funcName . '-' . $templateName . '.html.twig';
            }
            $templates[] = '@' . $modName . '/' . $funcName . '.html.twig';
            if ($modName !== 'dynamicdata') {
                if (!empty($templateName)) {
                    $templates[] = '@dynamicdata/' . $funcName . '-' . $templateName . '.html.twig';
                }
                $templates[] = '@dynamicdata/' . $funcName . '.html.twig';
            }
        } else {
            if (!empty($templateName)) {
                $templates[] = '@' . $modName . '/' . $modType . '/' . $funcName . '-' . $templateName . '.html.twig';
            }
            $templates[] = '@' . $modName . '/' . $modType . '/' . $funcName . '.html.twig';
            if ($modName !== 'dynamicdata') {
                if (!empty($templateName)) {
                    $templates[] = '@dynamicdata/' . $modType . '/' . $funcName . '-' . $templateName . '.html.twig';
                }
                $templates[] = '@dynamicdata/' . $modType . '/' . $funcName . '.html.twig';
            }
        }
        return static::findTwigTemplate($twig, $templates);
    }

    public static function block($modName, $blockType, $tplData = [], $tplName = null, $tplBase = null, $tplModule = null)
    {
        //return parent::block($modName, $blockType, $tplData, $tplName, $tplBase, $tplModule);
        return "xarTwigTpl::block($modName, $blockType, [...], $tplName, $tplBase, $tplModule)";
    }

    public static function findBlockTemplate($twig, $modName, $blockType, $tplName, $tplBase, $tplModule)
    {
        return null;
    }

    public static function object($modName, $objectName, $tplType = 'showdisplay', $tplData = [], $tplBase = null)
    {
        if (is_bool($tplData['context']['twig'])) {
            $tplData['context']['twig'] = static::getTwig([], [], $tplData['context']);
        }
        $trace = "xarTwigTpl::object($modName, $objectName, $tplType, [...], $tplBase)";
        /** @var Environment $twig */
        $twig = $tplData['context']['twig'];
        $templateName = static::findObjectTemplate($twig, $modName, $tplType, $objectName, $tplBase);
        if (empty($templateName)) {
            //return parent::object($modName, $objectName, $tplType, $tplData, $tplBase);
            return $trace;
        }
        $template = $twig->load($templateName);
        return static::renderTemplate($template, $tplData, $templateName, $trace);
    }

    public static function findObjectTemplate($twig, $modName, $tplType, $objectName, $tplBase)
    {
        $templates = [];
        // @todo ui_* templates are typically not overridden by objectName, but they could be...
        if (str_starts_with($tplType, 'ui_')) {
            $templates[] = '@' . $modName . '/objects/' . $tplType . '.html.twig';
            if ($modName !== 'dynamicdata') {
                $templates[] = '@dynamicdata/objects/' . $tplType . '.html.twig';
            }
        } else {
            $templates[] = '@' . $modName . '/objects/' . $tplType . '-' . $objectName . '.html.twig';
            $templates[] = '@' . $modName . '/objects/' . $tplType . '.html.twig';
            if ($modName !== 'dynamicdata') {
                $templates[] = '@dynamicdata/objects/' . $tplType . '-' . $objectName . '.html.twig';
                $templates[] = '@dynamicdata/objects/' . $tplType . '.html.twig';
            }
        }
        return static::findTwigTemplate($twig, $templates);
    }

    public static function property($modName, $propertyName, $tplType = 'showoutput', $tplData = [], $tplBase = null)
    {
        // xarTwigTpl::property(base, dropdown, showoutput, [...], )
        if (is_bool($tplData['context']['twig'])) {
            $tplData['context']['twig'] = static::getTwig([], [], $tplData['context']);
        }
        $trace = "xarTwigTpl::property($modName, $propertyName, $tplType, [...], $tplBase)";
        /** @var Environment $twig */
        $twig = $tplData['context']['twig'];
        $templateName = static::findPropertyTemplate($twig, $modName, $propertyName, $tplType, $tplBase);
        if (empty($templateName)) {
            //return parent::property($modName, $propertyName, $tplType, $tplData, $tplBase);
            return $trace;
        }
        $template = $twig->load($templateName);
        return static::renderTemplate($template, $tplData, $templateName, $trace);
    }

    public static function findPropertyTemplate($twig, $modName, $propertyName, $tplType, $tplBase)
    {
        $templates = [];
        // @todo many property templates are actually in the base module
        $templates[] = '@' . $modName . '/properties/' . $tplType . '-' . $propertyName . '.html.twig';
        $templates[] = '@' . $modName . '/properties/' . $tplType . '.html.twig';
        if ($modName !== 'dynamicdata') {
            $templates[] = '@dynamicdata/properties/' . $tplType . '-' . $propertyName . '.html.twig';
            $templates[] = '@dynamicdata/properties/' . $tplType . '.html.twig';
        }
        return static::findTwigTemplate($twig, $templates);
    }
}
