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
 * IsSet Validation Function
 */
function variable_validations_isset (&$subject, $parameters) {

    if (!isset($subject)) {
        $msg = xarML('Variable not set!');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }

    return true;
}

?>
