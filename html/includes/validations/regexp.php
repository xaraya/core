<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/


/**
 * Regular Expression Validation Class
 */
function variable_validations_regexp (&$subject, $parameters, $supress_soft_exc, &$name)
{

    if (!isset($parameters[0]) || trim($parameters[0]) == '') {
        if ($name != '')
            $msg = xarML('There is no parameter to check against in the Regexp validation of #(1)', $name);
        else
            $msg = xarML('There is no parameter to check against in the Regexp validation');
        xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
        return;
    } elseif (preg_match($parameters[0], $subject)) {
        return true;
    }

    $msg = xarML('Variable #(1): "#(2)" did not match pattern "#(3)"', $name, $subject, $parameters[0]);
    if (!$supress_soft_exc) xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
    return false;
}

?>
