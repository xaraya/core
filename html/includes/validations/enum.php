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
 * Enum Validation Function
 */
function variable_validations_enum (&$subject, $parameters) {

    $found = false;
    
    foreach ($parameters as $param) {
        if ($subject == $param) {
            $found = true;
        }
    }
    
    if ($found) {
        return true;
    } else {
        $msg = xarML('Input "#(1)" was not one of the possibilities: "', $subject);
        $first = true;
        foreach ($parameters as $param) {
            if ($first) $first = false;
            else $msg .= ' or ';

            $msg .= $param;
        }
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }
}

?>
