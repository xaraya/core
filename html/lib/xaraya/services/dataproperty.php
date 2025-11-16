<?php

/**
 * DataPropertyMaster available via methods (TODO)
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

use DataPropertyMaster;
use DataProperty;
use PropertyRegistration;

/**
 * For documentation purposes only - available via DataPropertyTrait
 */
interface DataPropertyInterface extends ServiceInterface
{
    public const SLICE = 'dataproperty';

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

    /**
     * Import DataProperty types into the property_types table
     * @param array<string> $dirs
     * @return array<mixed> an array of the property types currently available
     */
    public function importPropertyTypes(bool $flush = true, array $dirs = []): array;
}

/**
 * DataPropertyMaster available via methods
 */
trait DataPropertyTrait
{
    use ServiceTrait;

    /**
     * List all defined property types
     * @return array<int, mixed>
     */
    public function getPropertyTypes(): array
    {
        return DataPropertyMaster::getPropertyTypes($this->getParent());
    }

    /**
     * Get data properties of a data object
     * @param array<string, mixed> $args array with ['objectid' => '...']
     * @return array<string, mixed>
     */
    public function getProperties(array $args = []): array
    {
        return DataPropertyMaster::getProperties($args, $this->getParent());
    }

    /**
     * Get data property of the right type
     * @param array<string, mixed> $args array with ['type' => '...']
     */
    public function getProperty(array $args = []): DataProperty
    {
        return DataPropertyMaster::getProperty($args, $this->getParent());
    }

    /**
     * Import DataProperty types into the property_types table
     * @param array<string> $dirs
     * @return array<mixed> an array of the property types currently available
     * @todo flush seems to be unused
     */
    public function importPropertyTypes(bool $flush = true, array $dirs = []): array
    {
        return PropertyRegistration::importPropertyTypes($flush, $dirs, $this->getParent());
    }
}

/**
 * Access DataProperty*::* methods with context (getProperty, getPropertyTypes, ...)
 *
 * Available methods:
 * - getPropertyTypes()
 * - getProperties()
 * - getProperty()
 * - ...
 *
 */
class DataPropertyService implements DataPropertyInterface
{
    use DataPropertyTrait;
}
