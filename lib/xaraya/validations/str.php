<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
 **/

/**
 * Strings Validation Class
 *
 * @throws VariableValidationException, BadParameterException
**/
sys::import("xaraya.validations");
class StrValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        if (!is_string($subject)) {
            $msg = 'Not a string';
            throw new VariableValidationException(null, $msg);
        }

        $length = strlen($subject);

        if (isset($parameters[0]) && trim($parameters[0]) != '') {
            if (!is_numeric($parameters[0])) {
                // We need a number for the minimum length
                throw new BadParameterException($parameters[0],'The parameter specifying the minimum length of the string should be numeric. It is: "#(1)"');
            } elseif ($length < (int) $parameters[0]) {
                $msg = 'Size of the string "#(1)" is smaller than the specified minimum "#(2)"';
                throw new VariableValidationException(array($subject, $parameters[0]),$msg);
            }
        }

        if (isset($parameters[1]) && trim($parameters[1]) != '') {
            if (!is_numeric($parameters[1])) {
                // We need a number for the maximum length
                throw new BadParameterException($parameters[1],'The parameter specifying the maximum length of the string should be numeric. It is: "#(1)"');
            } elseif ($length > (int) $parameters[1]) {
                $msg = 'Size of the string "#(1)" is larger than the specified maximum "#(2)"';
                throw new VariableValidationException(array($subject, $parameters[1]),$msg);
            }
        }

        $subject = (string) $subject; //Is this useless?
        return true;
    }
}
?>
