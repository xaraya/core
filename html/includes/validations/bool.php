<?php


/**
 * Boolean Validation Function
 */
function variable_validations_bool (&$subject, $parameters=null) {

    if ($subject == 'true') {
        $subject = true;
    } elseif ($subject == 'false') {
        $subject = false;
    } else {
        return false;
    }

    return true;
}

?>
