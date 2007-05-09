<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 */

/**
 * Full Email Check -- Checks first thru the regexp and then by mx records
 *
 * @todo Why doesnt this throw an exception?
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