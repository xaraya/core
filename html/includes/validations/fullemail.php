<?php

/**
 * File: $Id$
 *
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/

/**
 * Full Email Check -- Checks first thru the regexp and then by mx records
 */
function variable_validations_fullemail (&$subject, $parameters=null)
{
    if (xarVarValidate ('email', $subject) &&
        xarVarValidate ('mxcheck', $subject)) {
        return true;
    }

    return false;
}

?>
