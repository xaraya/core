<?php

/**
 * @package core\validation
 * @subpackage validation
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */
/**
 * Base class for Variable Validations
 *
**/

interface IValidation
{
    public function validate(&$subject, array $parameters);
}

/**
 * @package core\validation
 * @subpackage validation
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */
class ValueValidations extends xarObject implements IValidation
{
    public static function &get($type)
    {
        $cls = 'ValueValidations';
        switch ($type) {
            case 'array': $cls = 'ArrayValidation';
                break;
            case 'bool': $cls = 'BoolValidation';
                break;
            case 'checkbox': $cls = 'CheckBoxValidation';
                break;
            case 'date': $cls = 'DateValidation';
                break;
            case 'email': $cls = 'EmailValidation';
                break;
            case 'enum': $cls = 'EnumValidation';
                break;
            case 'float': $cls = 'FloatValidation';
                break;
            case 'fullemail': $cls = 'FullEmailValidation';
                break;
            case 'html': $cls = 'HtmlValidation';
                break;
            case 'id': $cls = 'IdValidation';
                break;
            case 'int': $cls = 'IntValidation';
                break;
            case 'isset': $cls = 'IssetValidation';
                break;
            case 'keylist': $cls = 'KeyListValidation';
                break;
            case 'list': $cls = 'ListValidation';
                break;
            case 'mxcheck': $cls = 'MxCheckValidation';
                break;
            case 'notempty': $cls = 'NotEmptyValidation';
                break;
            case 'pre': $cls = 'PreValidation';
                break;
            case 'regexp': $cls = 'RegExpValidation';
                break;
            case 'str': $cls = 'StrValidation';
                break;
            case 'strlist': $cls = 'StrListValidation';
                break;
        }
        $obj = new $cls();
        return $obj;
    }

    public function validate(&$subject, array $parameters)
    {
        throw new Exception('Must implement');
    }
}
