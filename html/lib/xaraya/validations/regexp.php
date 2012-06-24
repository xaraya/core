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
 * @link http://www.xaraya.com
*/


/**
 * Regular Expression Validation Class
 *
 * @throws VariableValidationException
**/
sys::import("xaraya.validations");
class RegExpValidation extends ValueValidations
{
    function validate (&$subject, Array $parameters)
    {
        if (!isset($parameters[0]) || trim($parameters[0]) == '') {
            $msg = 'There is no parameter to check agains the regular expression validation.';
            // CHECK: this is probably better a BadParameterException ?
            throw new VariableValidationException(null, $msg);
        } elseif (preg_match($parameters[0], $subject)) {
            return true;
        }

        $msg = '"#(1)" Does not match pattern "#(2)"';
        throw new VariableValidationException(array($subject, $parameters[0]),$msg);
    }
}
?>
