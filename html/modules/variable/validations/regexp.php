<?php

/**
 * Regular Expression Validation Class
 */
function variable_validations_regexp ($subject, $parameters, &$convValue) {

    if (!isset($parameters[0]) || trim($parameters[0]) == '') {
            $msg = 'There is no parameter to check against in Regexp validation';
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                new SystemException($msg));
            return;
    } elseif (preg_match($parameters[0], $subject)) {
        $convValue = $subject;
        return true;
    }

    return false;

}

?>
