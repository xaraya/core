<?php

/**
 * notempty Validation Function
 */
function variable_validations_notempty (&$subject, $parameters) {

    if (empty($subject)) {
        $msg = xarML('Variable "#(1)" should not be empty', $subject);
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }

    return true;
}

?>
