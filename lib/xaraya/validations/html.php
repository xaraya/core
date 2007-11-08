<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2003 by the Xaraya Development Team.
*/

/**
 * HTML Validation Class
 *
 * @throws VariableValidationException
**/
sys::import('xaraya.validations');
class HtmlValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        assert('($parameters[0] == "restricted" ||
                 $parameters[0] == "basic" ||
                 $parameters[0] == "enhanced" ||
                 $parameters[0] == "admin")');

        if ($parameters[0] == 'admin') {
            return true;
        }

        $allowedTags = array();
        foreach (xarConfigVars::get(null,'Site.Core.AllowableHTML') as $k=>$v) {
            if ($v) {
                $allowedTags[] = $k;
            }
        }
        preg_match_all("|</?(\w+)(\s+.*?)?/?>|", $subject, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $tag = strtolower($match[1]);
            if (!isset($allowedTags[$tag])) {
                $msg = 'Specified tag is not allowed';
                throw new VariableValidationException(null, $msg);
            } elseif (isset($match[2]) && $allowedTags[$tag] == XARVAR_ALLOW_NO_ATTRIBS && trim($match[2]) != '') {
                // We should check for on* attributes
                // Attributes should be restricted too, shouldnt they?
                $msg = 'Attributes are not allowed for tag "#(1)"';
                throw new VariableValidationException(array($tag),$msg);
            }
        }
        return true;
    }
}
?>
