<?php

/**
 * Handle <xar:data-label ...> label tag
 * Format : <xar:data-label object="$object" /> with $object some Dynamic Object
 *       or <xar:data-label property="$property" /> with $property some Dynamic Property
 *
 * @param $args array containing the object or property
 * @returns string
 * @return the PHP code needed to show the object or property label in the BL template
 */
function dynamicdata_userapi_handleLabelTag($args)
{
    if (!empty($args['object'])) {
        return 'echo xarVarPrepForDisplay('.$args['object'].'->label); ';
    } elseif (!empty($args['property'])) {
        return 'echo xarVarPrepForDisplay('.$args['property'].'->label); ';
    } else {
        return 'echo "I need an object or a property"; ';
    }
}

?>
