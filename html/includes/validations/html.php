<?php

/**
 * HTML Validation Class
 */
function variable_validations_html (&$subject, $parameters) {

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
                $msg = xarML('Tag not allowed: "#(1)"', $tag);
                xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
                return false;
            } elseif (isset($match[2]) && $allowedTags[$tag] == XARVAR_ALLOW_NO_ATTRIBS && trim($match[2]) != '') {
                // We should check for on* attributes
                // Attributes should be restricted too, shouldnt they?
                $msg = xarML('Attributes are not allowed fo this tag : "#(1)"', $tag);
                xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
                return false;
            }
        }

        return true;
}

?>
