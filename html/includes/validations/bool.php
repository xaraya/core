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
 * Boolean Validation Function
 */
function variable_validations_bool (&$subject, $parameters=null) {

    if ($subject == 'true') {
        $subject = true;
    } elseif ($subject == 'false') {
        $subject = false;
    } else {
        return false;
    }

    return true;
}

?>
