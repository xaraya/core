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
        if ($name == '') $name = '<unknown>';
        $msg = 'Not a checkbox value';
        if (!$supress_soft_exc) throw new VariableValidationException(array($name,$subject,$msg));
        return false;
    }
    return true;
}

?>