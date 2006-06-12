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
 * @throws BAD_DATA
 */
function variable_validations_isset (&$subject, $parameters, $supress_soft_exc)
{
    if (!isset($subject)) {
        $msg = 'The variable was not set while the validation requires it to be.';
        if (!$supress_soft_exc) 
            throw new VariableValidationException('subject', $msg);
        return false;
    }

    return true;
}

?>
