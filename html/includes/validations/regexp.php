<?php

/**
 * Regular Expression Validation Class
 */
function variable_validations_regexp (&$subject, $parameters) {

    if (!isset($parameters[0]) || trim($parameters[0]) == '') {
        $msg = 'There is no parameter to check against in Regexp validation';
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    } elseif (preg_match($parameters[0], $subject)) {
        return true;
    }

    $msg = xarML('Variable "#(1)" didnt match pattern "#(2)"', $subject, $parameters[0]);
    xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
    return false;
}

?>
