<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/


/**
 * IsSet Validation Function
 */
function variable_validations_isset (&$subject, $parameters, $supress_soft_exc) 
{
    // CHECKME: why does this have no $name parameter?
    if (!isset($subject)) {
        $msg = 'The variable was not set while the validation requires it to be.';
        if (!$supress_soft_exc) 
            throw new VariableValidationException('subject', $msg);
        return false;
    }

    return true;
}

?>
