<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2002-2009 The Digital Development Foundation
*/

/**
 * Boolean Validation Function
 *
 * @throws VariableValidationException
**/
sys::import('xaraya.validations');
class BoolValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        if ($subject === true || $subject === 'true') {
            $subject = true;
        //Added '' because that is what false gets translated for...
        } elseif ($subject === false || $subject === 'false') {
            $subject = false;
        } else {
            $msg = 'Not a boolean';
            throw new VariableValidationException(null, $msg);
        }
        return true;
    }
}
?>
