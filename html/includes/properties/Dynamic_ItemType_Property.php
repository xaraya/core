<?php
/**
 * File: $Id$
 *
 * Dynamic Item Type Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata properties
 * @author mikespub <mikespub@xaraya.com>
*/

/**
 * Include the base class
 *
 */
include_once "includes/properties/Dynamic_NumberBox_Property.php";

/**
 * Handle the item type property
 *
 * @package dynamicdata
 */
class Dynamic_ItemType_Property extends Dynamic_NumberBox_Property
{
// TODO: evaluate if we want some other output here
    // default methods from Dynamic_NumberBox_Property

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
                              'id'         => 20,
                              'name'       => 'itemtype',
                              'label'      => 'Item Type',
                              'format'     => '20',
                              'validation' => '',
                              'source'         => '',
                              'dependancies'   => '',
                              'requiresmodule' => '',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }

}

?>
