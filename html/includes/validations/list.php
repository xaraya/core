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
 * Lists Validation Function
 */
function variable_validations_list (&$subject, $parameters, $supress_soft_exc) 
{

    if (!is_array($subject)) {
        $msg = xarML('Not an array: "#(1)"', $subject);

        // NULL is a special case. Perform a 'soft' fail should we encounter a NULL
        if (!($subject === NULL && $supress_soft_exc)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
            return;
        } else {
            return false;
        }
    }

    if (isset($parameters[0]) && trim($parameters[0]) != '') {
        $validation = implode(':', $parameters);
        foreach  ($subject as $key => $value) {
            $return = xarVarValidate($validation, $subject[$key], $supress_soft_exc);
            //$return === NULL or $return === FALSE => return
            if (!$return) {
                return $return;
            }
        }
    }

    return true;
}

?>