<?php

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * return the properties for an item
 *
 * @param $args array containing the items or fields to show
 * @returns array
 * @return array containing a reference to the properties of the item
 */
function dynamicdata_userapi_getitemfordisplay($args)
{
    $args['getobject'] = 1;
    $object = & xarModAPIFunc('dynamicdata','user','getitem',$args);
    if (isset($object)) {
        $properties = & $object->getProperties();
    } else {
        $properties = array();
    }
    return array(& $properties);
}

?>
