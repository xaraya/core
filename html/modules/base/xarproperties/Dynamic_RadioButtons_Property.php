<?php
/**
 * Radio Buttons property
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
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * handle radio buttons property
 *
 * @package dynamicdata
 */
class Dynamic_RadioButtons_Property extends Dynamic_Select_Property
{
    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template  = 'radio';

    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('base');
        $info->id   = 34;
        $info->name = 'radio';
        $info->desc = 'Radio Buttons';

        return $info;
    }

    function showOutput($data = array())
    {
        $this->template  = 'dropdown';
        return parent::showOutput($data);
    }
}
?>
