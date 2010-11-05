<?php
/**
 * Short description of purpose of file
 *
 * @package core
 * @subpackage validation
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
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
