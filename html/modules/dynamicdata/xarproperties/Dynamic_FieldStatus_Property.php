<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 */
/**
 * Dynamic Data Field Status Property
 * @author mikespub <mikespub@xaraya.com>
*/
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * Class to handle field status
 *
 * @package dynamicdata
 */
class Dynamic_FieldStatus_Property extends Dynamic_Select_Property
{
    function __construct($args)
    {
        parent::__construct($args);

        if (count($this->options) == 0) {
            $this->options = array(
                                 array('id' => DD_PROPERTYSTATE_ACTIVE, 'name' => xarML('Active')),
                                 array('id' => DD_PROPERTYSTATE_DISABLED, 'name' => xarML('Disabled')),
                                 array('id' => DD_PROPERTYSTATE_NOINPUT, 'name' => xarML('No Input Allowed')),
                                 array('id' => DD_PROPERTYSTATE_DISPLAYONLY, 'name' => xarML('Display Only')),
                                 array('id' => DD_PROPERTYSTATE_HIDDEN, 'name' => xarML('Hidden')),
                             );
        }
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('dynamicdata');
        $info->id   = 25;
        $info->name = 'fieldstatus';
        $info->desc = 'Field Status';
		$info->filepath   = 'modules/dynamicdata/xarproperties';

        return $info;
    }
}
?>
