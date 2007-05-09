<?php
/**
 * Base class for Variable Validations
 *
**/

interface IValidation
{
    function validate(&$subject, Array $parameters);
}

class ValueValidations extends Object implements IValidation
{

    static public function &get($type)
    {
        sys::import("xaraya.validations.$type");
        switch($type) {
            case 'array'    : $cls = 'ArrayValidation';     break;
            case 'bool'     : $cls = 'BoolValidation';      break;
            case 'checkbox' : $cls = 'CheckBoxValidation';  break;
            case 'date'     : $cls = 'DateValidation';      break;
            case 'email'    : $cls = 'EmailValidation';     break;
            case 'enum'     : $cls = 'EnumValidation';      break;
            case 'float'    : $cls = 'FloatValidation';     break;
            case 'fullemail': $cls = 'FullEmailValidation'; break;
            case 'html'     : $cls = 'HtmlValidation';      break;
            case 'id'       : $cls = 'IdValidation';        break;
            case 'int'      : $cls = 'IntValidation';       break;
            case 'isset'    : $cls = 'IssetValidation';     break;
            case 'keylist'  : $cls = 'KeyListValidation';   break;
            case 'list'     : $cls = 'ListValidation';      break;
            case 'mxcheck'  : $cls = 'MxCheckValidation';   break;
            case 'notempty' : $cls = 'NotEmptyValidation';  break;
            case 'pre'      : $cls = 'PreValidation';       break;
            case 'regexp'   : $cls = 'RegExpValidation';    break;
            case 'str'      : $cls = 'StrValidation';       break;
            case 'strlist'  : $cls = 'StrListValidation';   break;
        }
        $obj = new $cls();
        return $obj;
    }

    public function validate(&$subject, Array $parameters)
    {
        throw new Exception('Must implement');
    }
}
?>