<?php
/**
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
 * alnum        - strip out all but alphanumeric characters (a-z, A-Z, 0-9)
 * num          - strip out all but numeric characters (0-9)
 * ftoken       - convert to a token suitable for use within a file or path name
 *                (a-z, A-Z, 0-9, _, -, starting a-z or A-Z)
 *                Note: this token is not a file or path name in itself, but is more to
 *                be used as an item name that happens to be linked to template filenames
 * vtoken       - convert to a token suitable for use as a variable or function name.
 *                Similar to ftoken; must start with a letter or underscore
 * passthru:... - pass the remainder of the parameters on to further validation, which could
 * alias: val:... be any string validation type (e-mail, strings with min/max lengths, etc).
 *                Alias for passthru is 'val'.
 * html         - prep for HTML - xarVarPrepHTMLDisplay()
 * display      - prep for display - xarVarPrepForDisplay()
 * store        - prep for store (uses the default database connection for escaping quotes)
 * field:name   - name of the form field; if a validation error occurs, an error message will
 *                be generated, with the field name quoted.
 * left:bytes   - take only the left 'bytes' number of bytes (i.e. truncate the input string)
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
 * @throws VariableValidationException
**/

/**
 * Strings Validation Class
**/
sys::import('xaraya.validations');
class PreValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        // Start by forcing the subject into a string.
        //@todo extend it from string validation then??
        if (isset($subject) && !is_string($subject)) {
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
        // or replace any number of parameters.
        while (count($parameters) > 0 && $return) {
            // Consume a parameter.
            $param = array_shift($parameters);

            // Choose an action (and feel free to add more:)
            // The first switch is for rules that require a value to be set.
            if (isset($subject)) {
                switch ($param) {
                    case 'trim'   : $subject = trim($subject); break;
                    case 'upper'  : $subject = strtoupper($subject); break;
                    case 'lower'  : $subject = strtolower($subject); break;
                    case 'html'   : $subject = xarVarPrepHTMLDisplay($subject); break;
                    case 'display': $subject = xarVarPrepForDisplay($subject); break;
                    case 'store'  :
                    case 'sql'    :
                        // Preparing for use as a quoted SQL string.
                        $dbconn =& xarDBGetConn();
                        // @todo when using bindvars this can be just (string) $subject
                        $subject = $dbconn->qstr($subject);
                        break;
                    case 'alpha'  : $subject = preg_replace('/[^a-z]+/i', '', $subject); break;
                    case 'alnum'  : $subject = preg_replace('/[^a-z0-9]+/i', '', $subject); break;
                    case 'num'    : $subject = preg_replace('/[^0-9]+/i', '', $subject); break;
                    case 'vtoken' :
                        // Variable-name compatible token. Same as function names.
                        $subject = preg_replace(
                            array('/[ _-]+/', '/[^a-zA-Z0-9_\x7f-\xff]+/'),
                            array('_', ''),
                            trim($subject)
                        );
                        // The token must start with a letter or underscore.
                        // Raise an error if not.
                        if (!empty($subject) && !preg_match('/^[a-zA-Z_]/', $subject)) {
                            $msg = 'Value "#(1)" is not a valid variable name';
                            throw new VariableValidationException($subject,$msg);
                        }
                        break;
                    case 'ftoken' :
                        // Filename-compatible token. Use in conjunction with
                        // 'lower' if case forcing is required too.
                        // Note: this is not a file name, so periods/full stops/dots
                        // are not included in the accepted characters.
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
                    case 'left' :
                        // Truncate the string to 'n' bytes, or chars (depending on mb settings).
                        if (!empty($parameters)) {
                            $trimvalue = array_shift($parameters);
                            if (!empty($trimvalue) && is_numeric($trimvalue)) {
                                $subject = substr($subject, 0, $trimvalue);
                            }
                        }
                        break;
                } // switch
            } else {
                // The subject is not set. Go through the motions of processing
                // the parameters, without referencing the subject. We want to
                // consume the 'pre' parameters, in case there are further validation
                // rules to apply.
                switch($param) {
                    case 'field':
                    case 'left':
                        if (!empty($parameters)) {array_shift($parameters);}
                        break;
                }
            }

                // The second switch is for rules that don't require a value to be set.
            switch ($param) {
                case 'trim' :
                case 'upper' :
                case 'lower' :
                case 'html' :
                case 'display' :
                case 'store' :
                case 'sql' :
                case 'alpha' :
                case 'alnum' :
                case 'num' :
                case 'vtoken' :
                case 'ftoken' :
                case 'field' :
                case 'left' :
                    break;

                case 'field' :
                    if (!empty($parameters)) {
                        $fieldname = array_shift($parameters);
                    }
                    break;

                    // Assume an unrecognised option refers to an alternative validation
                    // type, making 'passthru' redundant. Doing it this way simplifies the
                    // validation, so fetching a string 'str:1:20' can be trimmed by adding
                    // a simple prefix - 'pre:trim:str:1:20'
                default:
                    if ($param != 'passthru' && $param != 'val') {
                        // Put the current parameter back onto the parameters stack, as we will now
                        // be treating it as the passthru validation type.
                        array_unshift($parameters, $param);
                    }

                    if (!empty($parameters)) {
                        // Roll up the remaining parameters.
                        $validation = implode(':', $parameters);
                        $return = xarVarValidate($validation, $subject);
                    }

                    // The passthru validation consumes all further parameters, so clear
                    // them here to exit the outer loop.
                    $parameters = array();
                    break;
                }
        }

        // CHECKME: since we either handle it directly and/or the stack is never filled. Is this still needed?
        if (!$return && !empty($fieldname)) {
            // Add another error message, naming the field.
            // Combine it with the 'short' details of the last message logged,
            // with the assumption that it will contain some useful details.
            $msg = 'Field "#(1)" is invalid. [#(2)]';
            throw new VariableValidationException(array($fieldname,'UNKNOWN'),$msg);
        }

        // Single point of exit.
        return $return;
    }
}
?>
