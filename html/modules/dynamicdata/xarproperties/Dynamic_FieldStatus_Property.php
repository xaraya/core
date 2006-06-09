<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
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
                                 array('id' => 0, 'name' => xarML('Disabled')),
                                 array('id' => 1, 'name' => xarML('Active')),
                                 array('id' => 2, 'name' => xarML('Display Only')),
                                 array('id' => 3, 'name' => xarML('Hidden')),
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

        return $info;
    }
}
?>
