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
function variable_validations_fullemail (&$subject, $parameters=null, $supress_soft_exc)
{
    if (xarVarValidate ('email', $subject, $supress_soft_exc) &&
        xarVarValidate ('mxcheck', $subject, $supress_soft_exc)) {
        return true;
    }

    return false;
}

?>