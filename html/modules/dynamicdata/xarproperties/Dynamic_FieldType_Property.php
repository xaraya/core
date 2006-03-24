<?php
/**
 * Dynamic Select Property
 *
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
    function Dynamic_FieldType_Property($args)
    {
        if( !isset($args['skipInit']) || ($args['skipInit'] != true) )
        {
            $this->Dynamic_Select_Property($args);
            if (count($this->options) == 0) {
                $proptypes = Dynamic_Property_Master::getPropertyTypes();
                if (!isset($proptypes)) {
                    $proptypes = array();
                }
                foreach ($proptypes as $propid => $proptype) {
                    $this->options[] = array('id' => $propid, 'name' => $proptype['label']);
                }
            }
        }
    }

    // default methods from Dynamic_Select_Property

    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $baseInfo = array(
                              'id'         => 22,
                              'name'       => 'fieldtype',
                              'label'      => 'Field Type',
                              'format'     => '22',
                            'validation' => '',
                              'source'         => '',
                              'dependancies'   => '',
                              'requiresmodule' => 'dynamicdata',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }
}

?>
