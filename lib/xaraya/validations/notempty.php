<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/


/**
 * notempty Validation Function
 *
 * @throws VariableValidationException
 **/
function variable_validations_notempty (&$subject, $parameters, &$name)
{
    if (empty($subject)) {
        if ($name == '') $name = '<unknown>';
        $msg = 'Variable is empty';
        throw new VariableValidationException(array($name,$subject,$msg));
    }
    return true;
}

?>
