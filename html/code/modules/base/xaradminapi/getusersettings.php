<?php
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Get user settings for admin API
 * 
 * @param array $args Optional function parameters
 * @param string $args['module'] Required module parameter
 * @param integer $args['itemid'] Required item id parameter
 * @return object Data object
 * @throws Exception Thrown is module or itemid have not been provided.
 */
function base_adminapi_getusersettings(Array $args=array())
{
    if (empty($args['module']))
        throw new Exception(xarML('The getusersettings function requires a module parameter'));
    if (!isset($args['itemid'])) // itemid = 0, module vars :)
        throw new Exception(xarML('The getusersettings function requires an itemid parameter'));
    sys::import('modules.dynamicdata.class.objects.master');
    // look for module specific user settings object
    $object = DataObjectMaster::getObject(array('name' => $args['module'] . '_user_settings'));
    // fall back to base module user settings?
    if (!isset($object)) {
        $object = DataObjectMaster::getObject(array('name' => 'user_settings'));
    }
    // shouldn't be necessary here, props should have the correct modvar datastore
    // but since props are easily added set it anyway, just to be sure...
    if (isset($object)) {
        foreach ($object->properties as $name => $property) {
            $object->properties[$name]->source = 'module variables: ' . $args['module'];
        }
        $object->datastores = array();
        $object->getDatastore();
        $object->getItem($args);
    }
    return $object;
}
?>