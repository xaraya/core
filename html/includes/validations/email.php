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
 * validate an email address
 *
 */
function variable_validations_email (&$subject, $parameters=null, $supress_soft_exc)
{
    if (!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $subject)) {

        $msg = xarML('Invalid Variable #(1), does not match an e-mail type.', $subject);
        if (!$supress_soft_exc) xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new SystemException($msg));

        return false;
    }

    return true;
}

?>
