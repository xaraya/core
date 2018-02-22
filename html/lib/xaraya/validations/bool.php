<?php
/**
 * Short description of purpose of file
 *
 * @package core\validation
 * @subpackage validation
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
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