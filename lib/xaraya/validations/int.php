<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 */
/**
 * Integer Validation Class
 * @return true on success of validation (value is an integer)
 *
 * @throws VariableValidationException, BadParameterException
 */
function variable_validations_int (&$subject, $parameters)
{
    $value = intval($subject);

    $msg = 'Not an integer';
    if ("$subject" != "$value") {
        throw new VariableValidationException(null, $msg);
    }

    if (isset($parameters[0]) && trim($parameters[0]) != '') {
        if (!is_numeric($parameters[0])) {
            // We need a number for the minimum
            throw new BadParameterException($parameters[0],'The parameter specifying the minimum value should be numeric. It is: "#(1)"');
        } elseif ($value < (int) $parameters[0]) {
            $msg = 'Integer Value "#(1)" is smaller than the specified minimum "#(2)"';
            throw new VariableValidationException(array($value,$parameters[0]),$msg);
        }
    }

    if (isset($parameters[1]) && trim($parameters[1]) != '') {
        if (!is_numeric($parameters[1])) {
            // We need a number for the maximum
            throw new BadParameterException($parameters[1],'The parameter specifying the maximum value should be numeric. It is: "#(1)"');
        } elseif ($value > (int) $parameters[1]) {
            $msg = 'Integer Value "#(1)" is larger than the specified minimum "#(2)"';
            throw new VariableValidationException(array($value,$parameters[1]),$msg);
        }
    }

    $subject = $value; //turn subject into an (int) type if it is not yet.

    return true;
}

?>
