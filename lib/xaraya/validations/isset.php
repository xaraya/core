<?php
/**
 * IsSet Validation Function
 *
 * @package validation
 * @copyright (C) 2002-2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 */
/**
 * This function checks for the 'isset' status of a parameter
 *
 * It will return true when the parameter isset, false on !isset.
 * When not set, the function will also throw the BAD_PARAM exception
 * @param bool supress_soft_exc
 * @param parameters
 * @param subject The parameter to check for
 * @return bool true on isset, false on !isset
 * @throws VariableValidationException
**/
sys::import('xaraya.validations');
class IssetValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters )
    {
        if (!isset($subject)) {
            $msg = 'The variable was not set while the validation requires it to be.';
            throw new VariableValidationException('subject', $msg);
        }
        return true;
    }
}
?>
