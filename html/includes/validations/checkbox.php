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
function variable_validations_checkbox (&$subject, $parameters, $supress_soft_exc) {

    if (empty($subject) || is_null($subject)) {
        $subject = false;
    } elseif (is_string($subject)) {
        $subject = true;
    } else {
        $msg = xarML('Not a checkbox Type: "#(1)"', $subject);
        if (!$supress_soft_exc) xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
        return false;
    }

    return true;
}

?>