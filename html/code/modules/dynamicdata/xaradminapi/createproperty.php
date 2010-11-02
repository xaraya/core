<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * create a new property field for an object
 *
 * @author the DynamicData module development team
 * @param $args['name'] name of the property to create
 * @param $args['label'] label of the property to create
 * @param $args['objectid'] object id of the property to create
 * @param $args['moduleid'] module id of the property to create
 * @param $args['itemtype'] item type of the property to create
 * @param $args['type'] type of the property to create
 * @param $args['defaultvalue'] default of the property to create
 * @param $args['source'] data source for the property (dynamic_data table or other)
 * @param $args['status'] status of the property to create (disabled/active/...)
 * @param $args['seq'] order of the property to create
 * @param $args['configuration'] configuration of the property to create
 * @return int property ID on success, null on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_createproperty($args)
{
    extract($args);

    // Required arguments
    $invalid = array();
    if (!isset($name) || !is_string($name)) {
        $invalid[] = 'name';
    }
    if (!isset($type) || !is_numeric($type)) {
        $invalid[] = 'type';
    }
    if (count($invalid) > 0) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array(join(', ',$invalid), 'admin', 'createproperty', 'DynamicData');
        throw new BadParameterException($vars,$msg);
    }

    if (empty($moduleid)) {
        // defaults to the current module
        $moduleid = xarMod::getRegID(xarModGetName());
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }
    $itemid = 0;

    // TODO: security check on object level

    // get the properties of the 'properties' object
    $fields = xarMod::apiFunc('dynamicdata','user','getprop',
                            array('objectid' => 2)); // the properties

    $values = array();
    // the acceptable arguments correspond to the property names !
    foreach ($fields as $name => $field) {
        if (isset($args[$name])) {
            $values[$name] = $args[$name];
        }
    }

    $propid = xarMod::apiFunc('dynamicdata', 'admin', 'create',
                            array('module_id'    => xarMod::getRegID('dynamicdata'), 
                                  'itemtype' => 1,
                                  'itemid'   => $itemid,
                                  'values'   => $values));
    if (!isset($propid)) return;
    return $propid;
}
?>
