<?php

/**
 * Templating available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use Xaraya\Bridge\TemplateEngine\TwigConfig;
use CompiledTemplate;
use DataPropertyMaster;
use XarayaCompiler;
use XarayaSourceTemplate;
use xarConst;
use xarCore;
use xarTemplateCache;
use xarTplPager;
use xarTwigTpl;
use xarVarPrep;
use sys;
use BadParameterException;
use DirectoryNotFoundException;
use FileNotFoundException;
use Exception;

/**
 * For documentation purposes only - available via TemplatingTrait
 */
interface TemplatingInterface extends ServiceInterface
{
    public const SLICE = 'templating';

    /** @param array<string, mixed> $tplData */
    public function module(string $modName, string $modType, string $funcName, array $tplData = [], ?string $templateName = null): string;

    /** @param array<string, mixed> $tplData */
    public function block(string $modName, string $blockType, array $tplData = [], ?string $tplName = null, ?string $tplBase = null, ?string $tplModule = null): string;

    /** @param array<string, mixed> $tplData */
    public function object(string $modName, string $objectName, string $tplType, array $tplData = []): string;

    /** @param array<string, mixed> $tplData */
    public function property(string $modName, string $propertyName, string $tplType = 'showoutput', array $tplData = [], ?string $tplBase = null): string;

    public function getPageTitle(): string;

    public function setPageTitle(string $title, ?string $modName = null): bool;

    public function getPageTemplateName(): string;

    public function setPageTemplateName(string $templateName): bool;

    public function setDoctype(string $doctypeName): bool;

    public function getDoctype(): string;

    public function setBaseDir(string $themesDir): bool;

    public function getBaseDir(): string;

    public function getThemeDir(?string $theme = null): string;

    public function setThemeDir(string $themeDir): bool;

    public function getThemeName(): string;

    public function setThemeName(string $themeName): bool;

    public function getThemeUrl(?string $theme = null): string;

    public function getCodeUrl(): string;

    public function getImage(string $fileName, ?string $scope = null, ?string $package = null): ?string;

    public function getFile(string $fileName, ?string $scope = null, ?string $package = null): ?string;

    /** @param array<mixed> $tplData */
    public function string(string $templateCode, array $tplData): string;

    /** @param array<mixed> $tplData */
    public function file(string $fileName, array &$tplData): string;

    public function compileString(string $templateSource): string;

    /** @param int|array<mixed> $blockOptions */
    public function getPager(int $startNum, int $total, string $urltemplate, int $itemsPerPage = 10, int|array $blockOptions = [], string $template = 'default', string $tplmodule = 'base'): string;

    public function renderPage(string $mainModuleOutput, ?string $pageTemplate = null): string;

    /** @param array<string, mixed> $blockInfo */
    public function renderBlockBox(array $blockInfo, ?string $templateName = null): string;

    public function includeTemplate($tplType, $package, $tplBase, $tplData = [], $tplPart = 'includes', $tplName = null, $callerMod = null): string;

    /** @param array<mixed> $tplData */
    public function executeFromFile(string $sourceFileName, array $tplData, string $tplType = 'module'): string;

    public function outputTemplate(string $sourceFileName, string $tplOutput): string;

    public function outputPHPCommentBlockInTemplates(): int;

    public function outputTemplateFilenames(): int;
}

/**
 * Templating available via methods
 */
trait TemplatingTrait
{
    use ServiceTrait;
    public const SCOPE = 'Templating.Config';

    // @todo replace properties with xar::mem() or context
    protected $themeName;
    protected $themeDir;

    protected $generateXMLURLs  = true;
    protected $doctype          = 'xhtml1-strict';
    protected $pageTemplateName;
    protected $pageTitle;

    protected $isHeaderContent;
    protected $showPHPCommentBlockInTemplates;
    protected $showTemplateFilenames;
    protected ?xarTwigTpl $twigTpl = null;
    protected bool $initialized = false;

    /**
     * Initialize service class
     * @param array<string, mixed> $config
     */
    public function init(array $config = []): bool
    {
        if (empty($config)) {
            if ($this->initialized) {
                return true;
            }
            $config = $this->getConfig();
        }
        // This is the theme directory, solo (aka, themename)
        $this->setThemeDir($config['defaultThemeDir']);

        // This is the default document type
        if (isset($config['defaultDocType'])) {
            $this->doctype = $config['defaultDocType'];
        }

        // This should be always true or better defined if it's
        // a client thing (js internal code generation for example)
        $this->generateXMLURLs = $config['generateXMLURLs'];

        $pageTemplateName = $config['pageTemplateName'] ?? 'default';
        if (!$this->setPageTemplateName($pageTemplateName)) {
            // If there is no page template, we can't show anything
            throw new FileNotFoundException($pageTemplateName . '.xt', "xar::tpl()->init: Called a nonexistent #(1) page in theme directory '" . $this->getThemeDir() . "'");
        }

        // initialize context for templating service
        if (empty($this->getContext()[static::SLICE])) {
            $this->getContext()[static::SLICE] = [
                'themeDir'         => $this->themeDir,
                'themeName'        => $this->themeName,
                'pageTemplateName' => $this->pageTemplateName,
                'generateXMLURLs'  => $this->generateXMLURLs,
                'doctype'          => $this->doctype,
                'baseDir'          => $this->getBaseDir(),
                'pageTitle'        => $this->getPageTitle(),
            ];
        }

        // @todo is the core define still needed now?
        xarTemplateCache::init(sys::varpath() . xarConst::TPL_CACHEDIR, $config['enableTemplatesCaching']);

        $this->initialized = true;
        return true;
    }

    /**
     * Get configuration
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        $xar = $this->getParent();
        $systemArgs = [
            'enableTemplatesCaching' => $xar->config()->getVar('Site.BL.CacheTemplates'),
            'defaultThemeDir'        => $xar->mod('themes')->getVar('default_theme') ?? 'default',
            'generateXMLURLs'        => true,
            'defaultDocType'         => $xar->config()->getVar('Site.BL.DocType'),
        ];
        return $systemArgs;
    }

    public function isLoaded(): bool
    {
        return $this->initialized;
    }

    /**
     * Get value from current context or memory
     */
    protected function getValue(string $varName, ?string $default = '')
    {
        return $this->getContext()?->getSliceValue(static::SLICE, $varName) ?? $default;
    }

    /**
     * Set value in current context or memory
     */
    protected function setValue(string $varName, ?string $value)
    {
        $this->getContext()?->setSliceValue(static::SLICE, $varName, $value);
    }

    protected function getTwigTpl()
    {
        if (!isset($this->twigTpl)) {
            $this->twigTpl = new xarTwigTpl($this->getParent());
        }
        return $this->twigTpl;
    }

