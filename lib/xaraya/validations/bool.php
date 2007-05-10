<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
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
        // @todo can't we use $subject = (boolean) $subject; ?

        //Added the '1' because that is what true is translated for afaik
        if ($subject === true || $subject === 'true' || $subject === 1 || $subject === '1') {
            $subject = true;
        //Added '' because that is what false gets translated for...
        } elseif ($subject === false || $subject === 'false' || $subject === 0 || $subject === '0' || $subject === '') {
            $subject = false;
        } else {
            $msg = 'Not a boolean';
            throw new VariableValidationException(null, $msg);
        }
        return true;
    }
}
?>
