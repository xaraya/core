<?php
/**
 * Dynamic Language List Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */

include_once "includes/properties/Dynamic_Select_Property.php";

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
}

?>
