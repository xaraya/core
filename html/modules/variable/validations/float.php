<?php

/**
 * Float Validation Function
 */
function variable_validations_float (&$subject, $parameters) {

        $value = floatval($subject);

        if ("$subject" != "$value") {
            $msg = xarML('Not a Float Type: "#(1)"', $subject);
            xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
            return false;
        }

        if (isset($parameters[0]) && trim($parameters[0]) != '') {
            if (!is_numeric($parameters[0])) {
                $msg = 'Parameter "'.$parameters[0].'" is not a Numeric Type';
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
                return;
            } elseif ($value < (float) $parameters[0]) {
                $msg = xarML('Float Value "#(1)" is smaller than the specified minimum "#(2)"', $value, $parameters[0]);
                xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
                return false;
            }
        }

        if (isset($parameters[1]) && trim($parameters[1]) != '') {
            if (!is_numeric($parameters[1])) {
                $msg = 'Parameter "'.$parameters[1].'" is not a Numeric Type';
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
                return;
            } elseif ($value > (float) $parameters[1]) {
                $msg = xarML('Float Value "#(1)" is bigger than the specified maximum "#(2)"', $value, $parameters[1]);
                xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
                return false;
            }
        }

        $subject = $value;
        return true;
}

?>
