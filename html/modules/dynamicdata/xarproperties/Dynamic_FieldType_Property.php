<?php
/**
 * Dynamic Select Property
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
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
		$this->filepath   = 'modules/dynamicdata/xarproperties';

        if (count($this->options) == 0) {
            $proptypes = Dynamic_Property_Master::getPropertyTypes();
            if (!isset($proptypes)) $proptypes = array();

            foreach ($proptypes as $propid => $proptype) {
                // TODO: label isnt guaranteed to be unique, if not, leads to some surprises.
                $this->options[$proptype['label']] = array('id' => $propid, 'name' => $proptype['label']);
            }
        }
        // sort em by name
        ksort($this->options);
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
