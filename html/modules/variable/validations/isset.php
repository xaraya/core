<?php

/**
 * IsSet Validation Function
 */
function variable_validations_isset (&$subject, $parameters) {

    if (!isset($subject)) {
        return false;
    }

    return true;
}

?>
