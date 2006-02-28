<?php
/**
 * Dynamic Select Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Include the base class
 *
 */
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * Class to handle field type property
 *
 * @package dynamicdata
 */
class Dynamic_FieldType_Property extends Dynamic_Select_Property
{
    function __construct($args)
    {
        parent::__construct($args);

        if (count($this->options) == 0) {
            $proptypes = Dynamic_Property_Master::getPropertyTypes();
            if (!isset($proptypes)) $proptypes = array();
                
            foreach ($proptypes as $propid => $proptype) {
                $this->options[] = array('id' => $propid, 'name' => $proptype['label']);
            }
        }
    }
        
    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('dynamicdata');
        $info->id   = 22;
        $info->name = 'fieldtype';
        $info->desc = 'Field Type';

        return $info;
    }
}
?>
