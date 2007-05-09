<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/


/**
 * Regular Expression Validation Class
 *
 * @throws VariableValidationException
 **/
function variable_validations_regexp (&$subject, $parameters, &$name)
{
    if ($name == '') $name = '<unknown>';
    if (!isset($parameters[0]) || trim($parameters[0]) == '') {
        $msg = 'There is not parameter to check agains the regular expression validation.';
        // CHECK: this is probably better a BadParameterException ?
        throw new VariableValidationException(array($name,$subject,$msg));
    } elseif (preg_match($parameters[0], $subject)) {
        return true;
    }

    $msg = 'Variable #(1): "#(2)" did not match pattern "#(3)"';
    throw new VariableValidationException(array( $name, $subject, $parameters[0]),$msg);
}

?>
