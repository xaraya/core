<?php
    function privileges_user_errors()
    {
        if(!xarVarFetch('layout',   'isset', $data['layout']   , 'default', XARVAR_DONT_SET)) {return;}
        return $data;
    }
?>