<?php

/**
 * Strings Validation Class
 */
function variable_validations_str ($subject, $parameters, &$convValue) {

    if (!is_string($subject)) {
        return false;
    }

    $length = strlen($subject);

    if (isset($parameters[0]) && trim($parameters[0]) != '') {
        if (!is_numeric($parameters[0])) {
            $msg = 'Parameter "'.$parameters[0].'" is not a Numeric Type';
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                new SystemException($msg));
            return;
        } elseif ($length < (int) $parameters[0]) {
            return false;
        }
    }

    if (isset($parameters[1]) && trim($parameters[1]) != '') {
        if (!is_numeric($parameters[1])) {
            $msg = 'Parameter "'.$parameters[1].'" is not a Numeric Type';
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                new SystemException($msg));
            return;
        } elseif ($length > (int) $parameters[1]) {
            return false;
        }
    }

    $convValue = (string) $subject;
    return true;
}

?>
