<?php
/**
 * Dynamic Data Lanugage List Property
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
 * Include the base file
 *
 */
include_once "includes/properties/Dynamic_Select_Property.php";

/**
 * handle the language list property
 *
 * @package dynamicdata
 */
class Dynamic_LanguageList_Property extends Dynamic_Select_Property
{
    function Dynamic_LanguageList_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0) {
        /*  // TODO: get language list
            $list = ...;
            foreach ($list as $code => $language) {
                $this->options[] = array('id' => $code,
                                         'name' => $language);
            }
        */
            $this->options[] = array('id' => 'eng',
                                     'name' => 'English');

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
                              'id'         => 36,
                              'name'       => 'language',
                              'label'      => 'Language List',
                              'format'     => '36',
                              'validation' => '',
                            'source'     => '',
                            'dependancies' => '',
                            'requiresmodule' => '',
                            'aliases'        => '',
							'args'           => serialize($args)
							// ...
						   );
		return $baseInfo;
	 }

}

?>
