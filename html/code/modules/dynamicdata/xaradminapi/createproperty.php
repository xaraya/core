<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * create a new property field for an object
 *
 * @author the DynamicData module development team
 * @param array    $args array of optional parameters<br/>
 *        string   $args['name'] name of the property to create<br/>
 *        string   $args['label'] label of the property to create<br/>
 *        integer  $args['objectid'] object id of the property to create<br/>
 *        integer  $args['moduleid'] module id of the property to create<br/>
 *        string   $args['itemtype'] item type of the property to create<br/>
 *        string   $args['type'] type of the property to create<br/>
 *        string   $args['defaultvalue'] default of the property to create<br/>
 *        string   $args['source'] data source for the property (dynamic_data table or other)<br/>
 *        string   $args['status'] status of the property to create (disabled/active/...)<br/>
 *        integer  $args['seq'] order of the property to create<br/>
 *        string   $args['configuration'] configuration of the property to create
 * @return integer property ID on success, null on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_createproperty(Array $args=array())
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

    sys::import('modules.dynamicdata.class.objects.master');
    $propertyobject = DataObjectMaster::getObject(array('name' => 'properties'));
    $propid = $propertyobject->createItem($values);
    return $propid;
}
?>
