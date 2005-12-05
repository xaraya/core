<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/

/**
 * Float Validation Function
 */
function variable_validations_float (&$subject, $parameters, $supress_soft_exc, &$name)
{
        $value = (float)$subject;
        if ($name == '') $name = '<unknown>';
        if ("$subject" != "$value") {
            $msg = 'Not a float type';
            if (!$supress_soft_exc) 
                throw new VariableValidationException(array($name,$subject,$msg));
            return false;
        }

        if (isset($parameters[0]) && trim($parameters[0]) != '') {
            if (!is_numeric($parameters[0])) {
                // We need a number for the minimum
                throw new BadParameterException($parameters[0],'The parameter specifying the minimum value should be numeric. It is: "#(1)"');
            } elseif ($value < (float) $parameters[0]) {
                $msg = 'Float Value "#(1)" is smaller than the specified minimum "#(2)"';
                if (!$supress_soft_exc) 
                    throw new VariableValidationException(array($value,$parameters[0]),$msg);
                return false;
            }
        }

        if (isset($parameters[1]) && trim($parameters[1]) != '') {
            if (!is_numeric($parameters[1])) {
                // We need a number for the maximum
                throw new BadParameterException($parameters[1],'The parameter specifying the maximum value should be numeric. It is: "#(1)"');
            } elseif ($value > (float) $parameters[1]) {
                $msg = 'Float Value "#(1)" is larger than the specified maximum "#(2)"';
                if (!$supress_soft_exc) 
                    throw new VariableValidationException(array($value,$parameters[0]),$msg);
                return false;
            }
        }

        $subject = $value;
        return true;
}

?>
