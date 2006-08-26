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
function variable_validations_notempty (&$subject, $parameters, $supress_soft_exc, &$name)
{
    if (empty($subject)) {
        if ($name == '') $name = '<unknown>';
        $msg = 'Variable is empty';
        if (!$supress_soft_exc) 
            throw new VariableValidationException(array($name,$subject,$msg));
        return false;
    }

    return true;
}

?>
