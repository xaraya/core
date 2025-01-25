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
use sys;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via TemplatingTrait
 */
interface TemplatingInterface extends ServiceInterface
{
    /** @param array<string, mixed> $tplData */
    public function module(string $modName, string $modType, string $funcName, array $tplData = [], ?string $templateName = null): string;

    /** @param array<string, mixed> $tplData */
    public function block(string $modName, string $blockType, array $tplData = [], ?string $tplName = null, ?string $tplBase = null, ?string $tplModule = null): string;

    /** @param array<string, mixed> $tplData */
    public function object(string $modName, string $objectName, string $tplType, array $tplData = []): string;

    /** @param array<string, mixed> $tplData */
    public function property(string $modName, string $propertyName, string $tplType = 'showoutput', array $tplData = [], ?string $tplBase = null): string;

    public function setPageTitle(string $title, ?string $modName = null): bool;

    public function setPageTemplateName(string $templateName): bool;
}

/**
 * Templating available via methods
 */
trait TemplatingTrait
{
    use ServiceTrait;

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
     * Set page title
     * @uses xarTpl::setPageTitle()
     * @param string $title
     * @param ?string $modName
     * @return bool
     */
    public function setPageTitle(string $title, ?string $modName = null): bool
    {
        $modName ??= $this->getModName();
        return xarTpl::setPageTitle($title, $modName);
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
