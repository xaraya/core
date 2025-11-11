<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Base\AdminApi;

/**
 * base adminapi get_supported_dbs function
 * @extends MethodClass<AdminApi>
 */
class GetSupportedDbsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Function return the database types give a middleware
     * @param array<string,mixed> $args
     * with $args['database_middleware'] Name of the chosen middleware
     * @return array Returns a dropdown array of the databases supported by the middleware
     * @see AdminApi::getSupportedDbs()
     */
    public function __invoke(array $args = [])
    {
        return AdminApi::getSupportedDbs($args);
    }
}
