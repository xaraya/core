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
 * Checkbox Validation Class
 */
function variable_validations_checkbox (&$subject, $parameters) {

    if (is_string($subject)) {
        $subject = true;
    } elseif (empty($subject) || is_null($subject)) {
        $subject = false;
    } else {
        $msg = xarML('Not a checkbox Type: "#(1)"', $subject);
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }

    return true;
}

?>
