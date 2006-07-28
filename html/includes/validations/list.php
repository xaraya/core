<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/


/**
 * Lists Validation Function
 *
 * @throws VariableValidationException 
 **/
function variable_validations_list (&$subject, $parameters, $supress_soft_exc, &$name)
{
    if ($name == '') $name = '<unknown>';
    if (!is_array($subject)) {
        $msg = 'Not an array';
        if (!$supress_soft_exc) 
            throw new VariableValidationException(array($name,$subject,$msg));
        return false;
    }

    if (isset($parameters[0]) && trim($parameters[0]) != '') {
        $validation = implode(':', $parameters);
        foreach  ($subject as $key => $value) {
            $return = xarVarValidate($validation, $subject[$key], $supress_soft_exc);
            //$return === null or $return === false => return
            if (!$return) {
                return $return;
            }
        }
    }

    return true;
}

?>
