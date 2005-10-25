<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/

/**
 * Enum Validation Function
 */
function variable_validations_enum (&$subject, $parameters, $supress_soft_exc, &$name)
{

    $found = false;

    foreach ($parameters as $param) {
        if ($subject == $param) {
            $found = true;
        }
    }

    if ($found) {
        return true;
    } else {
        if ($name != '')
            $msg = xarML('Input "#(1)" was not one of the possibilities for #(2): "', $subject, $name);
        else
            $msg = xarML('Input "#(1)" was not one of the possibilities.', $subject);
        $first = true;
        foreach ($parameters as $param) {
            if ($first) $first = false;
            else $msg .= ' or ';

            $msg .= $param;
        }
        if (!$supress_soft_exc) xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
        return false;
    }
}

?>