    /**
     * Render output with module template
     * @param string $modName
     * @param string $modType
     * @param string $funcName
     * @param array<string, mixed> $tplData
     * @param ?string $templateName
     * @return string
     */
    public function module(string $modName, string $modType, string $funcName, array $tplData = [], ?string $templateName = null): string
    {
        // Add standard template variables (module, itemtype and context)
        // @todo $tplData = $this->prepare($tplData);
        $tplData['context'] ??= $this->getContext();

        // See if we have a special template to apply
        if (!isset($templateName) && isset($tplData['_bl_template'])) {
            $templateName = (string) $tplData['_bl_template'];
        }

        if (!empty($tplData['context']) && !empty($tplData['context']['twig'])) {
            if (TwigConfig::isModuleSupported($modName)) {
                return $this->getTwigTpl()->module($modName, $modType, $funcName, $tplData, $templateName);
            }
        }
        // Basename of module template is apitype-functioname
        $tplBase        = "$modType-$funcName";

        // Get the right source filename (current > common > module)
        $sourceFileName = $this->getScopeFileName('module', $modName, $tplBase, $templateName);

        // Common data for BL
        $tplData['_bl_module_name'] = $modName;
        $tplData['_bl_module_type'] = $modType;
        $tplData['_bl_module_func'] = $funcName;
        $tplData['_bl_template']    = $sourceFileName;
        $tpl = (object) null;
        $tpl->pageTitle = $this->getPageTitle();
        $tplData['tpl'] = $tpl;

        $xar = $this->getParent();
        // TODO: make this work different, for example:
        // 1. Only create a link somewhere on the page,
        //    when clicked opens a page with the variables on that page
        // 2. Create a page in the themes module with an interface
        // 3. Use 1. to link to 2.
        // @checkme: modules is a depency of templates, redundant check?
        if (empty($xar->mem()->get('installer', 'installing')) && $xar->mod()->isLoaded() && $xar->user()->isLoaded()) {
            if ($xar->mod('themes')->getVar('variable_dump') && $xar->user()->isDebugAdmin()) {
                echo '<pre>',var_export($tplData, 1),'</pre>';
            }
        }

        if (empty($sourceFileName)) {
            throw new FileNotFoundException("Module: [$modName],[$tplBase],[$templateName]");
        }
        return $this->executeFromFile($sourceFileName, $tplData);
    }

    /**
     * Render output with object template
     * @param string $modName
     * @param string $blockType
     * @param array<string, mixed> $tplData
     * @param ?string $tplName
     * @param ?string $tplBase
     * @param ?string $tplModule - for stand-alone blocks
     * @return string
     */
    public function block(string $modName, string $blockType, array $tplData = [], ?string $tplName = null, ?string $tplBase = null, ?string $tplModule = null): string
    {
        // Add standard template variables (module, itemtype and context)
        // @todo $tplData = $this->prepare($tplData);
        $tplData['context'] ??= $this->getContext();

        // use name of blocktype as base unless over-ridden
        $tplBase = empty($tplBase) ? $blockType : $tplBase;
        if (!empty($tplData['context']) && !empty($tplData['context']['twig'])) {
            if (TwigConfig::isBlockSupported($tplBase, $modName)) {
                return $this->getTwigTpl()->block($modName, $blockType, $tplData, $tplName, $tplBase, $tplModule);
            }
        }
        if (!empty($modName)) {
            // get module block template (current > common > module)
            $sourceFileName = $this->getScopeFileName('module', $modName, $tplBase, $tplName, 'blocks');
        } else {
            // get standalone block template (current > common > module override > block)
            $sourceFileName = $this->getScopeFileName('block', $blockType, $tplBase, $tplName, '', $tplModule);
        }
        if (empty($sourceFileName)) {
            $msg = "Block: [#(1)],[#(2)],[#(3)]";
            $vars = [$modName, $tplBase, $tplName];
            throw new FileNotFoundException($vars, $msg);
        }
        return $this->executeFromFile($sourceFileName, $tplData);
    }

    /**
     * Render output with object template
     * @param string $modName
     * @param string $objectName
     * @param string $tplType
     * @param array<string, mixed> $tplData
     * @return string
     */
    public function object(string $modName, string $objectName, string $tplType, array $tplData = []): string
    {
        // Add standard template variables (module, itemtype and context)
        // @todo $tplData = $this->prepare($tplData);
        $tplData['context'] ??= $this->getContext();

        if (!empty($tplData['context']) && !empty($tplData['context']['twig'])) {
            if (TwigConfig::isObjectSupported($objectName, $modName)) {
                return $this->getTwigTpl()->object($modName, $objectName, $tplType, $tplData);
            }
        }
        $xar = $this->getParent();
        $modName = xarVarPrep::path($modName);
        $objectName = xarVarPrep::path($objectName);
        $tplType = xarVarPrep::path($tplType);
        $tplBase   = empty($tplBase) ? $tplType : xarVarPrep::path($tplBase);
        $cachename = "$modName:$objectName:$tplType:$tplBase:objects";

        // cache frequently-used sourcefilenames for DD elements
        if ($xar->mem()->has('Templates.DDElement', $cachename)) {
            $sourceFileName = $xar->mem()->get('Templates.DDElement', $cachename);
            return $this->executeFromFile($sourceFileName, $tplData);
        }

        $sourceFileName = $this->getScopeFileName('module', $modName, $tplBase, $objectName, 'objects');
        if (empty($sourceFileName) && $modName != 'dynamicdata') {
            $sourceFileName = $this->getScopeFileName('module', 'dynamicdata', $tplBase, $objectName, 'objects');
        }

        if (empty($sourceFileName)) {
            throw new FileNotFoundException("DD Element: [$modName],[$tplBase],[$objectName]");
        }

        $xar->mem()->set('Templates.DDElement', $cachename, $sourceFileName);

        return $this->executeFromFile($sourceFileName, $tplData);
    }

