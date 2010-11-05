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
 * Enum Validation Function
 *
 * @throws VariableValidationException
**/
sys::import('xaraya.validations');
class EnumValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        $found = false;

        foreach ($parameters as $param) {
            if ($subject == $param) {
                $found = true;
            }
        }

        if ($found) {
            return true;
        } else {
            $msg = 'Input given is not in list of valid options';
            $first = true;
            foreach ($parameters as $param) {
                if ($first) $first = false;
                else $msg .= ' or '; // TODO: evaluate MLS consequences later on

                $msg .= $param;
            }
            throw new VariableValidationException(null, $msg);
        }
    }
}
?>
