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
function variable_validations_list (&$subject, $parameters, &$name)
{
    if ($name == '') $name = '<unknown>';
    if (!is_array($subject)) {
        $msg = 'Not an array';
        throw new VariableValidationException(array($name,$subject,$msg));
    }

    if (isset($parameters[0]) && trim($parameters[0]) != '') {
        $validation = implode(':', $parameters);
        foreach  ($subject as $key => $value) {
            $return = xarVarValidate($validation, $subject[$key]);
            //$return === null or $return === false => return
            if (!$return) {
                return $return;
            }
        }
    }

    return true;
}

?>