    /**
     * Render output with property template
     * @param string $modName
     * @param string $propertyName
     * @param string $tplType
     * @param array<string, mixed> $tplData
     * @param ?string $tplBase
     * @return string
     */
    public function property(string $modName, string $propertyName, string $tplType = 'showoutput', array $tplData = [], ?string $tplBase = null): string
    {
        // Add standard template variables (module, itemtype and context)
        // @todo $tplData = $this->prepare($tplData);
        $tplData['context'] ??= $this->getContext();

        // @todo check and handle stand-alone properties with module 'auto' + adapt includes path
        if (!empty($tplData['context']) && !empty($tplData['context']['twig'])) {
            if (TwigConfig::isPropertySupported($propertyName, $modName)) {
                return $this->getTwigTpl()->property($modName, $propertyName, $tplType, $tplData, $tplBase);
            }
        }
        $xar = $this->getParent();
        $modName = xarVarPrep::path($modName);
        $propertyName = xarVarPrep::path($propertyName);
        $tplType = xarVarPrep::path($tplType);
        $tplBase   = empty($tplBase) ? $tplType : xarVarPrep::path($tplBase);
        $cachename = "$modName:$propertyName:$tplType:$tplBase:properties";

        // cache frequently-used sourcefilenames for DD elements
        if ($xar->mem()->has('Templates.DDElement', $cachename)) {
            $sourceFileName = $xar->mem()->get('Templates.DDElement', $cachename);
            return $this->executeFromFile($sourceFileName, $tplData);
        }

        $sourceFileName = $this->getScopeFileName('module', $modName, $tplBase, $propertyName, 'properties');

        // Property fall-back to default template in the module the property belongs to
        if (empty($sourceFileName)) {
            $tplModule = DataPropertyMaster::getProperty(['type' => $propertyName])->tplmodule;

            if ($modName == 'auto') {
                // standalone property called in standalone context
                $sourceFileName = $this->getScopeFileName('property', $propertyName, $tplBase, $propertyName);

                if (empty($sourceFileName)) {
                    // property inherits its template
                    $sourceFileName = $this->getScopeFileName('module', $tplModule, $tplBase, $propertyName, 'properties', $modName);
                }
            } else {
                // property called in module context
                if ($tplModule == 'auto') {
                    // standalone property (caller > owner)
                    $sourceFileName = $this->getScopeFileName('property', $propertyName, $tplBase, $propertyName, '', $modName);
                } else {
                    // module property (caller > owner)
                    $sourceFileName = $this->getScopeFileName('module', $tplModule, $tplBase, $propertyName, 'properties', $modName);

                }
            }
        }

        // fall back on dynamicdata template
        if (empty($sourceFileName)) {
            $sourceFileName = $this->getScopeFileName('module', 'dynamicdata', $tplBase, $propertyName, 'properties');
        }

        if (empty($sourceFileName)) {
            throw new FileNotFoundException("DD Element: [$modName],[$tplBase],[$propertyName]");
        }

        $xar->mem()->set('Templates.DDElement', $cachename, $sourceFileName);

        return $this->executeFromFile($sourceFileName, $tplData);
    }

    /**
     * Get page title
     */
    public function getPageTitle(): string
    {
        // Get pageTitle from current context
        $this->pageTitle = $this->getValue('pageTitle');
        return $this->pageTitle;
    }

    /**
     * Set page title
     * @param string $title
     * @param ?string $modName
     * @return bool
     */
    public function setPageTitle(string $title, ?string $modName = null): bool
    {
        // getModName() might not be available on all parents, so check first
        if (empty($modName) && method_exists($this->getParent(), 'getModName')) {
            $modName = $this->getParent()->getModName();
        }
        $xar = $this->getParent();

        // keep track of page title when we're caching
        $xar->cache()->setPageTitle($title, $modName);

        $xar->log()->info("xar::tpl()->setPageTitle: Setting pageTitle to $title");

        // @checkme: modules is a dependency of templates, redundant check?
        if (!empty($xar->mem()->get('installer', 'installing')) || !$xar->mod()->isLoaded()) {
            $this->pageTitle = $title;
        } else {
            $order      = $xar->mod('themes')->getVar('SiteTitleOrder');
            $separator  = $xar->mod('themes')->getVar('SiteTitleSeparator');
            if (empty($modName)) {
                // FIXME: the ucwords is layout stuff which doesn't belong here
                // <chris/> Why don't we just use display name then?
                $modName = ucwords($xar->mod()->getDisplayName($xar->mod()->getName()));
            }
            switch (strtolower($order)) {
                case 'default':
                default:
                    $this->pageTitle = $xar->mod('themes')->getVar('SiteName') . $separator . $modName . $separator . $title;
                    break;
                case 'sp':
                    $this->pageTitle = $xar->mod('themes')->getVar('SiteName') . $separator . $title;
                    break;
                case 'mps':
                    $this->pageTitle = $modName . $separator . $title . $separator . $xar->mod('themes')->getVar('SiteName');
                    break;
                case 'pms':
                    $this->pageTitle = $title . $separator . $modName . $separator . $xar->mod('themes')->getVar('SiteName');
                    break;
                case 'to':
                    $this->pageTitle = $title;
                    break;
            }
        }
        // Set pageTitle in current context
        $this->setValue('pageTitle', $this->pageTitle);
        return true;
    }

    /**
     * Get page template name
     */
    public function getPageTemplateName(): string
    {
        // Get pageTemplateName from current context
        $this->pageTemplateName = $this->getValue('pageTemplateName');
        return $this->pageTemplateName ?: 'default';
    }

    /**
     * Set page template name
     * @param  string $templateName Name of the page template
     * @return bool
     */
    public function setPageTemplateName(string $templateName): bool
    {
        assert($templateName != "");
        $xar = $this->getParent();

        $xar->log()->info("xar::tpl()->setPageTemplateName: Setting the template name to $templateName");

        if (!$this->exists('theme', $this->getThemeName(), $templateName, null, 'pages')) {
            return false;
        }
        $this->pageTemplateName = $templateName;
        // Set pageTemplateName in current context
        $this->setValue('pageTemplateName', $this->pageTemplateName);
        return true;
    }

    /**
     * Set doctype declared by page template
     * @param  string $doctypeName Identifier string of the doctype
     */
    public function setDoctype(string $doctypeName): bool
    {
        assert(is_string($doctypeName));
        $xar = $this->getParent();

        $xar->log()->info("xar::tpl()->setDoctype: Setting the doc type to $doctypeName");

        $this->doctype = $doctypeName;
        // Set doctype in current context
        $this->setValue('doctype', $this->doctype);
        return true;
    }

    /**
     * Get doctype declared by page template
     */
    public function getDoctype(): string
    {
        // Get doctype from current context
        if (!isset($this->doctype)) {
            $this->doctype = $this->getValue('doctype');
        }
        return $this->doctype;
    }

    /**
     * Set base dir
     *
     * Set the base directory for themes, added this for use by
     * the themes module to prevent setting a nonexistent directory
    **/
    public function setBaseDir(string $themesDir): bool
    {
        assert($themesDir != "" && $themesDir[0] != "/");
        $xar = $this->getParent();

        $xar->log()->info("xar::tpl()->setBaseDir: Setting the theme base dir to $themesDir");

        if (!is_dir($themesDir)) {
            // no directory
            throw new DirectoryNotFoundException($themesDir, 'xar::tpl()->setBaseDir: Nonexistent base themes directory #(1)');
        } elseif (!is_dir($themesDir . '/' . $this->getThemeName())) {
            // found a directory, but the current theme isn't in it
            throw new DirectoryNotFoundException([$this->getThemeName(), $themesDir], 'xar::tpl()->setBaseDir: Nonexistant theme #(1) in base themes directory #(2)');
        }
        $xar->config()->setVar('Site.BL.ThemesDirectory', $themesDir);
        // Set baseDir in current context
        $this->setValue('baseDir', $themesDir);
        return true;
    }

