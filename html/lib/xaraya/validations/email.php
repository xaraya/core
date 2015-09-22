<?php
/**
 * Short description of purpose of file
 *
 * @package core
 * @subpackage validation
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
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
        if (filter_var($subject, FILTER_VALIDATE_EMAIL) === false) {
            $msg = 'Not a valid email format';
            throw new VariableValidationException(null, $msg);
        }
        return true;
    }
}
?>
