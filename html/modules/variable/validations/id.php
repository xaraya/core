<?php

/**
 * Id Validation Class
 */
function variable_validations_id ($subject, $parameters, &$convValue)
{
    return xarVarValidate ($subject, 'int:1', $convValue);
}

?>
