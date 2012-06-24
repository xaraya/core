<?php
/**
 * Short description of purpose of file
 *
 * @package core
 * @subpackage validation
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 */

/**
 * Full Email Check -- Checks first thru the regexp and then by mx records
 *
**/
sys::import('xaraya.validations.email');
class FullEmailValidation extends EmailValidation
{
    function validate(&$subject, Array $parameters)
    {
        if (parent::validate($subject,array()) && xarVarValidate ('mxcheck', $subject)) {
            return true;
        }
        return false;
    }
}
?>