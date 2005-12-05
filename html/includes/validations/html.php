<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/

/**
 * HTML Validation Class
 */
function variable_validations_html (&$subject, $parameters, $supress_soft_exc, &$name)
{
        assert('($parameters[0] == "restricted" ||
                 $parameters[0] == "basic" ||
                 $parameters[0] == "enhanced" ||
                 $parameters[0] == "admin")');

        if ($parameters[0] == 'admin') {
            return true;
        }

        $allowedTags = xarVar__getAllowedTags($parameters[0]);
        preg_match_all("|</?(\w+)(\s+.*?)?/?>|", $subject, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $tag = strtolower($match[1]);
            if (!isset($allowedTags[$tag])) {
                if ($name == '') $name = '<unknown>';
                $msg = 'Specified tag is not allowed';
                if (!$supress_soft_exc) 
                    throw new VariableValidationException(array($name,$subject,$msg));
            } elseif (isset($match[2]) && $allowedTags[$tag] == XARVAR_ALLOW_NO_ATTRIBS && trim($match[2]) != '') {
                // We should check for on* attributes
                // Attributes should be restricted too, shouldnt they?
                $msg = 'Attributes are not allowed for this tag in variable #(1): "#(2)"';
                if (!$supress_soft_exc) 
                    throw new VariableValidationException(array($name,$tag),$msg);
            }
        }

        return true;
}

?>