<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/

/**
 * Checkbox Validation Class
 */
function variable_validations_checkbox (&$subject, $parameters, $supress_soft_exc, &$name)
{

    if (empty($subject) || is_null($subject)) {
        $subject = false;
    } elseif (is_string($subject)) {
        $subject = true;
    } else {
        if ($name != '')
            $msg = xarML('Variable #(1) is not a checkbox: "#(2)"', $name, $subject);
        else
            $msg = xarML('Not a scheckbox: "#(1)"', $subject);
        if (!$supress_soft_exc) xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
        return false;
    }

    return true;
}

?>