    /**
     * Get base directory
     */
    public function getBaseDir(): string
    {
        $xar = $this->getParent();
        try {
            $themesdir = sys::web() . $xar->config()->getVar('Site.BL.ThemesDirectory', 'themes');
        } catch (Exception $e) {
            $themesdir = sys::web() . 'themes';
        }
        return $themesdir;
    }

    /**
     * Get theme directory
     */
    public function getThemeDir(?string $theme = null): string
    {
        $currentBase = $this->getBaseDir();
        if (isset($theme) && is_dir($currentBase . '/' . $theme)) {
            return $currentBase . '/' . $theme;
        }
        // Get themeDir from current context
        $this->themeDir = $this->getValue('themeDir', $currentBase . '/' . 'default');
        return $this->themeDir;
    }

    /**
     * Set theme directory
     */
    public function setThemeDir(string $themeDir): bool
    {
        assert($themeDir != "" && $themeDir[0] != "/");
        $xar = $this->getParent();

        $xar->log()->info("xar::tpl()->setThemeDir: Setting the theme dir to $themeDir");

        $currentBase = $this->getBaseDir();
        if (is_dir($currentBase . '/' . $themeDir)) {
            // use current
        } elseif (is_dir($currentBase . '/common')) {
            // fall back to common
            $themeDir = 'common';
        } else {
            // @checkme: throw exception here vs return false in setThemeName ?
            throw new DirectoryNotFoundException("$currentBase/$themeDir", 'xar::tpl()->setThemeDir: Nonexistent theme directory #(1)');
        }
        $this->setThemeNameAndDir($themeDir);
        return true;
    }

    /**
     * Get theme name in use
     */
    public function getThemeName(): string
    {
        // Get themeName from current context
        $this->themeName = $this->getValue('themeName', null);
        if (isset($this->themeName)) {
            return $this->themeName;
        }
        $xar = $this->getParent();
        // If it is not set, set it return the default theme.
        // @checkme: modules is a depency of templates, redundant check?
        if ($xar->mod()->isLoaded()) {
            $themeName = $xar->mod('themes')->getVar('default_theme');
            if (!empty($themeName)) {
                $this->setThemeName($themeName);
            }
        } else {
            $this->themeName = 'default';
        }
        assert(isset($this->themeName));
        return $this->themeName;
    }

    /**
     * Set theme name
     */
    public function setThemeName(string $themeName): bool
    {
        assert($themeName != "" && $themeName[0] != "/");
        $xar = $this->getParent();

        $xar->log()->info("xar::tpl()->setThemeName: Setting the theme name to $themeName");

        $currentBase = $this->getBaseDir();
        if (!is_dir($currentBase . '/' . $themeName)) {
            // @checkme: return false here vs throw exception in setThemeDir ?
            return false;
        }
        $this->setThemeNameAndDir($themeName);
        return true;
    }

    /**
     * Private helper function for $this->setThemeName and $this->setThemeDir
     * @todo theme name and dir are not required to be identical
     * @return void
     */
    protected function setThemeNameAndDir(string $name): void
    {
        // dir and name are still required to be the same
        $this->themeName = $name;
        $this->themeDir  = $this->getBaseDir() . '/' . $name;
        // Set themeName in current context
        $this->setValue('themeName', $this->themeName);
        // Set themeDir in current context
        $this->setValue('themeDir', $this->themeDir);
    }

    /**
     * Get theme URL
     */
    public function getThemeUrl(?string $theme = null): string
    {
        $xar = $this->getParent();
        $themeDir = $this->getThemeDir($theme);

        // Turn relative path into an absolute URL
        $webDir = sys::web();
        if (!empty($webDir) && strpos($themeDir, $webDir) === 0) {
            $themeDir = substr($themeDir, strlen($webDir));
        }
        $themeUrl = $xar->ctl()->getBaseURL() . $themeDir;

        return $themeUrl;
    }

    /**
     * Get code URL
     */
    public function getCodeUrl(): string
    {
        $xar = $this->getParent();
        $codeDir = sys::code();

        // Turn relative path into an absolute URL
        $webDir = sys::web();
        if (!empty($webDir) && strpos($codeDir, $webDir) === 0) {
            $codeDir = substr($codeDir, strlen($webDir));
        }
        $codeUrl = $xar->ctl()->getBaseURL() . $codeDir;

        return $codeUrl;
    }

