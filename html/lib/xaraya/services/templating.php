<?php

/**
 * Templating available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use xarTpl;
use xarTplPager;
use sys;

sys::import('xaraya.services.servicetrait');

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

    public function getBaseDir(): string;

    public function getThemeDir(?string $theme = null): string;

    public function getThemeName(): string;

    public function setThemeName(string $themeName): bool;

    public function getThemeUrl(?string $theme = null): string;

    public function getCodeUrl(): string;

    public function getImage(string $fileName, ?string $scope = null, ?string $package = null): ?string;

    public function getFile(string $fileName, ?string $scope = null, ?string $package = null): ?string;

    /** @param int|array<mixed> $blockOptions */
    public function getPager(int $startNum, int $total, string $urltemplate, int $itemsPerPage = 10, int|array $blockOptions = [], string $template = 'default', string $tplmodule = 'base'): string;
}

/**
 * Templating available via methods
 */
trait TemplatingTrait
{
    use ServiceTrait;

    /**
     * Initialize service class
     * @param array<string, mixed> $args
     */
    public function init(array $args = []): bool
    {
        if (empty($args)) {
            $args = $this->getConfig();
        }
        $this->getContext()[static::SLICE] ??= $args;
        return true;
    }

    /**
     * Get configuration
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return [];
    }

    /**
     * Render output with module template
     * @uses xarTpl::module()
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

        // Create the output.
        return xarTpl::module(
            $modName,
            $modType,
            $funcName,
            $tplData,
            $templateName
        );
    }

    /**
     * Render output with object template
     * @uses xarTpl::block()
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

        // Create the output.
        return xarTpl::block(
            $modName,
            $blockType,
            $tplData,
            $tplName,
            $tplBase,
            $tplModule
        );
    }

    /**
     * Render output with object template
     * @uses xarTpl::object()
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

        // Create the output.
        return xarTpl::object(
            $modName,
            $objectName,
            $tplType,
            $tplData
        );
    }

    /**
     * Render output with property template
     * @uses xarTpl::property()
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

        // Create the output.
        return xarTpl::property(
            $modName,
            $propertyName,
            $tplType,
            $tplData,
            $tplBase
        );
    }

    /**
     * Get page title
     */
    public function getPageTitle(): string
    {
        // Get the page title from the current context
        // @todo remove fallback to xarTpl once fully migrated
        return $this->getContext()?->getSliceValue(static::SLICE, 'pageTitle') ?? xarTpl::getPageTitle();
    }

    /**
     * Set page title
     * @uses xarTpl::setPageTitle()
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
        // @todo see logic in xarTpl::setPageTitle()
        xarTpl::setPageTitle($title, $modName);
        $this->getContext()?->setSliceValue(static::SLICE, 'pageTitle', xarTpl::getPageTitle());
        return true;
    }

    /**
     * Get page template name
     */
    public function getPageTemplateName(): string
    {
        return xarTpl::getPageTemplateName();
    }

    /**
     * Set page template name
     * @uses xarTpl::setPageTemplateName()
     * @param  string $templateName Name of the page template
     * @return bool
     */
    public function setPageTemplateName(string $templateName): bool
    {
        return xarTpl::setPageTemplateName($templateName);
    }

    /**
     * Get base directory
     */
    public function getBaseDir(): string
    {
        return xarTpl::getBaseDir();
    }

    /**
     * Get theme directory
     */
    public function getThemeDir(?string $theme = null): string
    {
        return xarTpl::getThemeDir($theme);
    }

    /**
     * Get theme name in use
     */
    public function getThemeName(): string
    {
        return xarTpl::getThemeName();
    }

    /**
     * Set theme name
     */
    public function setThemeName(string $themeName): bool
    {
        return xarTpl::setThemeName($themeName);
    }

    /**
     * Get theme URL
     */
    public function getThemeUrl(?string $theme = null): string
    {
        return xarTpl::getThemeUrl($theme);
    }

    /**
     * Get code URL
     */
    public function getCodeUrl(): string
    {
        return xarTpl::getCodeUrl();
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
        return xarTpl::getImage($fileName, $scope, $package);
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
        return xarTpl::getFile($fileName, $scope, $package);
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
}
