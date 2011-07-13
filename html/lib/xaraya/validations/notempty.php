<?php
/**
 * Short description of purpose of file
 *
 * @package core
 * @subpackage validation
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
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
