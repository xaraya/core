<?php

/**
 * HTML Validation Class
 */
function variable_validations_html ($subject, $parameters, &$convValue) {

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
                // Errormsg: Tag not allowed $tag
                return false;
            } elseif (isset($match[2]) && $allowedTags[$tag] == XARVAR_ALLOW_NO_ATTRIBS && trim($match[2]) != '') {
                // Errormsg: Atrributes not allowed for tag $tag
                return false;
            }
        }

        $convValue = $subject;
        return true;
}




?>
