<?php

/**
 * File: $Id$
 *
 * Title: Pre-process values.
 * Desc: Allows a value to be pre-processed before (optionally)
 * passing on to another validation method. Each option is processed
 * in order, left-to-right, and can be chained together.
 *
 * Author: Jason Judge (judgej@academe.co.uk)
 *
 * Pre-process options are:
 *
 * trim         - trim the string of spaces left and right
 * upper        - make alphabetic characters upper-case
 * lower        - make alphabetic characters lower-case
 * alpha        - strip out all but alphabetical characters (a-z, A-Z)
 * alnum        - strip out all the alphanumeric characters (a-z, A-Z, 0-9)
 * ftoken       - convert to a token suitable for use within a file or path name
 *                (a-z, A-Z, 0-9, _, -, starting a-z or A-Z)
 *                Note: this token is not a file or path name in itself, but is more to
 *                be used as an item name that happens to be linked to template filenames
 * passthru:... - pass the remainder of the parameters on to further validation, which could
 * alias: val:... be any string validation type (e-mail, strings with min/max lengths, etc).
 *                Alias for passthru is 'val'.
 * html         - prep for HTML - xarVarPrepHTMLDisplay()
 * display      - prep for display - xarVarPrepForDisplay()
 * store        - prep for store (uses the default database connection for escaping quotes)
 * field:name   - name of the form field; if a validation error occurs, an error message will
 *                be generated, with the field name quoted.
 *
 * Examples:
 * 1. Get a trimmed, lower-case string between 5 and 10 characters long:
 *    'pre:trim:lower:passthru:str:5:10'
 * 2. Get a mandatory item 'name', in lower-case, and report validation errors for
 *    field 'Item Name':
 *    'pre:lower:ftoken:field:Item Name:val:notempty'
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/

/**
 * Strings Validation Class
 */
function variable_validations_pre (&$subject, $parameters, $supress_soft_exc) 
{
    // Start by forcing the subject into a string.
    if (!is_string($subject)) {
        $subject = strval($subject);
    }

    // Initialise the name of the field.
    $fieldname = '';

    // Default return value (success).
    $return = true;

    // TODO: input filters for use with content that is known will be
    // stored as comments etc. These filters should take the current
    // user privileges into account so, for example, admins can post
    // stuff that is filtered out from anonymous postings.

    // Loop through the parameter options.
    // Note: we count on every iteration as the loop may consume
    // any number of parameters.
    while (count($parameters) > 0) {
        // Consume a parameter.
        $param = array_shift($parameters);

        // Choose an action (and feel free to add more:)
        switch ($param) {
            case 'trim' :
                $subject = trim($subject);
                break;
            case 'upper' :
                $subject = strtoupper($subject);
                break;
            case 'lower' :
                $subject = strtolower($subject);
                break;
            case 'html' :
                $subject = xarVarPrepHTMLDisplay($subject);
                break;
            case 'display' :
                $subject = xarVarPrepForDisplay($subject);
                break;
            case 'store' :
            case 'sql' :
                // Preparing for use in a quoted SQL string.
                $dbconn =& xarDBGetConn();
                $subject = $dbconn->qstr($subject);
                break;
            case 'alpha' :
                $subject = preg_replace('/[^a-z]+/i', '', $subject);
                break;
            case 'alnum' :
                $subject = preg_replace('/[^a-z0-9]+/i', '', $subject);
                break;
            case 'ftoken' :
                // Filename-compatible token. Use in conjunction with
                // 'lower' if case preservation is required too.
                $subject = preg_replace(
                    array('/[ _]+/', '/[^-a-z0-9_]+/i'),
                    array('_', ''),
                    trim($subject)
                );
                break;
            case 'field' :
                if (!empty($parameters)) {
                    $fieldname = array_shift($parameters);
                }
                break;
            default:
                // Assume an unrecognised option refers to an alternative validation
                // type, making 'passthru' redundant. I'm not sure if we really should
                // do this or raise an error. Doing it this way simplifies the validation,
                // so fetching a string 'str:1:20' can be trimmed by adding a simple
                // prefix - 'pre:trim:str:1:20'
                // Put the current parameter back onto the parameters stack, as we will now
                // be treating it as the passthru validation type.
                array_unshift($parameters, $param);
                // $msg = xarML('Invalid option "#(1)" in validation type "pre"', $param);
                // xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
                // return false;
            case 'passthru' :
            case 'val' :
                if (!empty($parameters)) {
                    // Roll up the remaining parameters.
                    $validation = implode(':', $parameters);
                    $return = xarVarValidate($validation, $subject, $supress_soft_exc);
                    if (!$return && !empty($fieldname)) {
                        // Add another error message, naming the field.
                        // Combine it with the 'short' details of the last message logged,
                        // with the assumption that it will contain some useful details.
                        $errorstack = xarErrorGet();
                        $error = array_shift($errorstack);
                        $msg = xarML('#(1) is invalid. [#(2)]', $fieldname, $error['short']);
                        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
                        //return $return;
                    }
                }
                // Break out of the switch *and* the parameter loop.
                // Once we hit the passthru, we have nothing more to process here.
                break 2;
        }
    }
    
    // Single point of exit.
    return $return;
}

?>