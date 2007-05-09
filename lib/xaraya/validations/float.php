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
 * Float Validation Function
 *
 * This function will validate the input for it being a float number
 * It will return true when the value validated is a number in the format
                - 1.234
 * @return true on success (value is validated as a float number
 * @throws VariableValidationException, BadParameterException
 */
function variable_validations_float (&$subject, $parameters, &$name)
{
        $value = (float)$subject;
        if ($name == '') $name = '<unknown>';
        if ("$subject" != "$value") {
            $msg = 'Not a float type';
            throw new VariableValidationException(array($name,$subject,$msg));
        }

        if (isset($parameters[0]) && trim($parameters[0]) != '') {
            if (!is_numeric($parameters[0])) {
                // We need a number for the minimum
                throw new BadParameterException($parameters[0],'The parameter specifying the minimum value should be numeric. It is: "#(1)"');
            } elseif ($value < (float) $parameters[0]) {
                $msg = 'Float Value "#(1)" is smaller than the specified minimum "#(2)"';
                throw new VariableValidationException(array($value,$parameters[0]),$msg);
            }
        }

        if (isset($parameters[1]) && trim($parameters[1]) != '') {
            if (!is_numeric($parameters[1])) {
                // We need a number for the maximum
                throw new BadParameterException($parameters[1],'The parameter specifying the maximum value should be numeric. It is: "#(1)"');
            } elseif ($value > (float) $parameters[1]) {
                $msg = 'Float Value "#(1)" is larger than the specified maximum "#(2)"';
                throw new VariableValidationException(array($value,$parameters[0]),$msg);
            }
        }

        $subject = $value;
        return true;
}

?>
