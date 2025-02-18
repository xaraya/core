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
use DataObjectFactory;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata userapi getobjects function
 * @extends MethodClass<UserApi>
 */
class GetobjectsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get the list of defined dynamic objects
     * @author the DynamicData module development team
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @return array of object definitions
     * @see UserApi::getobjects()
     */
    public function __invoke(array $args = [])
    {
        $objects =  $this->data()->getObjects($args);
        return $objects;
    }
}
