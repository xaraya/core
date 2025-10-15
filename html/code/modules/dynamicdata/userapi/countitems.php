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
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata userapi countitems function
 * @extends MethodClass<UserApi>
 */
class CountitemsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * utility function to count the number of items held by this module
     * @author the DynamicData module development team
     * @param array<string,mixed> $args array of optional parameters<br/>
     * with
     *        integer  $args['objectid'] id of the objectlist you're looking for, or<br/>
     *        string   $args['name'] name of the objectlist you're looking for, or<br/>
     *        integer  $args['moduleid'] module id of the objectlist to get +<br/>
     *        string   $args['itemtype'] item type of the objectlist to get
     * @return int|void number of items held by this module
     * @see UserApi::countitems()
     */
    public function __invoke(array $args = [])
    {
        if (empty($args['objectid']) && empty($args['name'])) {
            $args = $this->data()->getObjectID($args);
        }
        $mylist = $this->data()->getObjectList($args);
        if (!isset($mylist)) {
            return;
        }

        return $mylist->countItems();
    }
}
