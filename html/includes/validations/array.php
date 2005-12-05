<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/

/**
 * Array Validation Function
 */
function variable_validations_array (&$subject, $parameters, $supress_soft_exc, &$name)
{
    // If the subject is not array, we can bail out, cos that's what it is all about
    if ($name == '') $name = '<unknown>';
    if (!is_array($subject)) {
        $msg = 'Not an array';
        if (!$supress_soft_exc) throw new VariableValidationException(array($name,$subject,$msg));
        return false;
    }

    if (isset($parameters[0]) && trim($parameters[0]) != '') {
        if (!is_numeric($parameters[0])) {
            // We need a number for the minimum nr of elements
            throw new BadParameterException($parameters[0],'The parameter specifying the minimum number of elements should be numeric. It is: "#(1)"');
        } elseif (count($subject) < (int) $parameters[0]) {
            // The subject has too little values
            $msg = 'Array variable has less elements "#(1)" than the specified minimum "#(2)"';
            if (!$supress_soft_exc) 
                throw new VariableValidationException(array(count($subject), $parameters[0]), $msg);
            return false;
        }
    }

    if (isset($parameters[1]) && trim($parameters[1]) != '') {
        if (!is_numeric($parameters[1])) {
            // We need a number for the maximum nr of elements
            throw new BadParameterException($parameters[1],'The parameter specifying the maximum number of elements should be numeric. It is: "#(1)"');
        } elseif (count($subject) > (int) $parameters[1]) {
            // The subject has too many values
            $msg = 'Array variable has more elements "#(1)" than the specified maximum "#(2)"';
            if (!$supress_soft_exc) 
                throw new VariableValidationException(array(count($subject), $parameters[1]), $msg);
            return false;
        }
    }

    return true;
}

?>
