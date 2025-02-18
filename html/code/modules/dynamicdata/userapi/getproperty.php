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
use DataPropertyMaster;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata userapi getproperty function
 * @deprecated 2.6.2 use $this->prop()->getProperty()
 * @extends MethodClass<UserApi>
 */
class GetpropertyMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get a dynamic property
     * @author the DynamicData module development team
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['type'] type of property (required)<br/>
     * string   $args['name'] name for the property (optional)<br/>
     * string   $args['label'] label for the property (optional)<br/>
     * string   $args['defaultvalue'] default for the property (optional)<br/>
     * string   $args['source'] data source for the property (optional)<br/>
     * string   $args['configuration'] configuration for the property (optional)
     * @return object|null a particular DataProperty
     * @see UserApi::getproperty()
     */
    public function __invoke(array $args = [])
    {
        if (empty($args['type'])) {
            $result = null;
            return $result;
        }
        return $this->prop()->getProperty($args);
    }
}
