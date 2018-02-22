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
 * Array Validation Function
 *
 * @throws VariableValidationException, BadParameterException
**/
sys::import('xaraya.validations');
class ArrayValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        // If the subject is not array, we can bail out, cos that's what it is all about
        if (!is_array($subject)) {
            $msg = 'Not an array';
            throw new VariableValidationException(null,$msg);
        }

        if (isset($parameters[0]) && trim($parameters[0]) != '') {
            if (!is_numeric($parameters[0])) {
                // We need a number for the minimum nr of elements
                throw new BadParameterException($parameters[0],'The parameter specifying the minimum number of elements should be numeric. It is: "#(1)"');
            } elseif (count($subject) < (int) $parameters[0]) {
                // The subject has too little values
                $msg = 'Array variable has less elements "#(1)" than the specified minimum "#(2)"';
                throw new VariableValidationException(array(count($subject), $parameters[0]), $msg);
            }
        }

        if (isset($parameters[1]) && trim($parameters[1]) != '') {
            if (!is_numeric($parameters[1])) {
                // We need a number for the maximum nr of elements
                throw new BadParameterException($parameters[1],'The parameter specifying the maximum number of elements should be numeric. It is: "#(1)"');
            } elseif (count($subject) > (int) $parameters[1]) {
                // The subject has too many values
                $msg = 'Array variable has more elements "#(1)" than the specified maximum "#(2)"';
                throw new VariableValidationException(array(count($subject), $parameters[1]), $msg);
            }
        }
        return true;
    }
}
?>