<?php

/**
 * Id Validation Class
 */
function variable_validations_id (&$subject, $parameters)
{
    return xarVarValidate ('int:1', $subject, $subject);
}

?>
