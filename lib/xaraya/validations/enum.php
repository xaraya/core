<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/

/**
 * Enum Validation Function
 *
 * @throws VariableValidationException
**/
sys::import('xaraya.validations');
class EnumValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
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
            $msg = 'Input given is not in list of valid options';
            $first = true;
            foreach ($parameters as $param) {
                if ($first) $first = false;
                else $msg .= ' or '; // TODO: evaluate MLS consequences later on

                $msg .= $param;
            }
            throw new VariableValidationException(null, $msg);
        }
    }
}
?>
