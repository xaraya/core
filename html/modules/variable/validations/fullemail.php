<?php

/**
 * Full Email Check -- Checks first thru the regexp and then by mx records
 */
function variable_validations_fullemail (&$subject, $parameters=null)
{
    if (xarVarValidate ('email', $subject, $subject) &&
        xarVarValidate ('mxcheck', $subject, $subject)) {
        return true;
    }

    return false;
}

?>
