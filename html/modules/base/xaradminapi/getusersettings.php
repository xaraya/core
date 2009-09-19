<?php
function base_adminapi_getusersettings($args)
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
        $object->getDatastores();
        $object->getItem($args);
    }
    return $object;
}
?>