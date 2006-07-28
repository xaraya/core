<?php
/**
 * Image Property
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
sys::import('modules.base.xarproperties.Dynamic_TextBox_Property');

/**
 * handle the image property
 *
 * @package dynamicdata
 */
class Dynamic_Image_Property extends Dynamic_TextBox_Property
{
    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template  = 'image';
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('base');
        $info->id   = 12;
        $info->name = 'image';
        $info->desc = 'Image';

        return $info;
    }

    function validateValue($value = null)
    {
        if (!isset($value)) $value = $this->value;
        // /me thinks the default of http:// is lame. (because the default isnt a valid value)
        if (!empty($value) && $value != 'http://') {
            // TODO: add some image validation routine !
            if (preg_match('/[<>"]/',$value)) {
                $this->invalid = xarML('image URL');
                $this->value = null;
                return false;
            } else {
                $this->value = $value;
            }
        } else {
            $this->value = '';
        }
        return true;
    }
}
?>
