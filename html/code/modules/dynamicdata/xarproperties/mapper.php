<?php
/**
 * The Mapper property extends the Queued property by mapping queued values
 * to an itemid of some configurable DataObject and returning some of its
 * properties from cache (batch) - see deferitem etc. (WIP)
 *
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 */

namespace Xaraya\DataObject\Properties;

use ObjectDescriptor;
use Exception;
use sys;

/* Include parent class */
sys::import('modules.dynamicdata.xarproperties.queued');

/**
 * This property displays the queued result of a callable function as value
 * The 'setter', 'getter' and 'batch' callables will be preset for basic *batch operation* if not defined in your property configuration
 *
 * In principle you only need to override or configure the 'batch' method for practical use:
 * ```
 *    /**
 *     * Example of callable 'batch' method = set everything from queue in cache :-)
 *     * Configuration: [$this,"batch"]
 *     * @param array<int, mixed> $values list of values to be resolved (current queue by reference)
 *     * @param array<mixed> $result assoc array of result by value (current cache by reference)
 *     * @param bool $debug show some debug messages or not
 *     * @return int
 *     * /
 *    public function batch(&$values, &$result, $debug = false)
 *    {
 *        if ($debug) {
 *            echo 'Batch method for ' . count($values) . ' values';
 *        }
 *        // basic 'batch' operation = set everything from queue in cache :-)
 *        foreach ($values as $value) {
 *            // set result for value = value here
 *            $result[$value] ??= $value;
 *        }
 *        // clear queue
 *        $values = [];
 *        return count($result);
 *    }
 * ```
 */
class MapperProperty extends QueuedProperty
{
    public $id         = 18272;
    public $name       = 'mapper';
    public $desc       = 'Queued Mapper (test)';
    public $reqmodules = ['dynamicdata'];
    public $options    = [];

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        // Set for runtime - re-use *-callable.xt templates here too for now
    }

    /**
     * Example of callable 'batch' method = set everything from queue in cache :-)
     * Configuration: [$this,"batch"]
     * @param array<int, mixed> $values list of values to be resolved (current queue by reference)
     * @param array<mixed> $result assoc array of result by value (current cache by reference)
     * @param bool $debug show some debug messages or not
     * @return int
     */
    public function batch(&$values, &$result, $debug = false)
    {
        if ($debug) {
            echo 'Batch method for ' . count($values) . ' values';
        }
        // basic 'batch' operation = set everything from queue in cache :-)
        foreach ($values as $value) {
            // set result for value = value here
            $result[$value] ??= $value;
        }
        // clear queue
        $values = [];
        return count($result);
    }
}

/**
 * Example of callable 'mapper' function = set everything from queue in cache :-)
 * Configuration: dynamicdata_callable_mapper or \Xaraya\DataObject\Properties\dynamicdata_callable_mapper
 * @param array<int, mixed> $values list of values to be resolved (current queue by reference)
 * @param array<mixed> $result assoc array of result by value (current cache by reference)
 * @param bool $debug show some debug messages or not
 * @return int
 */
function dynamicdata_callable_mapper(&$values, &$result, $debug = false)
{
    if ($debug) {
        echo 'Batch method for ' . count($values) . ' values';
    }
    // basic 'mapper' operation = set everything from queue in cache :-)
    foreach ($values as $value) {
        // set result for value = value here
        $result[$value] ??= $value;
    }
    // clear queue
    $values = [];
    return count($result);
}
