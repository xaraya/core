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
 * Regular Expression Validation Class
 */
function variable_validations_regexp (&$subject, $parameters, $supress_soft_exc) 
{

    if (!isset($parameters[0]) || trim($parameters[0]) == '') {
        $msg = 'There is no parameter to check against in Regexp validation';
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
        return;
    } elseif (preg_match($parameters[0], $subject)) {
        return true;
    }

    $msg = xarML('Variable "#(1)" did not match pattern "#(2)"', $subject, $parameters[0]);
    if (!$supress_soft_exc) xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
    return false;
}

?>