    /**
     * Get theme template image for module image
     * @param string $fileName
     * @param ?string $scope
     * @param ?string $package
     * @return string|null
     */
    public function getImage(string $fileName, ?string $scope = null, ?string $package = null): ?string
    {
        // return absolute URIs and URLs "as is"
        if (empty($fileName) || substr($fileName, 0, 1) == '/' || preg_match('/^https?\:\/\//', $fileName)) {
            return $fileName;
        }

        // handle legacy calls still passing module as second param
        // @todo remove this when all modules are passing correct params
        if ($scope != 'theme' && $scope != 'module' && $scope != 'property' && $scope != 'block') {
            // assume module scope
            $package = $scope;
            $scope = 'module';
        }
        $xar = $this->getParent();

        $paths = [];
        switch ($scope) {
            case 'theme':
                // optional theme images to look in passed as third param
                if (!empty($package)) {
                    $package = xarVarPrep::path($package);
                    $paths[] = $this->getThemeDir($package) . '/images/' . $fileName;
                }
                // current theme images
                $paths[] = $this->getThemeDir() . '/images/' . $fileName;
                // common images
                $paths[] = $this->getThemeDir('common') . '/images/' . $fileName;
                break;
            case 'module':
                if (empty($package)) {
                    $package = $xar->mod()->getName();
                }
                // @checkme: modules is a depency of templates, redundant check?
                if ($xar->mod()->isLoaded()) {
                    $modBaseInfo = $xar->mod()->getBaseInfo($package);
                    if (empty($modBaseInfo)) {
                        return null;
                    }
                    $modOsDir = $modBaseInfo['osdirectory'];
                } else {
                    $modOsDir = xarVarPrep::path($package);
                }
                // handle legacy calls to base module images moved to common/images or themename/images
                // @todo remove this when all modules are passing correct params
                if ($package == 'base') {
                    // current theme images
                    $paths[] = $this->getThemeDir() . '/images/' . $fileName;
                    // common images
                    $paths[] = $this->getThemeDir('common') . '/images/' . $fileName;
                }
                // current theme module images
                $paths[] = $this->getThemeDir() . '/modules/' . $modOsDir . '/images/' . $fileName;
                // common module images
                $paths[] = $this->getThemeDir('common') . '/modules/' . $modOsDir . '/images/' . $fileName;
                // module images (legacy)
                $paths[] = sys::code() . 'modules/' . $modOsDir . '/xarimages/' . $fileName;
                // module images
                $paths[] = sys::code() . 'modules/' . $modOsDir . '/xartemplates/images/' . $fileName;
                break;
            case 'property':
                if (empty($package)) {
                    return null;
                }
                $package = xarVarPrep::path($package);
                // current theme property images
                $paths[] = $this->getThemeDir() . '/properties/' . $package . '/images/' . $fileName;
                // common property images
                $paths[] = $this->getThemeDir('common') . '/properties/' . $package . '/images/' . $fileName;
                // property images (legacy)
                $paths[] = sys::code() . 'properties/' . $package . '/xarimages/' . $fileName;
                // property images
                $paths[] = sys::code() . 'properties/' . $package . '/xartemplates/images/' . $fileName;
                break;
            case 'block':
                if (empty($package)) {
                    return null;
                }
                $package = xarVarPrep::path($package);
                // current theme block images
                $paths[] = $this->getThemeDir() . '/blocks/' . $package . '/images/' . $fileName;
                // common block images
                $paths[] = $this->getThemeDir('common') . '/blocks/' . $package . '/images/' . $fileName;
                // code/blocks/block/xartemplates/style
                $paths[] = sys::code() . 'blocks/' . $package . '/xarimages/' . $fileName;
                break;
        }
        if (empty($paths)) {
            return null;
        }

        $filePath = null;
        foreach ($paths as $path) {
            if (!file_exists($path)) {
                continue;
            }
            $filePath = $path;
            break;
        }
        if (empty($filePath)) {
            return null;
        }

        // Turn relative path into an absolute URL
        $webDir = sys::web();
        if (!empty($webDir) && strpos($filePath, $webDir) === 0) {
            $filePath = substr($filePath, strlen($webDir));
        }
        $filePath = $xar->ctl()->getBaseURL() . $filePath;

        // Return as an XML URL if required.
        // This will generally have little effect, but is here for
        // completeness to support alternative types of URL.
        if ($this->generateXMLURLs) {
            $filePath = htmlspecialchars($filePath);
        }
        return $filePath;
    }

    /**
     * Get theme/module/property/block file with the right file URL
     * @param string $fileName
     * @param ?string $scope
     * @param ?string $package
     * @return string|null
     */
    public function getFile(string $fileName, ?string $scope = null, ?string $package = null): ?string
    {
        // return absolute URIs and URLs "as is"
        if (empty($fileName) || substr($fileName, 0, 1) == '/' || preg_match('/^https?\:\/\//', $fileName)) {
            return $fileName;
        }

        if ($scope != 'theme' && $scope != 'module' && $scope != 'property' && $scope != 'block') {
            return null;
        }
        $xar = $this->getParent();

        $paths = [];
        switch ($scope) {
            case 'theme':
                // optional theme files to look in passed as third param
                if (!empty($package)) {
                    $package = xarVarPrep::path($package);
                    $paths[] = $this->getThemeDir($package) . '/' . $fileName;
                }
                // current theme files
                $paths[] = $this->getThemeDir() . '/' . $fileName;
                // common files
                $paths[] = $this->getThemeDir('common') . '/' . $fileName;
                break;
            case 'module':
                if (empty($package)) {
                    $package = $xar->mod()->getName();
                }
                // @checkme: modules is a depency of templates, redundant check?
                if ($xar->mod()->isLoaded()) {
                    $modBaseInfo = $xar->mod()->getBaseInfo($package);
                    if (empty($modBaseInfo)) {
                        return null;
                    }
                    $modOsDir = $modBaseInfo['osdirectory'];
                } else {
                    $modOsDir = xarVarPrep::path($package);
                }
                // code/modules/{module}/{file}
                $paths[] = sys::code() . 'modules/' . $modOsDir . '/' . $fileName;
                break;
            case 'property':
                if (empty($package)) {
                    return null;
                }
                $package = xarVarPrep::path($package);
                // code/properties/{property}/{file}
                $paths[] = sys::code() . 'properties/' . $package . '/' . $fileName;
                break;
            case 'block':
                if (empty($package)) {
                    return null;
                }
                $package = xarVarPrep::path($package);
                // code/blocks/{block}/{file}
                $paths[] = sys::code() . 'blocks/' . $package . '/' . $fileName;
                break;
        }
        if (empty($paths)) {
            return null;
        }

        $filePath = null;
        foreach ($paths as $path) {
            if (!file_exists($path)) {
                continue;
            }
            $filePath = $path;
            break;
        }
        if (empty($filePath)) {
            return null;
        }

        // Turn relative path into an absolute URL
        $webDir = sys::web();
        if (!empty($webDir) && strpos($filePath, $webDir) === 0) {
            $filePath = substr($filePath, strlen($webDir));
        }
        $filePath = $xar->ctl()->getBaseURL() . $filePath;

        // Return as an XML URL if required.
        // This will generally have little effect, but is here for
        // completeness to support alternative types of URL.
        if ($this->generateXMLURLs) {
            $filePath = htmlspecialchars($filePath);
        }
        return $filePath;
    }

    /**
     * Execute a pre-compiled template string with the supplied template variables
     * @param  string $templateCode pre-compiled template code (see self::compileString)
     * @param array<mixed> $tplData template variables
     * @return string filled-in template
     * @todo   this is not MLS-aware (never was)
     * @todo   how 'special' should the 'memory' file be, namewise?
     */
    public function string(string $templateCode, array $tplData): string
    {
        $xar = $this->getParent();
        // Pretend as if the cache is fully operational and we'll be fine
        xarTemplateCache::saveEntry('memory', $templateCode);

        // Execute the cache file
        $compiled = new CompiledTemplate(xarTemplateCache::cacheFile('memory'), null, 'module', $xar);
        try {
            $caching = $xar->config()->getVar('Site.BL.MemCacheTemplates');
        } catch (Exception $e) {
            $caching = 0;
        }
        $out = $compiled->execute($tplData, $caching);
        return $out;
    }

    /**
     * Execute a specific template file with the supplied template variables
     * @param  string $fileName location of the template file
     * @param array<mixed> $tplData template variables
     * @return string filled-in template
     */
    public function file(string $fileName, array &$tplData): string
    {
        return $this->executeFromFile($fileName, $tplData);
    }

