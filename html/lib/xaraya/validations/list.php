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
 * Lists Validation Function
 *
 * @throws VariableValidationException
**/
sys::import('xaraya.validations');
class ListValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        if (!is_array($subject)) {
            $msg = 'Not an array';
            throw new VariableValidationException(null, $msg);
        }

        if (isset($parameters[0]) && trim($parameters[0]) != '') {
            $validation = implode(':', $parameters);
            foreach  ($subject as $key => $value) {
                $return = xarVarValidate($validation, $subject[$key]);
                //$return === null or $return === false => return
                if (!$return) {
                    return $return;
                }
            }
        }

        return true;
    }
}
?>
