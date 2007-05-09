<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/

/**
 * Id Validation Class
 */
function variable_validations_id (&$subject, $parameters)
{
    return xarVarValidate ('int:1', $subject);
}

?>