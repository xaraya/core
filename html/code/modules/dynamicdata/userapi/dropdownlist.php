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
use BadParameterException;
use DataObjectList;
use DataProperty;
use EmptyParameterException;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata userapi dropdownlist function
 * @extends MethodClass<UserApi>
 */
class DropdownlistMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get an array of DD items (itemid => fieldvalue) for use in dropdown lists
     * E.g. to specify the parent of an item for parent-child relationships,
     * add a dynamic data field of type Dropdown List with the configuration rule
     * $userapi->dropdownlist(array('field' => 'name','module' => 'dynamicdata','itemtype' => 2))
     *
     * Note : for additional optional parameters, see the getitems() function
     * @author the DynamicData module development team
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['field'] field to use in the dropdown list (required here)<br/>
     * boolean  $args['showoutput'] go through showOutput() for this field (default false)<br/>
     * string   $args['module'] module name of the item fields to get, or<br/>
     * integer  $args['module_id'] module id of the item fields to get +<br/>
     * string   $args['itemtype'] item type of the item fields to get, or<br/>
     * string   $args['table'] database table to turn into an object
     * @return array|void of (itemid => fieldvalue), or false on failure
     * @throws \EmptyParameterException
     * @see UserApi::dropdownlist()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        if (empty($args['field'])) {
            throw new EmptyParameterException('field');
        }


        // put the field in the required fieldlist
        $args['fieldlist'] = [$args['field']];

        // get back the object
        $args['getobject'] = 1;

        /** @var DataObjectList|null $object */
        $object = $userapi->getitems($args);
        if (!isset($object)) {
            return;
        }

        $field = $args['field'];
        if (!isset($object->properties[$field])) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = ['property', 'user', 'dropdownlist', 'DynamicData'];
            throw new BadParameterException($vars, $msg);
        }

        // Fill in the dropdown list
        $list = [];
        $list[0] = '';
        foreach ($object->items as $itemid => $item) {
            if (!isset($item[$field])) {
                continue;
            }
            if (!empty($args['showoutput'])) {
                /** @var DataProperty $property */
                $property = $object->properties[$field];
                $value = $property->showOutput(['value' => $item[$field]]);
                if (isset($value)) {
                    $list[$itemid] = $value;
                }
            } else {
                $list[$itemid] = $this->var()->prep($item[$field]);
            }
        }
        return $list;
    }
}