    /**
     * Compile a template string for storage and/or later use in self::string()
     * Note : your module should always support the possibility of re-compiling
     *        template strings e.g. after an upgrade, so you should store both
     *        the original template and the compiled version if necessary
     * @param  string $templateSource template source
     * @return string compiled template
     */
    public function compileString(string $templateSource): string
    {
        $compiler = XarayaCompiler::instance($this->getParent());
        return $compiler->compileString($templateSource);
    }

    /**
     * Render output with pager template
     * @uses xarTplPager::getPager()
     * @param int $startNum
     * @param int $total
     * @param string $urltemplate
     * @param int $itemsPerPage
     * @param int|array<mixed> $blockOptions
     * @param string $template
     * @param string $tplmodule
     * @return string
     */
    public function getPager(int $startNum, int $total, string $urltemplate, int $itemsPerPage = 10, int|array $blockOptions = [], string $template = 'default', string $tplmodule = 'base'): string
    {
        return xarTplPager::getPager($startNum, $total, $urltemplate, $itemsPerPage, $blockOptions, $template, $tplmodule);
    }

    /**
     * Renders a page template.
     * @param  string $mainModuleOutput       the module output
     * @param  ?string $pageTemplate           the page template to use (without extension .xt)
     */
    public function renderPage(string $mainModuleOutput, ?string $pageTemplate = null): string
    {
        if (empty($pageTemplate)) {
            $pageTemplate = $this->getPageTemplateName();
        }
        $context = $this->getContext();
        if (!empty($context) && !empty($context['twig'])) {
            $themeName = $context['theme'] ?? $this->getThemeName();
            if (TwigConfig::isThemeSupported($themeName)) {
                return $this->getTwigTpl()->renderPage($mainModuleOutput, $pageTemplate, $context);
            }
        }

        // get page template source (current > common)
        $sourceFileName = $this->getScopeFileName('theme', $this->getThemeName(), $pageTemplate, null, 'pages');

        $tpl = (object) null; // Create an object to hold the 'specials'
        $tpl->pageTitle = $this->getPageTitle();

        $tplData = [
            'tpl'                      => $tpl,
            '_bl_mainModuleOutput'     => $mainModuleOutput,
            // pass context for xar:blockgroup etc.
            '_bl_context'              => $context,
        ];

        return $this->executeFromFile($sourceFileName, $tplData);
    }

    /**
     * Render a block box
     * @param array<string, mixed> $blockInfo  Information on the block
     * @param  ?string $templateName string
     * @return string self::executeFromFile($sourceFileName, $blockInfo)
     *
     * @todo the search logic for the templates can perhaps use the private function?
     * @todo implement common templates in cascade
     */
    public function renderBlockBox(array $blockInfo, ?string $templateName = null): string
    {
        if (!empty($blockInfo['context']) && !empty($blockInfo['context']['twig'])) {
            $themeName = $blockInfo['context']['theme'] ?? $this->getThemeName();
            if (TwigConfig::isThemeSupported($themeName)) {
                return $this->getTwigTpl()->renderBlockBox($blockInfo, $templateName);
            }
        }
        // look for specific templateName.xt (current > common)
        if (!empty($templateName)) {
            $sourceFileName = $this->getScopeFileName('theme', $this->getThemeName(), $templateName, null, 'blocks');
        }
        // no specific template, fallback to default.xt (current > common)
        if (empty($sourceFileName)) {
            $sourceFileName = $this->getScopeFileName('theme', $this->getThemeName(), 'default', null, 'blocks');
        }
        // no default, fallback to blocks module block.xt (current > common > module)
        if (empty($sourceFileName)) {
            $sourceFileName = $this->getScopeFileName('module', 'blocks', 'block', null, 'blocks');
        }
        // sanity check: shouldn't happen since block.xt is a core module template, but just in case
        if (empty($sourceFileName)) {
            throw new FileNotFoundException(null, 'Could not find block outer template block.xt');
        }

        return $this->executeFromFile($sourceFileName, $blockInfo);
    }

    /**
     * xar:template tag handler
     * Include a subtemplate from wherever
     * @param  string $tplType      scope in which to look for templates [theme|module|block|property]
     * @param  string $package      name of the theme|module|block|property supplying the template
     * @param  string $tplName      The name of the template to use
     * @param array<mixed> $tplData array of data for the template
     * @param  string $tplPart      Optional sub path to look for templates in relative to template path
     * @param  ?string $callerMod    Optional name of the module calling the template, if different from $package
     * @throws FileNotFoundException
     * @return string $this->executeFromFile($sourceFileName, $tplData);
    **/
    public function includeTemplate($tplType, $package, $tplBase, $tplData = [], $tplPart = 'includes', $tplName = null, $callerMod = null): string
    {
        // chris: added this to replicate behaviour of includeModuleTemplate()
        $packages = array_map('trim', explode(',', $package));
        // @checkme: do we really want to fall back on dd in all cases here?
        if ($tplType == 'module' && !in_array('dynamicdata', $packages)) {
            $packages[] = 'dynamicdata';
        }
        foreach ($packages as $tplPkg) {
            if (!$sourceFileName = $this->getScopeFileName($tplType, $tplPkg, $tplBase, $tplName, $tplPart, $callerMod)) {
                continue;
            }
            break;
        }
        if (empty($sourceFileName)) {
            // Not found: raise an exception
            $vars = [$tplType, $tplPart, $tplBase, $package];
            $msg = 'Missing #(1) include template #(2)/#(3) in #(4)';
            throw new FileNotFoundException($vars, $msg);
        }
        return $this->executeFromFile($sourceFileName, $tplData);
    }

