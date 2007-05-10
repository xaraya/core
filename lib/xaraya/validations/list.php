<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/


/**
 * Lists Validation Function
 *
 * @throws VariableValidationException
**/
sys::import('xaraya.validations');
class ListValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        if (!is_array($subject)) {
            $msg = 'Not an array';
            throw new VariableValidationException(null, $msg);
        }

        if (isset($parameters[0]) && trim($parameters[0]) != '') {
            $validation = implode(':', $parameters);
            foreach  ($subject as $key => $value) {
                $return = xarVarValidate($validation, $subject[$key]);
                //$return === null or $return === false => return
                if (!$return) {
                    return $return;
                }
            }
        }

        return true;
    }
}
?>
