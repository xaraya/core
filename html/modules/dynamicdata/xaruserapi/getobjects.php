<?php

/**
 * get the list of defined dynamic objects
 *
 * @author the DynamicData module development team
 * @returns array
 * @return array of object definitions
 * @raise DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getobjects($args = array())
{
    return Dynamic_Object_Master::getObjects();
}

?>