<?php

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * show some predefined output field in a template
 *
 * @param $args array containing the definition of the field (type, name, value, ...)
 * @returns string
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_userapi_showoutput($args)
{
    $property = & Dynamic_Property_Master::getProperty($args);
    return $property->showOutput($args['value']);

    // TODO: output from some common hook/utility modules
}

?>
