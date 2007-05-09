<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 */

/**
 * Full Email Check -- Checks first thru the regexp and then by mx records
 *
**/
sys::import('xaraya.validations.email');
class FullEmailValidation extends EmailValidations
{
    function validate(&$subject, $parameters=null)
    {
        if (parent::validate($subject,array()) && xarVarValidate ('mxcheck', $subject)) {
            return true;
        }
        return false;
    }
}
?>