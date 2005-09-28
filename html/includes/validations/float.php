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
 * Float Validation Function
 */
function variable_validations_float (&$subject, $parameters, $supress_soft_exc, &$name)
{
        $value = (float)$subject;

        if ("$subject" != "$value") {
            if ($name != '')
                $msg = xarML('Variable #(1) is not a float type: "#(2)"', $name, $subject);
            else
                $msg = xarML('Not a float type: "#(1)"', $subject);
            if (!$supress_soft_exc) xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
            return false;
        }

        if (isset($parameters[0]) && trim($parameters[0]) != '') {
            if (!is_numeric($parameters[0])) {
                $msg = 'Parameter "'.$parameters[0].'" is not a Numeric Type';
                xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
                return;
            } elseif ($value < (float) $parameters[0]) {
                $msg = xarML('Float Value "#(1)" is smaller than the specified minimum "#(2)"', $value, $parameters[0]);
                if (!$supress_soft_exc) xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
                return false;
            }
        }

        if (isset($parameters[1]) && trim($parameters[1]) != '') {
            if (!is_numeric($parameters[1])) {
                $msg = 'Parameter "'.$parameters[1].'" is not a Numeric Type';
                xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
                return;
            } elseif ($value > (float) $parameters[1]) {
                $msg = xarML('Float Value "#(1)" is bigger than the specified maximum "#(2)"', $value, $parameters[1]);
                if (!$supress_soft_exc) xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
                return false;
            }
        }

        $subject = $value;
        return true;
}

?>
