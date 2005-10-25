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
    if (!eregi('^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,6})$', $subject)) {
        if ($name != '')
            $msg = xarML('Variable #(1) does not match an e-mail type: "#(2)"', $name, $subject);
        else
            $msg = xarML('Not an e-mail type: "#(1)"', $subject);
        if (!$supress_soft_exc) xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));

        return false;
    }

    return true;
}

?>
