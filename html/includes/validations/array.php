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
 * Array Validation Function
 */
function variable_validations_array (&$subject, $parameters) {

    if ($subject === NULL) {
        //Most likely there will be a foreach on this $subject right
        //after the validation... I think it is nicer to have an empty array, then
        //have the devs checking the $subject == NULL...
        $subject = array();
    }
    
    if (!is_array($subject)) {
        $msg = xarML('Not an array: "#(1)"', $subject);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    if (isset($parameters[0]) && trim($parameters[0]) != '') {
        if (!is_numeric($parameters[0])) {
            $msg = 'Parameter "'.$parameters[0].'" is not a Numeric Type';
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
            return;
        } elseif (count($subject) < (int) $parameters[0]) {
            $msg = xarML('Array variable has less elements "#(1)" than the specified minimum "#(2)"', count($subject), $parameters[0]);
            xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
            return false;
        }
    }

    if (isset($parameters[1]) && trim($parameters[1]) != '') {
        if (!is_numeric($parameters[1])) {
            $msg = 'Parameter "'.$parameters[1].'" is not a Numeric Type';
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
            return;
        } elseif (count($subject) > (int) $parameters[1]) {
            $msg = xarML('Array variable has more elements "#(1)" than the specified maximum "#(2)"', $value, $parameters[1]);
            xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
            return false;
        }
    }


    return true;
}

?>
