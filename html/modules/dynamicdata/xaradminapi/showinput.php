<?php

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * show some predefined form input field in a template
 *
 * @param $args array containing the definition of the field (type, name, value, ...)
 * @returns string
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_adminapi_showinput($args)
{
    $property = & Dynamic_Property_Master::getProperty($args);
    if (!empty($args['hidden'])) {
        return $property->showHidden($args);
    } else {
        return $property->showInput($args);
    }

    // TODO: input for some common hook/utility modules
}

?>
