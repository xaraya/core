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
 * Checkbox Validation Class
 *
 * @throws VariableValidationException
 */
sys::import('xaraya.validations');
class CheckBoxValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        if (empty($subject) || is_null($subject)) {
            $subject = 0;
        } elseif (is_string($subject)) {
            $subject = 1;
        } else {
            $msg = 'Not a checkbox value';
            throw new VariableValidationException(null,$msg);
        }
        return true;
    }
}
?>