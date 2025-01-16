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
use DataObject;
use DataObjectList;
use sys;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via TemplatingTrait
 */
interface TemplatingInterface extends ServiceInterface
{
    /** @param array<string, mixed> $tplData */
    public function module(string $funcName, array $tplData = []): string;

    /** @param array<string, mixed> $tplData */
    public function object(string $tplType, array $tplData = []): string;

    /**
     * Add standard template variables (module, itemtype and context)
     * @param array<string, mixed> $tplData
     * @return array<string, mixed>
     */
    public function prepare(array $tplData = []): array;

    public function setPageTitle(string $title, ?string $modName = null): bool;
}

/**
 * Templating available via methods
 * @template TParent of ServicesInterface
 */
trait TemplatingTrait
{
    /** @use ServiceTrait<TParent> */
    use ServiceTrait;

    /**
     * Render output with module template
     * @uses xarTpl::module()
     * @param string $funcName
     * @param array<string, mixed> $tplData
     * @return string
     */
    public function module(string $funcName, array $tplData = []): string
    {
        // Add standard template variables (module, itemtype and context)
        $tplData = $this->prepare($tplData);

        // See if we have a special template to apply
        $templateName = null;
        if (isset($tplData['_bl_template'])) {
            $templateName = (string) $tplData['_bl_template'];
        }

        // Create the output.
        return xarTpl::module(
            $this->getModName(),
            $this->getModType(),
            $funcName,
            $tplData,
            $templateName
        );
    }

    /**
     * Render output with object template
     * @uses xarTpl::object()
     * @param string $tplType
     * @param array<mixed> $tplData
     * @return string
     */
    public function object(string $tplType, array $tplData = []): string
    {
        // Add standard template variables (module, itemtype and context)
        // @todo $tplData = $this->prepare($tplData);
        $tplData['context'] ??= $this->getContext();

        // Create the output.
        return xarTpl::object(
            $this->getModName(),
            $this->getObject()?->template,
            $tplType,
            $tplData
        );
    }

    /**
     * Add standard template variables (module, itemtype and context)
     * @param array<string, mixed> $tplData
     * @return array<string, mixed>
     */
    public function prepare(array $tplData = []): array
    {
        // Add standard template variables
        $tplData['module'] ??= $this->getModName();
        $tplData['itemtype'] ??= $this->getItemType();
        // Pass along the context for xarTpl::module() if needed
        $tplData['context'] ??= $this->getContext();
        return $tplData;
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
}

/**
 * Access xarTpl::* Templating methods (module, setPageTitle, ...)
 *
 * Available methods:
 * - module()
 * - object()
 * - prepare()
 * - setPageTitle()
 * - ...
 *
 * Required methods in parent:
 * - getModName()
 * - getItemType() for xTpl()->prepare()
 * - getModType() for xTpl()->module()
 * - getObject() for xTpl()->object()
 *
 * @template TParent of ServicesInterface
 */
class TemplatingService implements TemplatingInterface
{
    /** @use TemplatingTrait<TParent> */
    use TemplatingTrait;

    /**
     * Get name of the module from parent
     */
    public function getModName(): string
    {
        return $this->getParent()->getModName();
    }

    /**
     * Get item type from parent
     */
    public function getItemType(): int
    {
        return $this->getParent()->getItemType();
    }

    /**
     * Get module type (user, admin, ...) from parent
     */
    public function getModType(): string
    {
        return $this->getParent()->getModType();
    }

    /**
     * Get data object or objectlist from parent
     */
    public function getObject(): DataObjectList|DataObject|null
    {
        return $this->getParent()->getObject();
    }
}
