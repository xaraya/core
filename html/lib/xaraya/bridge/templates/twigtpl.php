<?php

/**
 * Use Twig template engine for output in Xaraya
 */
use Twig\Environment;
use Twig\Loader\LoaderInterface;
use Twig\TemplateWrapper;
use Xaraya\Bridge\TemplateEngine\TwigConfig;
use Xaraya\Context\Context;
use Xaraya\Services\TemplatingService;
use Xaraya\Services\WithServicesClass;

/**
 * Use Twig template engine to generate output in Xaraya
 *
 * @link https://twig.symfony.com/
 */
class xarTwigTpl
{
    use WithServicesClass;

    protected ?TemplatingService $tplService = null;

    protected function tpl(): TemplatingService
    {
        if (!isset($this->tplService)) {
            $xar = $this->getServicesClass();
            $this->tplService = $xar->tpl();
        }
        return $this->tplService;
    }

    public function __construct($xar = null)
    {
        $this->setServicesClass($xar);
    }

    public function getTwigEnvironment($context)
    {
        return TwigConfig::getTwigEnvironment($context, $this->getServicesClass());
    }

    /**
     * Find the first available template in the list or null
     * @param Environment $twig
     * @param list<string> $templates
     * @return string|null
     */
    public function findTwigTemplate(Environment $twig, array $templates)
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
    public function renderTemplate($template, $tplData, $templateName, $caller = '')
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
        if (empty($caller) || !$this->tpl()->outputTemplateFilenames()) {
            return $output;
        }
        return '<!-- start: ' . $templateName . " -->\n"
            //'<!-- args: ' . $caller . ' -->' .
            . trim($output)
            . '<!-- end: ' . $templateName . " -->\n";
    }

    /**
     * @param string $mainModuleOutput
     * @param ?string $pageTemplate
     * @param ?Context<string, mixed> $context
     * @return string
     */
    public function renderPage($mainModuleOutput, $pageTemplate = null, $context = null)
    {
        if (!isset($context) || !isset($context['twig'])) {
            throw new Exception('How did we end up here without twig context?');
        }
        // xarTwigTpl::renderPage('...', 'theme', default, user, null, 'pages')
        if (is_bool($context['twig'])) {
            $context['twig'] = $this->getTwigEnvironment($context);
        }
        if (empty($pageTemplate)) {
            $pageTemplate = $context['page'] ?? $this->tpl()->getPageTemplateName();
        }
        $themeName = $context['theme'] ?? $this->tpl()->getThemeName();
        $caller = "xarTwigTpl::renderPage('...', 'theme', $themeName, $pageTemplate, null, 'pages')";
        // get page template source (current > common)
        //$sourceFileName = self::getScopeFileName('theme', self::getThemeName(), $pageTemplate, null, 'pages');
        /** @var Environment $twig */
        $twig = $context['twig'];
        $templateName = $this->findPageTemplate($twig, $themeName, 'pages', $pageTemplate, '');
        if (empty($templateName)) {
            //return $this->tpl()->renderPage($mainModuleOutput, $pageTemplate, $context);
            return 'Twig template not found: ' . $caller;
        }
        // see $this->tpl()->renderPage
        $tpl = (object) null; // Create an object to hold the 'specials'
        $tpl->pageTitle = $this->tpl()->getPageTitle();
        $tplData = [
            'tpl'                      => $tpl,
            '_bl_mainModuleOutput'     => $mainModuleOutput,
            // not really needed for xar_blockgroup() etc. as context is already known in extension, but let's be consistent
            '_bl_context'              => $context,
        ];
        $template = $twig->load($templateName);
        // don't use trace in page templates to avoid adding comments to page
        return $this->renderTemplate($template, $tplData, $templateName, '');
    }

    /**
     * @param Environment $twig
     * @param string $themeName
     * @param string $tplType
     * @param string $tplName
     * @param string $pageName - unused?
     * @return string|null
     */
    public function findPageTemplate($twig, $themeName, $tplType, $tplName, $pageName)
    {
        $mem = $this->getServicesClass()->mem();
        $cachename = "page:$themeName:$tplType:$tplName:$pageName";
        // cache frequently-used sourcefilenames
        if ($mem->has('Templates.Twig', $cachename)) {
            return $mem->get('Templates.Twig', $cachename);
        }

        $extension = TwigConfig::getThemeExtension($themeName);
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

        $templateName = $this->findTwigTemplate($twig, $templates);
        $mem->set('Templates.Twig', $cachename, $templateName);

        return $templateName;
    }

    public function renderBlockBox($blockInfo, $tplName = null)
    {
        // xarTwigTpl::module(workflow, user, showactions, [...], updated)
        if (is_bool($blockInfo['context']['twig'])) {
            $blockInfo['context']['twig'] = $this->getTwigEnvironment($blockInfo['context']);
        }
        $themeName = $blockInfo['context']['theme'] ?? $this->tpl()->getThemeName();
        $caller = "[$themeName] xarTwigTpl::renderBlockBox([...], $tplName)";
        /** @var Environment $twig */
        $twig = $blockInfo['context']['twig'];
        $templateName = $this->findBoxTemplate($twig, $themeName, $tplName ?? '');
        if (empty($templateName)) {
            //return $this->tpl()->renderPage($mainModuleOutput, $pageTemplate, $context);
            return 'Twig template not found: ' . $caller;
        }
        //var_dump($blockInfo);
        //return $templateName . ':' . $caller;
        $template = $twig->load($templateName);
        return $this->renderTemplate($template, $blockInfo, $templateName, $caller);
    }

    /**
     * @param Environment $twig
     * @param string $themeName
     * @param string $tplName - optional
     * @return string|null
     */
    public function findBoxTemplate($twig, $themeName, $tplName)
    {
        $mem = $this->getServicesClass()->mem();
        $cachename = "box:$themeName:$tplName";
        // cache frequently-used sourcefilenames
        if ($mem->has('Templates.Twig', $cachename)) {
            return $mem->get('Templates.Twig', $cachename);
        }

        $extension = TwigConfig::getThemeExtension($themeName);
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

        $templateName = $this->findTwigTemplate($twig, $templates);
        $mem->set('Templates.Twig', $cachename, $templateName);

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
    public function module($modName, $modType, $funcName, $tplData = [], $tplName = null)
    {
        // xarTwigTpl::module(workflow, user, showactions, [...], updated)
        if (is_bool($tplData['context']['twig'])) {
            $tplData['context']['twig'] = $this->getTwigEnvironment($tplData['context']);
        }
        $themeName = $tplData['context']['theme'] ?? $this->tpl()->getThemeName();
        $caller = "[$themeName] xarTwigTpl::module($modName, $modType, $funcName, [...], $tplName)";
        /** @var Environment $twig */
        $twig = $tplData['context']['twig'];
        $templateName = $this->findModuleTemplate($twig, $themeName, $modName, $modType, $funcName, $tplName ?? '');
        if (empty($templateName)) {
            //return $this->tpl()->module($modName, $modType, $funcName, $tplData, $templateName);
            return 'Twig template not found: ' . $caller;
        }
        $template = $twig->load($templateName);
        return $this->renderTemplate($template, $tplData, $templateName, $caller);
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
    public function findModuleTemplate($twig, $themeName, $modName, $modType, $funcName, $tplName)
    {
        $mem = $this->getServicesClass()->mem();
        $cachename = "module:$themeName:$modName:$modType:$funcName:$tplName";
        // cache frequently-used sourcefilenames
        if ($mem->has('Templates.Twig', $cachename)) {
            return $mem->get('Templates.Twig', $cachename);
        }

        $extension = TwigConfig::getThemeExtension($themeName);
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

        $templateName = $this->findTwigTemplate($twig, $templates);
        $mem->set('Templates.Twig', $cachename, $templateName);

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
    public function block($modName, $blockType, $tplData = [], $tplName = null, $tplBase = null, $tplModule = null)
    {
        if (is_bool($tplData['context']['twig'])) {
            $tplData['context']['twig'] = $this->getTwigEnvironment($tplData['context']);
        }
        $themeName = $tplData['context']['theme'] ?? $this->tpl()->getThemeName();
        //return $this->tpl()->block($modName, $blockType, $tplData, $tplName, $tplBase, $tplModule);
        $caller = "[$themeName] xarTwigTpl::block($modName, $blockType, [...], $tplName, $tplBase, $tplModule)";
        /** @var Environment $twig */
        $twig = $tplData['context']['twig'];
        $templateName = $this->findBlockTemplate($twig, $themeName, $modName, $blockType, $tplName ?? '', $tplBase ?? '', $tplModule ?? '');
        if (empty($templateName)) {
            //return $this->tpl()->object($modName, $objectName, $tplType, $tplData, $tplBase);
            return 'Twig template not found: ' . $caller;
        }
        //var_dump($tplData);
        //return $templateName . ':' . $caller;
        $template = $twig->load($templateName);
        return $this->renderTemplate($template, $tplData, $templateName, $caller);
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
    public function findBlockTemplate($twig, $themeName, $modName, $blockType, $tplName, $tplBase, $tplModule)
    {
        $mem = $this->getServicesClass()->mem();
        $cachename = "block:$themeName:$modName:$blockType:$tplName:$tplBase:$tplModule";
        // cache frequently-used sourcefilenames
        if ($mem->has('Templates.Twig', $cachename)) {
            return $mem->get('Templates.Twig', $cachename);
        }

        // use name of blocktype as base unless over-ridden
        $tplBase = empty($tplBase) ? $blockType : $tplBase;

        // [default] xarTwigTpl::block(base, adminmenu, [...], , verticallistbycats, )
        $extension = TwigConfig::getThemeExtension($themeName);
        $templates = [];
        $templates[] = '@theme/' . $themeName . '/modules/' . $modName . '/blocks/' . $tplBase . $extension;
        $templates[] = '@' . $modName . '/blocks/' . $tplBase . $extension;
        // many block templates are actually in the base module
        if ($modName !== 'base') {
            $templates[] = '@theme/' . $themeName . '/modules/base/blocks/' . $tplBase . $extension;
            $templates[] = '@base/blocks/' . $tplBase . $extension;
        }

        $templateName = $this->findTwigTemplate($twig, $templates);
        $mem->set('Templates.Twig', $cachename, $templateName);

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
     * @param string $modName
     * @param string $objectName
     * @param string $tplType
     * @param array<string, mixed> $tplData
     * @param ?string $tplBase - unused
     * @return string
     */
    public function object($modName, $objectName, $tplType = 'showdisplay', $tplData = [], $tplBase = null)
    {
        if (is_bool($tplData['context']['twig'])) {
            $tplData['context']['twig'] = $this->getTwigEnvironment($tplData['context']);
        }
        $themeName = $tplData['context']['theme'] ?? $this->tpl()->getThemeName();
        $caller = "[$themeName] xarTwigTpl::object($modName, $objectName, $tplType, [...], $tplBase)";
        /** @var Environment $twig */
        $twig = $tplData['context']['twig'];
        $templateName = $this->findObjectTemplate($twig, $themeName, $modName, $objectName, $tplType, $tplBase ?? '');
        if (empty($templateName)) {
            //return $this->tpl()->object($modName, $objectName, $tplType, $tplData, $tplBase);
            return 'Twig template not found: ' . $caller;
        }
        $template = $twig->load($templateName);
        return $this->renderTemplate($template, $tplData, $templateName, $caller);
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
    public function findObjectTemplate($twig, $themeName, $modName, $objectName, $tplType, $tplBase)
    {
        $mem = $this->getServicesClass()->mem();
        $cachename = "object:$themeName:$modName:$objectName:$tplType:$tplBase";
        // cache frequently-used sourcefilenames
        if ($mem->has('Templates.Twig', $cachename)) {
            return $mem->get('Templates.Twig', $cachename);
        }

        $extension = TwigConfig::getThemeExtension($themeName);
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

        $templateName = $this->findTwigTemplate($twig, $templates);
        $mem->set('Templates.Twig', $cachename, $templateName);

        return $templateName;
    }

    /**
     * @param string $modName
     * @param string $propertyName
     * @param string $tplType
     * @param array<string, mixed> $tplData
     * @param ?string $tplBase - used by xar:data-label - why not change tplType?
     * @return string
     */
    public function property($modName, $propertyName, $tplType = 'showoutput', $tplData = [], $tplBase = null)
    {
        // @todo check and handle stand-alone properties with module 'auto' + adapt includes path
        // xarTwigTpl::property(base, dropdown, showoutput, [...], )
        if (is_bool($tplData['context']['twig'])) {
            $tplData['context']['twig'] = $this->getTwigEnvironment($tplData['context']);
        }
        $themeName = $tplData['context']['theme'] ?? $this->tpl()->getThemeName();
        $caller = "[$themeName] xarTwigTpl::property($modName, $propertyName, $tplType, [...], $tplBase)";
        /** @var Environment $twig */
        $twig = $tplData['context']['twig'];
        $templateName = $this->findPropertyTemplate($twig, $themeName, $modName, $propertyName, $tplType, $tplBase ?? '');
        if (empty($templateName)) {
            //return $this->tpl()->property($modName, $propertyName, $tplType, $tplData, $tplBase);
            return 'Twig template not found: ' . $caller;
        }
        $template = $twig->load($templateName);
        return $this->renderTemplate($template, $tplData, $templateName, $caller);
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
    public function findPropertyTemplate($twig, $themeName, $modName, $propertyName, $tplType, $tplBase)
    {
        $mem = $this->getServicesClass()->mem();
        $cachename = "property:$themeName:$modName:$propertyName:$tplType:$tplBase";
        // cache frequently-used sourcefilenames
        if ($mem->has('Templates.Twig', $cachename)) {
            return $mem->get('Templates.Twig', $cachename);
        }
        if (!empty($tplBase)) {
            $tplType = xarVarPrep::path($tplBase);
        }

        $extension = TwigConfig::getThemeExtension($themeName);
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

        $templateName = $this->findTwigTemplate($twig, $templates);
        $mem->set('Templates.Twig', $cachename, $templateName);

        return $templateName;
    }
}
