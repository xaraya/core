<?php


/**
 * Boolean Validation Function
 */
function variable_validations_bool {$subject, $parameters, &$convValue) {

        if ($subject == 'true') {
            $subject = true;
        } elseif ($subject == 'false') {
            $subject = false;
        } else { // Is this possible??
            return false;
        }

        $convValue = $subject;
        return true;
    }
}

?>
