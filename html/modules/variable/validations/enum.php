<?php

/**
 * Enum Validation Function
 */
function variable_validations_enum (&$subject, $parameters) {

    $found = false;
    
    foreach ($parameters as $param) {
        if ($subject == $param) {
            $found = true;
        }
    }
    
    if ($found) {
        return true;
    } else {
        return false;
    }
}

?>
