<?php
/**
 * Short description of purpose of file
 *
 * @package core\validation
 * @subpackage validation
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
*/

sys::import('xaraya.validations');
sys::import('xaraya.facades.config');
use Xaraya\Facades\xarConfig3;

/**
 * HTML Validation Class
 *
 * @throws VariableValidationException
**/
class HtmlValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        assert(($parameters[0] == "restricted" ||
                 $parameters[0] == "basic" ||
                 $parameters[0] == "enhanced" ||
                 $parameters[0] == "admin"));

        if ($parameters[0] == 'admin') {
            return true;
        }

        $allowedTags = array();
        foreach (xarConfig3::getVar('Site.Core.AllowableHTML') as $k=>$v) {
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
            } elseif (isset($match[2]) && $allowedTags[$tag] == xarVar::ALLOW_NO_ATTRIBS && trim($match[2]) != '') {
                // We should check for on* attributes
                // Attributes should be restricted too, shouldnt they?
                $msg = 'Attributes are not allowed for tag "#(1)"';
                throw new VariableValidationException(array($tag),$msg);
            }
        }
        return true;
    }
}
