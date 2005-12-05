<?php
/**
 * Validate a date value.
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/

/**
 * Date Validation Class
 *
 * Notes:
 * There are three formats for a date:
 * - the display format
 * - the storage format
 * - the the allowed input format(s)
 *
 * There is no distinction between the display and storage formats here, since we
 * are just doing validation, but it would be important as a property. The store
 * format will be used to format the return string if validation is successful and
 * will default to the display format. The display format is otherwise ignored, but
 * is retained here to aid compatibility with a matching date property.
 * Care is also taken with XX/YY/ZZZZ formats, as PHP uses the US ordering convention.
 *
 * Syntax is (when used from xarVarValidate) is:
 *  display-format[:store-format[:input-format:...]]
 * where each format string uses the format specifiers as defined by strftime()
 */

function variable_validations_date (&$subject, $parameters, $supress_soft_exc, &$name)
{
    if ($name == '') $name = '<unknown>';
    if (!is_string($subject)) {
        $msg = 'Not a string';
        if (!$supress_soft_exc) throw new VariableValidationException(array($name,$subject,$msg));
    }

    if (isset($parameters[0])) {
        $display_format = trim($parameters[0]);
        array_shift($parameters);
    }
    if (empty($display_format)) {
        // Default format.
        $display_format = '%d/%m/%Y';
    }

    if (isset($parameters[0])) {
        $store_format = trim($parameters[0]);
        array_shift($parameters);
    }
    if (empty($store_format)) {
        // Default store format to the display format.
        $store_format = $display_format;
    }

    $input_formats = array();
    foreach($parameters as $parameter) {
        if (trim($parameter) != '') {
            $input_formats[] = trim($parameter);
        }
        // Look for non-US style date formats of the form 'D/M/Y'.
        if (strcasecmp($parameter, '%d/%m/%y') == 0) {
            $non_us_format = true;
        }
    }

    // Trim the subject, no matter what the validation result.
    $subject = trim($subject);

    // Swap month and day if US-style date supplied and at least one of the
    // input formats include a non-US style format string (with day and month the
    // proper way around ;-)
    if (preg_match('/[0-9]+\/[0-9]+\/[0-9]+/', $subject)
        && (!empty($non_us_format) || strcasecmp($display_format, '%d/%m/%y') == 0)) {
        $split = explode('/', $subject);
        $totest = $split[1] . "/" . $split[0] . "/" . $split[2];
    } else {
        $totest = $subject;
    }

    // Convert to a timestamp.
    // TODO: instead of handing the complete conversion to strtotime, loop through
    // the valid input formats and convert according to those format strings. A
    // separate utility may be required to do that, as PHP has no functions to
    // convert a date string to a timestamp or structure, based on a specified
    // format string. Not sure how to tackle this one yet, but I suspect it may
    // involve converting the date format to a sscanf() format string, scanning
    // the date to extract the values, then plugging them into mktime(). Sounds
    // like hard work, which someone is sure to have done before now...
    $timestamp = strtotime($totest);

    // If converted okay, convert it back to a string.
    if ($timestamp > 0) {
        $subject = strftime($store_format, $timestamp);
    } else {
        $msg = 'Not a valid date format';
        if (!$supress_soft_exc) 
            throw new VariableValidationException(array($name,$subject,$msg));
    }

    return true;
}

?>