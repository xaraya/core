<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
*/

/**
 * Checkbox Validation Class
 *
 * @throws VariableValidationException
 */
function variable_validations_checkbox (&$subject, $parameters)
{

    if (empty($subject) || is_null($subject)) {
        $subject = false;
    } elseif (is_string($subject)) {
        $subject = true;
    } else {
        $msg = 'Not a checkbox value';
        throw new VariableValidationException(null,$msg);
    }
    return true;
}

?>
