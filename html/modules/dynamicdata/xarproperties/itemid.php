<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.integerbox');

/**
 * Handle item id property
 */
class ItemIDProperty extends NumberBoxProperty
{
    public $id         = 21;
    public $name       = 'itemid';
    public $desc       = 'Item ID';
    public $reqmodules = array('dynamicdata');

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'dynamic_data';
        $this->template = 'itemid';
        $this->filepath   = 'modules/dynamicdata/xarproperties';
    }

    function checkInput($name = '', $value = null)
    {
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            if (!xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET)) {return;}
        }
        return $this->validateValue($value);
    }
}

?>
