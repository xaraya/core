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

sys::import('xaraya.modules.method');

/**
 * dynamicdata userapi getitemfields function
 * @extends MethodClass<UserApi>
 */
class GetitemfieldsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * utility function to pass item field definitions to whoever
     * @param array<string,mixed> $args array of optional parameters<br/>
     * with
     *        integer  $args['objectid'] object id of the item fields to get, or<br/>
     *        integer  $args['name'] object name of the item fields to get, or<br/>
     *        string   $args['module'] module name of the item fields, or<br/>
     *        integer  $args['moduleid'] module id of the item fields to get +<br/>
     *        string   $args['itemtype'] item type of the item fields to get<br/>
     * @return array containing the item field definitions
     * @see UserApi::getitemfields()
     */
    public function __invoke(array $args = [])
    {
        if (empty($args['objectid']) && empty($args['name'])) {
            $args = $this->data()->getObjectID($args);
        }
        $object = $this->data()->getObject($args);
        if (!is_object($object)) {
            return [];
        }
        $fields = $object->getProperties();
        $itemfields = [];
        foreach ($fields as $name => $prop) {
            $itemfields[$name] = $prop->label;
        }
        return $itemfields;
    }
}
