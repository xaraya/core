<?php

/**
 * Id Validation Class
 */
function variable_validations_id ($subject, $parameters, &$convValue)
{
    return xarVarValidate ('int:1', $subject, $convValue);
}

?>
