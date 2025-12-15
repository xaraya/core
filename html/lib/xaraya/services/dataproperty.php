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

    /** @var ?\DataObject */
    protected $dummyObject = null;

    /**
     * List all defined property types
     * @return array<int, mixed>
     */
    public function getPropertyTypes(): array
    {
        $xar = $this->getServicesClass();
        return DataPropertyMaster::getPropertyTypes($xar);
    }

    /**
     * Get data properties of a data object
     * @param array<string, mixed> $args array with ['objectid' => '...']
     * @return array<string, mixed>
     */
    public function getProperties(array $args = []): array
    {
        $xar = $this->getServicesClass();
        return DataPropertyMaster::getProperties($args, $xar);
    }

    /**
     * Get data property of the right type
     * @param array<string, mixed> $args array with ['type' => '...']
     */
    public function getProperty(array $args = []): DataProperty
    {
        $xar = $this->getServicesClass();
        if (!isset($this->dummyObject)) {
            $property = DataPropertyMaster::getProperty($args, $xar);
            // initialize dummy object with static services class for stand-alone properties
            $this->dummyObject = $property->getDummyObject($xar);
            return $property;
        }
        return DataPropertyMaster::getProperty($args, $xar);
    }

    /**
     * Import DataProperty types into the property_types table
     * @param array<string> $dirs
     * @return array<mixed> an array of the property types currently available
     * @todo flush seems to be unused
     */
    public function importPropertyTypes(bool $flush = true, array $dirs = []): array
    {
        $xar = $this->getServicesClass();
        return PropertyRegistration::importPropertyTypes($flush, $dirs, $xar);
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
