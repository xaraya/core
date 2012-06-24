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
 * Id Validation Class
 *
 * Validates as integer larger than or equal than 1 'int:1'
**/
sys::import("xaraya.validations.int");
class IdValidation extends IntValidation
{
    function validate(&$subject, Array $parameters)
    {
        return parent::validate($subject,array(1));
    }
}

?>