<?php

/**
 * Checkbox Validation Class
 */
function variable_validations_checkbox {$subject, $parameters, &$convValue) {

    if (is_string($subject)) {
        $subject = true;
    } elseif (empty($subject) || is_null($subject)) {
        $subject = false;
    } else {
        return false;
    }

    $convValue = $subject;
    return true;
}

?>
