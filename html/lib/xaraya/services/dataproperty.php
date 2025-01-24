<?php

/**
 * DataPropertyMaster available via methods (TODO)
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

use DataPropertyMaster;
use DataProperty;
use xarTpl;
use sys;

sys::import('xaraya.services.servicetrait');
sys::import('modules.dynamicdata.class.objects.factory');

/**
 * For documentation purposes only - available via DataPropertyTrait
 */
interface DataPropertyInterface extends ServiceInterface
{
    /**
     * Render output with property template
     * @param array<mixed> $tplData
     */
    public function template(string $tplType, array $tplData = []): string;

    /**
     * List all defined property types
     * @return array<int, mixed>
     */
    public function getPropertyTypes(): array;

    /**
     * Get data properties of a data object
     * @param array<string, mixed> $args array with ['objectid' => '...']
     * @return array<string, mixed>
     */
    public function getProperties(array $args = []): array;

    /**
     * Get data property of the right type
     * @param array<string, mixed> $args with ['type' => '...']
     */
    public function getProperty(array $args = []): DataProperty;
}

/**
 * DataPropertyMaster available via methods
 */
trait DataPropertyTrait
{
    use ServiceTrait;

    /**
     * Render output with property template
     * @uses xarTpl::property()
     * @param string $tplType
     * @param array<mixed> $tplData
     * @param ?string $tplBase
     * @return string
     */
    public function template(string $tplType, array $tplData = [], ?string $tplBase = null): string
    {
        // Add standard template variables (module, itemtype and context)
        // @todo $tplData = $this->prepare($tplData);
        $tplData['context'] ??= $this->getContext();

        $modName = $this->getModName();
        $propertyName = $this->getPropertyTemplate();

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
     * List all defined property types
     * @return array<int, mixed>
     */
    public function getPropertyTypes(): array
    {
        return DataPropertyMaster::getPropertyTypes();
    }

    /**
     * Get data properties of a data object
     * @param array<string, mixed> $args array with ['objectid' => '...']
     * @return array<string, mixed>
     */
    public function getProperties(array $args = []): array
    {
        return DataPropertyMaster::getProperties($args);
    }

    /**
     * Get data property of the right type
     * @param array<string, mixed> $args array with ['type' => '...']
     */
    public function getProperty(array $args = []): DataProperty
    {
        return DataPropertyMaster::getProperty($args);
    }
}

/**
 * Access DataProperty*::* methods with context (getProperty, template, ...)
 *
 * Available methods:
 * - template() for current property - or use tpl()->property() in general with modName propertyName
 * - getPropertyTypes()
 * - getProperties()
 * - getProperty()
 * - ...
 *
 * Required methods in parent:
 * - getPropertyName() for prop()->template()
 *
 * @todo do something with getParent()->getProperty() + simplify methods by name or propid?
 *
 */
class DataPropertyService implements DataPropertyInterface
{
    use DataPropertyTrait;

    /**
     * Get name of the module from parent getProperty()
     */
    public function getModName(): string
    {
        return $this->getParent()->getProperty()?->tplmodule ?? 'dynamicdata';
    }

    /**
     * Get template name of the property from parent getProperty()
     */
    public function getPropertyTemplate(): string
    {
        return $this->getParent()->getProperty()?->template ?? 'base';
    }
}
