<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/

/**
 * Strings Validation Class
 */
function variable_validations_str (&$subject, $parameters, $supress_soft_exc, &$name)
{
    if ($name == '') $name = '<unknown>';
    if (!is_string($subject)) {
        $msg = 'Not a string';
        if (!$supress_soft_exc) 
            throw new VariableValidationException(array($name,$subject,$msg));
        return false;
    }

    $length = strlen($subject);

    if (isset($parameters[0]) && trim($parameters[0]) != '') {
        if (!is_numeric($parameters[0])) {
            // We need a number for the minimum length
            throw new BadParameterException($parameters[0],'The parameter specifying the minimum length of the string should be numeric. It is: "#(1)"');
        } elseif ($length < (int) $parameters[0]) {
            $msg = 'Size of the string "#(1)" is smaller than the specified minimum "#(2)"';
            if (!$supress_soft_exc)
                throw new VariableValidationException(array($subject, $parameters[0]),$msg);
            return false;
        }
    }

    if (isset($parameters[1]) && trim($parameters[1]) != '') {
        if (!is_numeric($parameters[1])) {
            // We need a number for the maximum length
            throw new BadParameterException($parameters[1],'The parameter specifying the maximum length of the string should be numeric. It is: "#(1)"');
        } elseif ($length > (int) $parameters[1]) {
            $msg = 'Size of the string "#(1)" is larger than the specified maximum "#(2)"';
            if (!$supress_soft_exc)
                throw new VariableValidationException(array($subject, $parameters[1]),$msg);
            return false;
        }
    }

    $subject = (string) $subject; //Is this useless?
    return true;
}

?>
