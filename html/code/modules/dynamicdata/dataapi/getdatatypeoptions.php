<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\DataApi;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\DataApi;

/**
 * dynamicdata dataapi getdatatypeoptions function
 * @extends MethodClass<DataApi>
 */
class GetdatatypeoptionsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get datatype options
     * @see DataApi::getdatatypeoptions()
     */
    public function __invoke(array $args = [])
    {
        $options['datatypes'] = [
            1 => "varchar(64)",
            2 => "varchar(254)",
            3 => "tinyint",
            4 => "int",
            5 => "float",
            6 => "text",
        ];

        $options['collations'] = [
            1 => "utf8_general_ci",
            2 => "iso-8859-1",
        ];

        $options['nulls'] = [
            0 => "not null",
            1 => "null",
        ];

        $options['attributes'] = [
            0 => "",
            1 => "unsigned",
        ];

        return $options;
    }
}
