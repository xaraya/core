<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2002-2009 The Digital Development Foundation
*/


/**
 * notempty Validation Function
 *
 * @throws VariableValidationException
 * @todo this class is probably too close to issetvalidation
**/
sys::import('xaraya.validations');
class NotEmptyValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        if (empty($subject)) {
            $msg = 'Variable is empty';
            throw new VariableValidationException(null, $msg);
        }
        return true;
    }
}
?>
