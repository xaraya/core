<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright see the html/credits.html file in this release
*/

/**
 * validate an email address
 *
 *
 * @throws VariableValidationException
**/
sys::import('xaraya.validations');
class EmailValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        if (!mb_eregi('^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,6})$', $subject)) {
            $msg = 'Not a valid email format';
            throw new VariableValidationException(null, $msg);
        }
        return true;
    }
}
?>