    /**
     * Execute template from file
     * @param  string $sourceFileName       From which file do we want to execute? Assume it exists by now ;-)
     * @param array<mixed> $tplData Template variables
     * @param  string $tplType              'module' or 'page'
     * @return string generated output from the file
     * @todo  insert log warning when double entry in cachekeys occurs? (race condition)
     * @todo  make the checking whether templatecode is set more robust (related to templated exception handling)
     */
    public function executeFromFile(string $sourceFileName, array $tplData, string $tplType = 'module'): string
    {
        assert(!empty($sourceFileName));
        assert(is_array($tplData));
        $xar = $this->getParent();

        // cache frequently-used cachedfilenames
        if ($xar->mem()->has('Templates.ExecuteFromFile', $sourceFileName)) {
            $cachedFileName = $xar->mem()->get('Templates.ExecuteFromFile', $sourceFileName);

        } else {
            // Load translations for the template
            $xar->mls()->loadTranslations($sourceFileName);

            $xar->log()->debug("xar::tpl()->executeFromFile: Using template $sourceFileName");
            $templateCode = null;

            // Determine if we need to compile this template
            if (xarTemplateCache::isDirty($sourceFileName)) {
                // Get an instance of SourceTemplate
                $srcTemplate = new XarayaSourceTemplate($sourceFileName, null, 'module', $xar);

                // Compile it
                // @todo return a CompiledTemplate object here?
                $templateCode = $srcTemplate->compile();

                // Save the entry in templatecache (if active)
                xarTemplateCache::saveEntry($sourceFileName, $templateCode);
            }

            // Execute either the compiled template, or the code determined
            // @todo get rid of the cachedFileName usage - why?
            $cachedFileName = xarTemplateCache::cacheFile($sourceFileName);

            $xar->mem()->set('Templates.ExecuteFromFile', $sourceFileName, $cachedFileName);
        }

        // Execute the compiled template from the cache file
        // @todo the tplType should be irrelevant
        $compiled = new CompiledTemplate($cachedFileName, $sourceFileName, $tplType, $xar);
        try {
            $caching = $xar->config()->getVar('Site.BL.MemCacheTemplates');
        } catch (Exception $e) {
            $caching = 0;
        }
        $output = $compiled->execute($tplData, $caching);
        return $output;
    }

    /**
     * Output template
     * @param  string $sourceFileName
     * @param  string $tplOutput
     * @return string generated output from the template
     * @todo Rethink this function, it contains hardcoded xhtml
     */
    public function outputTemplate(string $sourceFileName, string $tplOutput): string
    {
        $xar = $this->getParent();
        // flag used to determine if the header content has been found.
        if (!isset($this->isHeaderContent)) {
            $this->isHeaderContent = false;
        }

        $finalTemplate = '';
        try {
            if ($this->outputTemplateFilenames() && $xar->user()->isLoaded() && ($xar->user()->isDebugAdmin())) {
                $outputStartComment = true;
                if ($this->isHeaderContent === false) {
                    if ($this->isHeaderContent = $this->modifyHeaderContent($sourceFileName, $tplOutput)) {
                        $outputStartComment = false;
                    }
                }
                // optionally show template filenames if start comment has not already
                // been added as part of a header determination.
                if ($outputStartComment === true) {
                    $finalTemplate .= "<!-- start: " . $sourceFileName . " -->\n";
                }
                $finalTemplate .= $tplOutput;
                $finalTemplate .= "<!-- end: " . $sourceFileName . " -->\n";
            } else {
                $finalTemplate .= $tplOutput;
            }
        } catch (Exception $e) {
            $finalTemplate .= $tplOutput;
        }
        return $finalTemplate;
    }

    /**
     * Output php comment block in templates
     * @return int value of self::showPHPCommentBlockInTemplates (0 or 1)
     */
    public function outputPHPCommentBlockInTemplates(): int
    {
        $xar = $this->getParent();
        try {
            // We need to make sure enough of the core is loaded to run this
            $allowed = $xar->user()->isLoaded();
            if ($allowed
                && !isset($this->showPHPCommentBlockInTemplates)
                && $xar->user()->isDebugAdmin()) {
                // Default to not show the comments
                $this->showPHPCommentBlockInTemplates = 0;
                // @checkme: modules is a depency of templates, redundant check?
                if ($xar->mod()->isLoaded()) {
                    $showphpcbit = $xar->mod('themes')->getVar('ShowPHPCommentBlockInTemplates');
                    if (!empty($showphpcbit)) {
                        $this->showPHPCommentBlockInTemplates = 1;
                    }
                } else {
                    $this->showPHPCommentBlockInTemplates = 0;
                }
            } elseif (!isset($this->showPHPCommentBlockInTemplates)) {
                $this->showPHPCommentBlockInTemplates = 0;
            }
        } catch (Exception $e) {
            $this->showPHPCommentBlockInTemplates = 0;
        }
        return $this->showPHPCommentBlockInTemplates;
    }

    /**
     * Output template filenames
     * @return int value of self::showTemplateFilenames (0 or 1)
     * @todo Check whether the check for xar::mod()->getVar is needed
     * @todo Rethink this function
     */
    public function outputTemplateFilenames(): int
    {
        if (!isset($this->showTemplateFilenames)) {
            $xar = $this->getParent();
            // Default to not showing it
            $this->showTemplateFilenames = 0;
            // @checkme: modules is a depency of templates, redundant check?
            if ($xar->mod()->isLoaded()) {
                $showtemplates = $xar->mod('themes')->getVar('ShowTemplates');
                if (!empty($showtemplates)) {
                    $this->showTemplateFilenames = 1;
                }
            }
        }
        return $this->showTemplateFilenames;
    }

    /**
     * Modify header content
     *
     * Attempt to determine if $tplOutput contains header content and if
     * so append a start comment after the first matched header tag
     * found.
     * @param  string $sourceFileName
     * @param  string $tplOutput
     * @return bool found header content
     * @todo it is possible that the first regex <!DOCTYPE[^>].*]> is too
     *       greedy in more complex xml documents and others.
     * @todo The doctype of the output belongs in a template somewhere (probably the xar:blocklayout tag, as an attribute
     */
    protected function modifyHeaderContent(string $sourceFileName, string $tplOutput): bool
    {
        $foundHeaderContent = false;

        // $headerTagsRegexes is an array of string regexes to match tags that could
        // be sent as part of a header. Important: the order here should be inside out
        // as the first regex that matches will have a start comment appended.
        // fixes bugs: #1427, #1190, #603
        // - Comments that precede <!doctype... cause ie6 not to sniff the doctype
        //   correctly.
        // - xml parsers dont like comments that precede xml output.
        // At this time attempting to match <!doctype... and <?xml version... tags.
        // This is about the best we can do now, until we process xar documents with an xml parser and actually 'parse'
        // the document.
        $headerTagRegexes = ['<!DOCTYPE[^>].*]>',// eg. <!DOCTYPE doc [<!ATTLIST e9 attr CDATA "default">]>
            '<!DOCTYPE[^>]*>',// eg. <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
            '<\?xml\s+version[^>]*\?>'];// eg. <?xml version="1.0"? > // remove space between qmark and gt

        foreach ($headerTagRegexes as $headerTagRegex) {
            if (preg_match("/$headerTagRegex/smix", $tplOutput, $matchedHeaderTag)) {
                // FIXME: the next line assumes that we are not in a comment already, no way of knowing that,
                // keep the functionality for now, but dont change more than necessary (see bug #3559)
                // $startComment = '<!-- start(output actually commenced before header(s)): ' . $sourceFileName . ' -->';
                $startComment = '';
                // replace matched tag with an appended start comment tag in the first match
                // in the template output $tplOutput
                $tplOutput = preg_replace("/$headerTagRegex/smix", $matchedHeaderTag[0] . $startComment, $tplOutput, 1);
                // dont want start comment to be sent below as it has already been added.
                $foundHeaderContent = true;
                break;
            }
        }
        return $foundHeaderContent;
    }

