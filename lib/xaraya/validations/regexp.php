<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/


/**
 * Regular Expression Validation Class
 *
 * @throws VariableValidationException
**/
sys::import("xaraya.validations");
class RegExpValidation extends ValueValidations
{
    function validate (&$subject, Array $parameters)
    {
        if (!isset($parameters[0]) || trim($parameters[0]) == '') {
            $msg = 'There is no parameter to check agains the regular expression validation.';
            // CHECK: this is probably better a BadParameterException ?
            throw new VariableValidationException(null, $msg);
        } elseif (preg_match($parameters[0], $subject)) {
            return true;
        }

        $msg = '"#(1)" Does not match pattern "#(2)"';
        throw new VariableValidationException(array($subject, $parameters[0]),$msg);
    }
}
?>
