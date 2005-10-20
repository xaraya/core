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

    if (!isset($subject)) {
        $msg = xarML('Variable not set!');
        if (!$supress_soft_exc) xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
        return false;
    }

    return true;
}

?>
