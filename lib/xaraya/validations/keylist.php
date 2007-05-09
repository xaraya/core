<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/


/**
 * List keys Validation Function
 *
 * Usage:
 * keylist:key-validation-rule;[value-validation]
 *
 * - The key validation rule must be terminated by a semi-colon. The
 *   value-validation is optional.
 * - Keys will be validated, but not altered.
 * - Values will be validated and altered as normal.
 *
 * Examples:
 * 1. Validate array keys are ids and values are strings of up to 10 chars:
 *    'keylist:id;str:1:10'
 *    e.g. $x[123] = 'xyz'
 *
 * 2. Any keys allowed for first level, positive ints for second level, and checkbox
 *    validation at the bottom level (box values will be converted to true/false).
 *    'list:keylist:int:0:;checkbox'
 *    e.g. $x['anything'][123] = 'on'
 *
 * 3. ints for two levels, and no validation on the final values:
 *    'keylist:int;keylist:int;'
 *    e.g. $x[123][456] = 'anything'
 *
 * @throws VariableValidationException
**/
sys::import('xaraya.validations');
class KeyListValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        if (!is_array($subject)) {
            $msg = 'Not an array';

            // NULL is a special case. Perform a 'soft' fail should we encounter a NULL
            // @todo What does that mean? Soft fail?
            if ($subject === NULL) {
                return false;
            }
            throw new VariableValidationException(null, $msg);
        }

        if (isset($parameters[0]) && trim($parameters[0]) != '') {
            // Get the remainder of the validation as a string.
            $validation = implode(':', $parameters);

            // The key validation is everything up to the first ';'.
            list($validation_key, $validation_value) = explode(';', $validation, 2);

            foreach  ($subject as $key => $value) {
                // Note: key is a copy, so it will not get updated by the validation routine.
                // That is the behaviour we want: not to start updating key values.
                $return = xarVarValidate($validation_key, $key);

                // The value validation is optional. We may want to just validate the keys
                // and disregard the values.
                if (!empty($validation_value)) {
                    // subject[key] is a reference to the original value, so it can get updated.
                    $return = $return & xarVarValidate($validation_value, $subject[$key]);
                }

                if (!$return) {
                    return $return;
                }
            }
        }

        return true;
    }
}
?>
