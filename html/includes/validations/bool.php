<?php

/**
 * File: $Id$
 *
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/

/**
 * Boolean Validation Function
 */
function variable_validations_bool (&$subject, $parameters=null, $supress_soft_exc) 
{

    if ($subject == 'true') {
        $subject = true;
    } elseif ($subject == 'false') {
        $subject = false;
    } else {
        $msg = xarML('Variable "#(1)" is not boolean', $subject);
        if (!$supress_soft_exc) xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
        return false;
    }

    return true;
}

?>