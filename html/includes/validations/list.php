<?php

/**
 * Lists Validation Function
 */
function variable_validations_list (&$subject, $parameters) {

    if (!is_array($subject)) {
        $msg = xarML('Not an array: "#(1)"', $subject);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    if (isset($parameters[0]) && trim($parameters[0]) != '') {
        $validation = implode(':', $parameters);
        foreach  ($subject as $key => $value) {
            $return = xarVarValidate($validation, $subject[$key]);
            //$return === NULL or $return === FALSE => return
            if (!$return) {
                return $return;
            }
        }
    }

    return true;
}

?>
