<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/

/**
 * validate an email address
 *
 */
function variable_validations_email (&$subject, $parameters=null, $supress_soft_exc, &$name)
{
    if($name == '') $name = '<unknown>';
    if (!eregi('^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,6})$', $subject)) {
        $msg = 'Not a valid email format';
        if (!$supress_soft_exc) 
            throw new VariableValidationException(array($name,$subject,$msg));
        return false;
    }

    return true;
}

?>