    /**
     * Summary of exists
     * @return bool
     */
    protected function exists(string $scope, string $package, string $tplBase, ?string $tplName = null, string $tplPart = ''): bool
    {
        return (bool) $this->getScopeFileName($scope, $package, $tplBase, $tplName, $tplPart);
    }

    /**
     * Determine the template sourcefile to use
     *
     * Based on the scope, the module|property|block|theme name, the basename for the template
     * a possible override and a subpart and the active
     * theme, determine the template source we should use and loads
     * the appropriate translations based on the outcome (see todo).
     *
     * @param  string $scope        scope in which to look for templates [theme|module|block|property]
     * @param  string $package      name of the theme|module|block|property supplying the template
     * @param  string $tplBase      The base name for the template
     * @param  ?string $tplName      The name of the template to use, if any
     * @param  string $tplPart      Optional sub path to look for templates in, default ''
     * @param  ?string $callerMod    Optional name of module calling this package (looks here first if supplied)
     * @return string the path [including sys::code()] to an existing template sourcefile, or empty
     *
     * @todo do we need to load the translations here or a bit later? (here:easy, later: better abstraction)
     */
    protected function getScopeFileName(string $scope, string $package, string $tplBase, ?string $tplName = null, string $tplPart = '', ?string $callerMod = null): string
    {
        $xar = $this->getParent();
        // prep input
        $package = xarVarPrep::path($package);
        $tplBase = xarVarPrep::path($tplBase);
        if (!empty($tplName)) {
            $tplName = xarVarPrep::path($tplName);
        }
        if (!empty($tplPart)) {
            $tplPart = strtr(trim(xarVarPrep::path($tplPart)), " ", "/");
        }
        $canTemplateName = strtr($tplName ?? "", "-", "/");
        $canonical = ($canTemplateName == $tplName) ? false : true;

        $cachename = "$scope:$package:$tplBase:$tplName:$tplPart:$callerMod";
        // cache frequently-used sourcefilenames
        if ($xar->mem()->has('Templates.Element', $cachename)) {
            return $xar->mem()->get('Templates.Element', $cachename);
        }

        // default paths
        $themePath = $this->getThemeDir();
        $commonPath = $this->getThemeDir('common');
        $codePath = sys::code();

        if ($scope == 'theme') {
            // theme scope
            // if package isn't current theme or common theme, look there first
            if ($package != $this->getThemeName() && $package != 'common') {
                $basepaths[] = $this->getThemeDir($package);
            }
            $basepaths[] = $themePath;
            $basepaths[] = $commonPath;
        } else {

            switch ($scope) {
                case 'module':
                    $packages = 'modules';
                    break;
                case 'block':
                    // standalone blocks
                    $packages = 'blocks';
                    break;
                case 'property':
                    // standalone properties
                    $packages = 'properties';
                    break;
                default:
                    $vars = [$scope];
                    $msg = 'Invalid scope "#(1)" for core function xar::tpl()->getScopeFileName()';
                    throw new BadParameterException($vars, $msg);
            }
            if (!empty($callerMod) && $callerMod != $package) {
                if ($scope != 'module') {
                    $basepaths = [
                        "$themePath/modules/$callerMod/$packages/",
                        "$commonPath/modules/$callerMod/$packages/",
                        "{$codePath}modules/$callerMod/xartemplates/$packages/",
                        "$themePath/modules/$callerMod/",
                        "$commonPath/modules/$callerMod/",
                        "{$codePath}modules/$callerMod/xartemplates/",
                        "$themePath/$packages/$package/",
                        "$commonPath/$packages/$package/",
                        "{$codePath}{$packages}/$package/xartemplates/",
                    ];
                } else {
                    $basepaths = [
                        "$themePath/modules/$callerMod/",
                        "$commonPath/modules/$callerMod/",
                        "{$codePath}modules/$callerMod/xartemplates/",
                        "$themePath/$packages/$package/",
                        "$commonPath/$packages/$package/",
                        "{$codePath}{$packages}/$package/xartemplates/",
                    ];
                }
            } else {
                $basepaths = [
                    "$themePath/$packages/$package/",
                    "$commonPath/$packages/$package/",
                    "{$codePath}{$packages}/$package/xartemplates/",
                ];
            }

        }

        $paths = [];
        // approach this the other way, look for tplBase-tplName in all paths first
        if (!empty($tplName)) {
            foreach ($basepaths as $basepath) {
                $paths[] = "$basepath/$tplPart/$tplBase-$tplName.xt";
            }
        }
        // then look for tplBase... (checkme: this is cfr getSourceFileName order)
        foreach ($basepaths as $basepath) {
            $paths[] = "$basepath/$tplPart/$tplBase.xt";
        }
        // then look at canonical... (see checkme)
        if ($canonical) {
            foreach ($basepaths as $basepath) {
                $paths[] = "$basepath/$tplPart/$canTemplateName.xt";
            }
        }

        $debug = 0;
        // Debug display
        if ($debug) {
            foreach ($paths as $path) {
                $path = preg_replace('%\/\/+%', '/', $path);
                echo $xar->mls()->translate('Possible location: ') . $path . "<br/>";
            }
        }

        $sourceFileName = '';
        if (count($paths) > 0) {
            foreach ($paths as $file) {

                if (!file_exists($file)) {
                    continue;
                }

                // Some parts may have been empty, remove extra slashes
                $sourceFileName = preg_replace('%\/\/+%', '/', $file);

                // Debug display
                if ($debug) {
                    echo "<b>" . $xar->mls()->translate('Chosen: ') . $sourceFileName . "</b><br/>";
                }
                break;
            }
        }

        $xar->mem()->set('Templates.Element', $cachename, $sourceFileName);

        return $sourceFileName;
    }
}

/**
 * Access xarTpl::* Templating methods (module, setPageTitle, ...)
 *
 * Available methods:
 * - module() - or use mod()->template() for current module
 * - block()
 * - object() - or use data()->template() for current object
 * - property()
 * - setPageTitle()
 * - setPageTemplateName()
 * - getImage()
 * - getPager()
 * - ...
 *
 * Optional methods in parent:
 * - getModName() for tpl()->setPageTitle()
 *
 */
class TemplatingService implements TemplatingInterface
{
    use TemplatingTrait;

    /**
     * Get name of the module from parent
     */
    public function getModName(): string
    {
        return $this->getParent()->getModName();
    }

    public function __clone()
    {
        // keep current context
    }
}
