<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\AdminApi;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\AdminApi;
use DataObjectFactory;

/**
 * dynamicdata adminapi deleteobject function
 * @extends MethodClass<AdminApi>
 */
class DeleteobjectMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * delete a dynamic object and its properties
     * @author the DynamicData module development team
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['objectid'] object id of the object to delete
     * @return int|bool object ID on success, null on failure
     * @see AdminApi::deleteobject()
     */
    public function __invoke(array $args = [])
    {
        $objectid = DataObjectFactory::deleteObject($args);
        return $objectid;
    }
}
