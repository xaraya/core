<?php
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