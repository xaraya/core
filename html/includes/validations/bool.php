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
function variable_validations_bool (&$subject, $parameters=null) {

    if ($subject == 'true') {
        $subject = true;
    } elseif ($subject == 'false') {
        $subject = false;
    } else {
        $msg = xarML('Variable "#(1)" is not boolean', $subject);
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM', $msg);
        return false;
    }

    return true;
}

?>
