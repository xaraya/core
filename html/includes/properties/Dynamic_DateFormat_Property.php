<?php
/**
 * File: $Id$
 *
 * Dynamic Data Date Format Property
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
include_once "includes/properties/Dynamic_Select_Property.php";

/**
 * Class for the date format property
 *
 * @package dynamicdata
 */
class Dynamic_DateFormat_Property extends Dynamic_Select_Property
{
    function Dynamic_DateFormat_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0) {
            $this->options = array(
                                 array('id' => 0, 'name' => xarML('d M Y H:i')),
                                 array('id' => 1, 'name' => xarML('TODO')),
                             );
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
	 	$baseInfo = array(
                              'id'         => 33,
                              'name'       => 'dateformat',
                              'label'      => 'Date Format',
                              'format'     => '33',
                              'validation' => '',
							'source'     => '',
							// ...
						   );
		return $baseInfo;
	 }

}
?>
