<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/

/**
 * Boolean Validation Function
 *
 * @throws VariableValidationException
 */
function variable_validations_bool (&$subject, $parameters=null, $supress_soft_exc, &$name)
{
    // NOTE: can't we use $subject = (boolean) $subject; ?

    //Added the '1' because that is what true is translated for afaik
    if ($subject === true || $subject === 'true' || $subject == '1') {
        $subject = true;
    //Added '' becayse that is what false get translated for...
    } elseif ($subject === false || $subject === 'false' || $subject == '0' || $subject == '') {
        $subject = false;
    } else {
        if ($name == '') $name = '<unknown>';
        $msg = 'Not a boolean';
        if (!$supress_soft_exc) throw new VariableValidationException(array($name,$subject,$msg));
        return false;
    }
    return true;
}

?>
