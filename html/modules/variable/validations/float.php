<?php

/**
 * Float Validation Function
 */
function variable_validations_float {$subject, $parameters, &$convValue) {

        $value = floatval($subject);

        if ("$subject" != "$value") {
            return false;
        }

        if (isset($parameters[0]) && trim($parameters[0]) != '') {
            if (!is_numeric($parameters[0])) {
                $msg = 'Parameter "'.$parameters[0].'" is not a Numeric Type';
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                new SystemException($msg));
                return;
            } elseif ($value < (float) $parameters[0]) {
                return false;
            }
        }

        if (isset($parameters[1]) && trim($parameters[1]) != '') {
            if (!is_numeric($parameters[1])) {
                $msg = 'Parameter "'.$parameters[1].'" is not a Numeric Type';
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                new SystemException($msg));
                return;
            } elseif ($value > (float) $parameters[1]) {
                return false;
            }
        }

        $convValue = $value;
        return true;
}

?>
