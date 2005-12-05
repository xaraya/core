<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/

/**
 * Enum Validation Function
 */
function variable_validations_enum (&$subject, $parameters, $supress_soft_exc, &$name)
{

    $found = false;

    foreach ($parameters as $param) {
        if ($subject == $param) {
            $found = true;
        }
    }

    if ($found) {
        return true;
    } else {
        if ($name == '') $name = '<unknown>';
        $msg = 'Input given is not in list of valid options';
        $first = true;
        foreach ($parameters as $param) {
            if ($first) $first = false;
            else $msg .= ' or '; // TODO: evaluate MLS consequences later on

            $msg .= $param;
        }
        if (!$supress_soft_exc) 
            throw new VariableValidationException(array($name,$subject,$msg));
    }
}

?>