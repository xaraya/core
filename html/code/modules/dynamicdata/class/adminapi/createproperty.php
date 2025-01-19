<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\DataObject\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\DataObject\AdminApi;
use BadParameterException;
use DataObjectFactory;
use xarMod;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata adminapi createproperty function
 * @extends MethodClass<AdminApi>
 */
class CreatepropertyMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * create a new property field for an object
     * @author the DynamicData module development team
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['name'] name of the property to create<br/>
     * string   $args['label'] label of the property to create<br/>
     * integer  $args['objectid'] object id of the property to create<br/>
     * integer  $args['moduleid'] module id of the property to create<br/>
     * string   $args['itemtype'] item type of the property to create<br/>
     * string   $args['type'] type of the property to create<br/>
     * string   $args['defaultvalue'] default of the property to create<br/>
     * string   $args['source'] data source for the property (dynamic_data table or other)<br/>
     * string   $args['status'] status of the property to create (disabled/active/...)<br/>
     * integer  $args['seq'] order of the property to create<br/>
     * string   $args['configuration'] configuration of the property to create
     * @return int property ID on success, null on failure
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        // Required arguments
        $invalid = [];
        if (!isset($name) || !is_string($name)) {
            $invalid[] = 'name';
        }
        if (!isset($type) || !is_numeric($type)) {
            $invalid[] = 'type';
        }
        if (count($invalid) > 0) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = [join(', ', $invalid), 'admin', 'createproperty', 'DynamicData'];
            throw new BadParameterException($vars, $msg);
        }

        if (empty($moduleid)) {
            // defaults to the current module
            $moduleid = xarMod::getRegID(xarMod::getName());
        }
        if (empty($itemtype)) {
            $itemtype = 0;
        }
        $itemid = 0;

        // TODO: security check on object level

        // get the properties of the 'properties' object
        $fields = xarMod::apiFunc(
            'dynamicdata',
            'user',
            'getprop',
            ['objectid' => 2]
        ); // the properties

        $values = [];
        // the acceptable arguments correspond to the property names !
        foreach ($fields as $name => $field) {
            if (isset($args[$name])) {
                $values[$name] = $args[$name];
            }
        }

        sys::import('modules.dynamicdata.class.objects.factory');
        $propertyobject = DataObjectFactory::getObject(['name' => 'properties']);
        $propid = $propertyobject->createItem($values);
        return $propid;
    }
}
