<?php
/**
 * Dynamic Static Text property
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/*
 * @author mikespub <mikespub@xaraya.com>
*/
class Dynamic_StaticText_Property extends Dynamic_Property
{
    public $id         = 1;
    public $name       = 'static';
    public $desc       = 'Static Text';
    public $reqmodules = array('base');

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template = 'static';
        $this->filepath = 'modules/base/xarproperties';
    }

    function validateValue($value = null)
    {
        if (isset($value) && $value != $this->value) {
            $this->invalid = xarML('static text');
            $this->value = null;
            return false;
        }
        return true;
    }
}
?>
