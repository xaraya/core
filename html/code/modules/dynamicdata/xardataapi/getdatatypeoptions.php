<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 */

    function dynamicdata_dataapi_getdatatypeoptions() 
    {        
        $options['datatypes'] = array(
            1 => "varchar(64)",
            2 => "varchar(254)",
            3 => "tinyint",
            4 => "int",
            5 => "float",
            6 => "text",
        );

        $options['collations'] = array(
            1 => "utf8_general_ci",
            2 => "iso-8859-1",
        );

        $options['nulls'] = array(
            0 => "not null",
            1 => "null",
        );

        $options['attributes'] = array(
            0 => "",
            1 => "unsigned",
        );
        
        return $options;
    }
?>