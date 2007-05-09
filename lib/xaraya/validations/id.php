<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/

/**
 * Id Validation Class
 *
 * Validates as integer larger than or equal than 1 'int:1'
**/
sys::import("xaraya.validations.int");
class IdValidation extends IntValidation
{
    function validate(&$subject, Array $parameters)
    {
        return parent::validate($subject,array(1));
    }
}

?>