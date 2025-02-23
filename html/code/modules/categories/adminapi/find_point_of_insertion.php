<?php

/**
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Categories\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Categories\AdminApi;
use BadParameterException;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories adminapi find_point_of_insertion function
 * @extends MethodClass<AdminApi>
 */
class FindPointOfInsertionMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Find the correct point of insertion for a node in Celko�s model for
     * hierarchical SQL Trees.
     * @param array $args
     * with
     *     string $args['inorout'] Where the new category should be: IN or OUT
     *     string $args['rightorleft'] Where the new category should be: RIGHT or LEFT
     *     int $args['right'] The right value of the reference category
     *     int $args['left'] The left value of the reference category
     * @return int Returns the point of insertion value
     * @throws \BadParameterException Thrown if parameters contain invalid values
     * @see AdminApi::findPointOfInsertion()
     */
    public function __invoke(array $args = [])
    {

        extract($args);

        // Switch chosen over ifs for easiness of comprehession of the code
        $rightorleft = strtolower($rightorleft);
        $inorout = strtolower($inorout);

        switch ($rightorleft) {
            case "right":
                $point_of_insertion = $right;

                switch ($inorout) {
                    case "out":
                        $point_of_insertion++;
                        break;

                    case "in":
                        break;

                    default:
                        $msg = $this->ml('Valid values: IN or OUT');
                        throw new BadParameterException(null, $msg);
                }

                break;
            case "left":
                $point_of_insertion = $left;
                switch ($inorout) {
                    case "out":
                        break;

                    case "in":
                        $point_of_insertion++;
                        break;

                    default:
                        $msg = $this->ml('Valid values: IN or OUT');
                        throw new BadParameterException(null, $msg);
                }
                break;
            default:
                $msg = $this->ml('Valid values: RIGHT or LEFT');
                throw new BadParameterException(null, $msg);
        }
        return $point_of_insertion;
    }
}
