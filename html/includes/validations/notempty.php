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
 * notempty Validation Function
 */
function variable_validations_notempty (&$subject, $parameters, $supress_soft_exc) {

    if (empty($subject)) {
        $msg = xarML('Variable "#(1)" should not be empty', $subject);
        if (!$supress_soft_exc) xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }

    return true;
}

?>
