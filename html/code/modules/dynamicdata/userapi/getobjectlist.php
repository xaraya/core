<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\UserApi;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\UserApi;
use DataObjectDescriptor;
use DataObjectFactory;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata userapi getobjectlist function
 * @extends MethodClass<UserApi>
 */
class GetobjectlistMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get a dynamic object list
     * @author the DynamicData module development team
     * @param array<string,mixed> $args array of optional parameters<br/>
     * with
     *        integer  $args['objectid'] id of the objectlist you're looking for, or<br/>
     *        string   $args['name'] name of the objectlist you're looking for, or<br/>
     *        integer  $args['moduleid'] module id of the objectlist to get +<br/>
     *        string   $args['itemtype'] item type of the objectlist to get
     * @return object a particular DataObjectList
     * @see UserApi::getobjectlist()
     */
    public function __invoke(array $args = [])
    {
        if (empty($args['objectid']) && empty($args['name'])) {
            sys::import('modules.dynamicdata.class.objects.descriptor');
            $args = $this->data()->getObjectID($args);
        }
        sys::import('modules.dynamicdata.class.objects.factory');
        // set context if available in function
        $list = $this->data()->getObjectList($args);
        return $list;
    }
}
