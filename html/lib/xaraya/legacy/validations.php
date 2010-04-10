<?php

    // Legacy validations by dataproperty
    
    function dropdown($validation)
    {
        $options = array();
        try {
            foreach($validation as $id => $name) {
                array_push($options, array('id' => $id, 'name' => $name));
            }
        } catch (Exception $s) {
            $options = array();
        }
        return $options;
    }
?>