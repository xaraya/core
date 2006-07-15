<?php
/**
 * Hidden property
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Class to handle hidden properties
 * @author mikespub <mikespub@xaraya.com>
 * @package dynamicdata
 */
class Dynamic_Hidden_Property extends Dynamic_Property
{
    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template = 'hidden';
		$this->filepath   = 'modules/base/xarproperties';
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('base');
        $info->id   = 18;
        $info->name = 'hidden';
        $info->desc = 'Hidden';

        return $info;
    }

    function validateValue($value = null)
    {
        if (isset($value) && $value != $this->value) {
            $this->invalid = xarML('hidden field');
            $this->value = null;
            return false;
        } else {
            return true;
        }
    }
}
?>
