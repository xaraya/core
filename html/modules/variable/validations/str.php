<?php

/**
 * Strings Validation Class
 */
function variable_validations_str (&$subject, $parameters) {

    if (!is_string($subject)) {
        $msg = xarML('Not a string: "#(1)"', $subject);
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }

    $length = strlen($subject);

    if (isset($parameters[0]) && trim($parameters[0]) != '') {
        if (!is_numeric($parameters[0])) {
            $msg = 'Parameter "'.$parameters[0].'" is not a Numeric Type';
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
            return;
        } elseif ($length < (int) $parameters[0]) {
            $msg = xarML('Size of the string "#(1)" is smaller than the specified minimum "#(2)"', $subject, $parameters[0]);
            xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
            return false;
        }
    }

    if (isset($parameters[1]) && trim($parameters[1]) != '') {
        if (!is_numeric($parameters[1])) {
            $msg = 'Parameter "'.$parameters[1].'" is not a Numeric Type';
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
            return;
        } elseif ($length > (int) $parameters[1]) {
            $msg = xarML('Size of the string "#(1)" is bigger than the specified maximum "#(2)"', $subject, $parameters[1]);
            xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
            return false;
        }
    }

    $subject = (string) $subject; //Is this useless?
    return true;
}

?>
